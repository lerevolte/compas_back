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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->comment('');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->text('settings')->nullable();
            $table->timestamps();
            $table->longtext('tables')->nullable();
            $table->longtext('bitrix24_config')->nullable();
            $table->integer('sort')->nullable();
            $table->softDeletes();
            $table->string('last_name')->nullable();
            $table->string('second_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('work_phone')->nullable();
            $table->integer('crm_id')->nullable();
            $table->string('api_token', 60)->nullable();
            $table->string('color')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
