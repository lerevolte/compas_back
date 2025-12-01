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
            $table->comment('');
            $table->bigIncrements('id');
            $table->string('role_id')->nullable();
            $table->string('name')->nullable()->default('не выбрано');
            $table->string('email')->nullable();
            $table->text('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->text('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('choosed_at')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->timestamp('geo_updated_at')->nullable();
            $table->string('last_name')->nullable();
            $table->string('second_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('work_phone')->nullable();
            $table->text('token')->nullable();
            $table->text('device_id')->nullable();
            $table->string('app_version')->nullable();
            $table->text('permission_geo')->nullable();
            $table->text('permission_notification')->nullable();
            $table->string('api_token', 60)->nullable();
            $table->string('color')->nullable();
            $table->longText('tables')->nullable();
            $table->text('bitrix24_config')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('employee_id')->nullable();
            $table->tinyInteger('is_admin')->nullable()->default(0);
            $table->dateTime('token_expiration_date')->nullable();
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
