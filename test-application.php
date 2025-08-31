<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Set up the application instance
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use App\Models\Job;
use App\Models\User;
use App\Models\Application;

// Create a test job
$job = new Job([
    'title' => 'Test Job',
    'description' => 'Test Job Description',
    'requirements' => 'Test Requirements',
    'location' => 'Remote',
    'salary' => 50000,
    'type' => 'full-time',
    'status' => 'active',
    'category' => 'it',
    'company_id' => 1, // Assuming there's a company with ID 1
]);
$job->save();

// Create a test user
$user = new User([
    'name' => 'Test User',
    'email' => 'test' . Str::random(8) . '@example.com',
    'password' => bcrypt('password'),
    'role' => 'job_seeker',
]);
$user->save();

// Create a test CV file
$cvPath = storage_path('app/public/cvs/test_cv.pdf');
file_put_contents($cvPath, 'Test CV Content');

// Create a test file upload
$uploadedFile = new UploadedFile(
    $cvPath,
    'test_cv.pdf',
    'application/pdf',
    null,
    true // Mark as test
);

// Test the application submission
try {
    echo "Testing job application submission...\n";
    
    // Authenticate the user
    Auth::login($user);
    
    // Create the application directly
    $application = new Application([
        'user_id' => $user->id,
        'job_id' => $job->id,
        'cover_letter' => 'This is a test cover letter for the job application.',
        'status' => 'pending',
    ]);
    
    // Save the CV file
    $cvName = time() . '_' . $uploadedFile->getClientOriginalName();
    $uploadedFile->storeAs('public/cvs', $cvName);
    $application->cv_path = 'cvs/' . $cvName;
    
    $application->save();
    
    echo "Application submitted successfully!\n";
    echo "Application ID: " . $application->id . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (str_contains($e->getMessage(), 'SQLSTATE')) {
        echo "\nSQL Error Code: " . $e->getCode() . "\n";
    }
    
    // Print the full stack trace for debugging
    echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
} finally {
    // Clean up
    if (file_exists($cvPath)) {
        unlink($cvPath);
    }
    
    // Delete test data
    if (isset($application) && $application->exists) {
        $application->delete();
    }
    
    if (isset($job) && $job->exists) {
        $job->delete();
    }
    
    if (isset($user) && $user->exists) {
        $user->delete();
    }
}
