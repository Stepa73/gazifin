<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('id');
            $table->text('google_token')->nullable();
            $table->text('google_refresh_token')->nullable();
            $table->string('avatar')->nullable();
            $table->string('company_name')->nullable();
            $table->text('address')->nullable();
            $table->string('ico', 20)->nullable();
            $table->string('dic', 20)->nullable();
            $table->string('bank_account', 30)->nullable();
            $table->boolean('is_vat_payer')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'google_token',
                'google_refresh_token',
                'avatar',
                'company_name',
                'address',
                'ico',
                'dic',
                'bank_account',
                'is_vat_payer',
            ]);
        });
    }
};
