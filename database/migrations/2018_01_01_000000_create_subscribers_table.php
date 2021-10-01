<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscribersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('client_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('referer')->nullable();
            $table->string('ip', 45)->nullable();
            $table->char('country_id', 2)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->timestamp('email_verified_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscribers');
    }
}
