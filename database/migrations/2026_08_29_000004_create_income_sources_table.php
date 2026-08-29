<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('income_plan_id')->constrained()->cascadeOnDelete();
            // Zdroj nemusí odpovídat evidovanému klientovi — pak se skutečnost nedoplňuje.
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('mode', 10)->default('rate');
            $table->decimal('rate', 12, 2)->default(0);
            $table->string('unit', 2)->default('h');
            $table->decimal('hours_per_day', 4, 1)->default(8);
            $table->unsignedTinyInteger('payment_lag')->default(2);
            $table->unsignedTinyInteger('pay_day')->default(15);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->decimal('fixed_amount', 12, 2)->default(0);
            $table->date('invoice_date')->nullable();
            $table->decimal('invoice_amount', 12, 2)->default(0);
            $table->json('vacation');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_sources');
    }
};
