<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // First, update the applications table
        Schema::table('applications', function (Blueprint $table) {
            // Change the enum values to include 'pending_payment'
            $table->enum('status', ['pending_payment', 'pending', 'shortlisted', 'rejected', 'hired'])
                  ->default('pending_payment')
                  ->change();
        });
    }

    public function down()
    {
        // Revert back to original enum values
        Schema::table('applications', function (Blueprint $table) {
            $table->enum('status', ['pending', 'shortlisted', 'rejected', 'hired'])
                  ->default('pending')
                  ->change();
        });
    }
};
