<?php

namespace Mydnic\Subscribers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Mydnic\Subscribers\Events\SubscriberVerified;
use Mydnic\Subscribers\Subscriber;
use Mydnic\Subscribers\Http\Requests\StoreSubscriberRequest;
use Mydnic\Subscribers\Http\Requests\DeleteSubscriberRequest;
use Mydnic\Subscribers\Http\Requests\VerifySubscriberRequest;
use Mydnic\Subscribers\Exceptions\SubscriberVerificationException;

class SubscriberController extends Controller
{
    public function store(StoreSubscriberRequest $request)
    {
        $subscriber = new Subscriber();
        $subscriber->email = $request->email;
        $subscriber->user_id = auth()->check() ? auth()->user()->id : null;
        $subscriber->client_id = ($clientId = get_client_id()) ?: null;
        $subscriber->session_id = $request->session()->getId();
        $subscriber->referer = ($referer = request()->headers->get('referer')) ? substr($referer, 0, 255) : null;
        $subscriber->ip = $request->ip();
        $subscriber->country_id = \MyNet::getClientISOCountry();
        $subscriber->user_agent = ($userAgent = $request->header('User-Agent')) ? substr($userAgent, 0, 255) : null;
        $subscriber->save();

        if (config('laravel-subscribers.verify')) {
            $subscriber->sendEmailVerificationNotification();
            return redirect()->route(config('laravel-subscribers.redirect_url'))
                ->with('subscribed', __('Please verify your email address!'));
        }

        return redirect()->route(config('laravel-subscribers.redirect_url'))
            ->with('subscribed', __('You are successfully subscribed to our list!'));
    }

    public function delete(DeleteSubscriberRequest $request)
    {
        $request->subscriber()->delete();
        return view('subscribe.deleted');
    }

    public function verify(VerifySubscriberRequest $request)
    {
        $subscriber = Subscriber::find($request->id);
        if (!hash_equals((string) $request->route('id'), (string) $subscriber->getKey())) {
            throw new SubscriberVerificationException;
        }

        if (!hash_equals((string) $request->route('hash'), sha1($subscriber->getEmailForVerification()))) {
            throw new SubscriberVerificationException;
        }

        if ($subscriber->hasVerifiedEmail()) {
            return $request->wantsJson()
                ? new Response('', 204)
                : redirect($this->redirectPath());
        }

        if ($subscriber->markEmailAsVerified()) {
            event(new SubscriberVerified($subscriber));
        }

        return $request->wantsJson()
            ? new Response('', 204)
            : redirect()->route(config('laravel-subscribers.redirect_url'))->with('verified', __('You are successfully subscribed to our list!'));
    }
}
