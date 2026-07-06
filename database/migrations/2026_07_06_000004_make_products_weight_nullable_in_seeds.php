<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('seeds');
        if (!$schema->hasTable('products')) {
            return;
        }

        $schema->table('products', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('products', 'weight')) {
                $table->integer('weight')->nullable()->default(null)->change();
            }
            if ($schema->hasColumn('products', 'quantity')) {
                $table->integer('quantity')->nullable()->default(null)->change();
            }
            if ($schema->hasColumn('products', 'price')) {
                $table->double('price', 8, 2)->nullable()->default(null)->change();
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('seeds');
        $db = DB::connection('seeds');
        if (!$schema->hasTable('products')) {
            return;
        }

        if ($schema->hasColumn('products', 'weight')) {
            $db->table('products')->whereNull('weight')->update(['weight' => 1]);
        }
        if ($schema->hasColumn('products', 'quantity')) {
            $db->table('products')->whereNull('quantity')->update(['quantity' => 0]);
        }
        if ($schema->hasColumn('products', 'price')) {
            $db->table('products')->whereNull('price')->update(['price' => 0]);
        }

        $schema->table('products', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('products', 'weight')) {
                $table->integer('weight')->nullable(false)->default(1)->change();
            }
            if ($schema->hasColumn('products', 'quantity')) {
                $table->integer('quantity')->nullable(false)->default(0)->change();
            }
            if ($schema->hasColumn('products', 'price')) {
                $table->double('price', 8, 2)->nullable(false)->default(0)->change();
            }
        });
    }
};
