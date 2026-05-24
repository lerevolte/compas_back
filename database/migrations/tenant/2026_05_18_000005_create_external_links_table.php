<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('external_links')) {
            Schema::create('external_links', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('token', 64)->unique();
                $table->string('model_slug', 255);
                $table->unsignedBigInteger('model_id');
                $table->timestamp('expires_at')->nullable();
                $table->unsignedInteger('max_visits')->nullable();
                $table->unsignedInteger('visits')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['model_slug', 'model_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('external_links');
    }
};
