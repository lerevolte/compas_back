<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'weight')) {
                $table->integer('weight')->nullable()->default(null)->change();
            }
            if (Schema::hasColumn('products', 'quantity')) {
                $table->integer('quantity')->nullable()->default(null)->change();
            }
            if (Schema::hasColumn('products', 'price')) {
                $table->double('price', 8, 2)->nullable()->default(null)->change();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        if (Schema::hasColumn('products', 'weight')) {
            DB::table('products')->whereNull('weight')->update(['weight' => 1]);
        }
        if (Schema::hasColumn('products', 'quantity')) {
            DB::table('products')->whereNull('quantity')->update(['quantity' => 0]);
        }
        if (Schema::hasColumn('products', 'price')) {
            DB::table('products')->whereNull('price')->update(['price' => 0]);
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'weight')) {
                $table->integer('weight')->nullable(false)->default(1)->change();
            }
            if (Schema::hasColumn('products', 'quantity')) {
                $table->integer('quantity')->nullable(false)->default(0)->change();
            }
            if (Schema::hasColumn('products', 'price')) {
                $table->double('price', 8, 2)->nullable(false)->default(0)->change();
            }
        });
    }
};
