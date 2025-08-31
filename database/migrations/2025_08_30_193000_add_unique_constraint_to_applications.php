<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Remove existing duplicates first (if any)
        $duplicates = DB::table('applications')
            ->select('user_id', 'job_id')
            ->groupBy('user_id', 'job_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            // Keep the most recent application
            $ids = DB::table('applications')
                ->where('user_id', $duplicate->user_id)
                ->where('job_id', $duplicate->job_id)
                ->orderBy('created_at', 'desc')
                ->skip(1)
                ->pluck('id');

            DB::table('applications')->whereIn('id', $ids)->delete();
        }

        // Add unique constraint
        Schema::table('applications', function (Blueprint $table) {
            $table->unique(['user_id', 'job_id']);
        });
    }

    public function down()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'job_id']);
        });
    }
};
