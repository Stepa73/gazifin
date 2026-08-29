<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('invoice_number_prefix', 20)->nullable()->after('phone');
            $table->string('invoice_number_suffix', 20)->nullable()->after('invoice_number_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['invoice_number_prefix', 'invoice_number_suffix']);
        });
    }
};
