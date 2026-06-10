<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_groups')) {
            Schema::dropIfExists('user_groups');
        }
        if (Schema::hasTable('groups')) {
            Schema::dropIfExists('groups');
        }
    }

    public function down(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('group');
            $table->integer('fee')->default(0);
            $table->string('fee_description')->nullable();
            $table->decimal('reward_ratio', 5, 2)->default(1.0);
            $table->string('reward_description')->nullable();
            $table->timestamps();
        });

        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->integer('fee')->default(0);
            $table->boolean('guest')->default(false);
            $table->boolean('active')->default(false);
            $table->timestamps();
        });
    }
};
