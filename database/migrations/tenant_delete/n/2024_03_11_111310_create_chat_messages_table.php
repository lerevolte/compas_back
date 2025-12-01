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
            $table->id();
            $table->tinyInteger('read')->default(0);
            $table->text('message')->nullable();
            $table->text('files')->nullable();
            $table->text('images')->nullable();
            $table->text('audio_file')->nullable();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('group_id')->nullable();
            $table->timestamps();
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
