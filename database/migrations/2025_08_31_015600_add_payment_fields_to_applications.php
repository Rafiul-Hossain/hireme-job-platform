<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('payment_reference')->nullable()->after('cv_path');
            $table->decimal('payment_amount', 10, 2)->nullable()->after('payment_reference');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_amount');
            $table->string('payment_method')->nullable()->after('payment_verified_at');
            $table->string('payment_status')->default('pending')->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'payment_reference',
                'payment_amount',
                'payment_verified_at',
                'payment_method',
                'payment_status'
            ]);
        });
    }
};
