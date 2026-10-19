<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('regime', 10)->default('auto');
            $table->boolean('side_activity')->default(false);
            $table->string('activity', 3)->default('60');
            $table->string('exp_mode', 10)->default('pausal');
            $table->decimal('exp_real', 12, 2)->default(0);
            $table->decimal('carry_amount', 12, 2)->default(0);
            $table->unsignedTinyInteger('carry_month')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_plans');
    }
};
