<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('income_plans', function (Blueprint $table) {
            // Paušální daň se platí za každý měsíc v režimu, ne vždy dvanáctkrát —
            // kdo začal v srpnu, platí pět záloh.
            $table->unsignedTinyInteger('pausal_months')->default(12)->after('activity');
        });
    }

    public function down(): void
    {
        Schema::table('income_plans', function (Blueprint $table) {
            $table->dropColumn('pausal_months');
        });
    }
};
