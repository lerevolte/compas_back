<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('phone_verifications')) {
            return;
        }
        Schema::create('phone_verifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('phone', 20)->index();
            $table->string('id_sms', 64)->nullable();
            $table->string('call_to', 32)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('token', 64)->nullable()->index();
            $table->string('ip', 45)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_verifications');
    }
};
