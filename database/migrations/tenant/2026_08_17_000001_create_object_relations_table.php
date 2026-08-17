<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('object_relations')) {
            return;
        }

        Schema::create('object_relations', function (Blueprint $table) {
            $table->id();
            $table->string('source_slug', 64);
            $table->unsignedBigInteger('source_id');
            $table->string('target_slug', 64);
            $table->unsignedBigInteger('target_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->unique(['source_slug', 'source_id', 'target_slug', 'target_id'], 'object_relations_pair_unique');
            $table->index(['target_slug', 'target_id'], 'object_relations_target_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('object_relations');
    }
};
