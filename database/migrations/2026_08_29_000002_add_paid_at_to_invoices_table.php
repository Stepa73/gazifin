<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('paid_at')->nullable()->after('status');
        });

        // U už uhrazených faktur datum úhrady neznáme — nejbližší dostupný odhad je splatnost.
        DB::table('invoices')
            ->where('status', 'paid')
            ->whereNull('paid_at')
            ->update(['paid_at' => DB::raw('due_date')]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('paid_at');
        });
    }
};
