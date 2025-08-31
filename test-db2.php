<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Set up the application instance
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    // Test database connection
    echo "Testing database connection...\n";
    DB::connection()->getPdo();
    echo "Connected to database: " . DB::connection()->getDatabaseName() . "\n\n";
    
    // Check if applications table exists
    if (Schema::hasTable('applications')) {
        echo "Applications table exists. Structure:\n";
        $columns = Schema::getColumnListing('applications');
        print_r($columns);
    } else {
        echo "Applications table does not exist.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (str_contains($e->getMessage(), 'SQLSTATE')) {
        echo "\nSQL Error Code: " . $e->getCode() . "\n";
    }
}
