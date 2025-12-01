<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->boolean('read')->default(false);
            $table->text('message')->nullable();
            $table->text('files')->nullable();
            $table->text('images')->nullable();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('group_id')->nullable();
            $table->timestamps();
            $table->text('audio_file')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
};
