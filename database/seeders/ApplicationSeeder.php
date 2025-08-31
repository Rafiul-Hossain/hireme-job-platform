<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    private $coverLetters = [
        "I am excited to apply for this position. With my experience and skills, I believe I would be a great fit for your team.",
        "I am writing to express my interest in this position. I have the necessary qualifications and experience to excel in this role.",
        "After reviewing the job description, I am confident that my skills and experience align well with your requirements.",
        "I am eager to bring my expertise to your company and contribute to your team's success.",
        "This position aligns perfectly with my career goals and I am excited about the opportunity to join your team."
    ];

    private $cvFilenames = [
        'cv_john_doe.pdf',
        'resume_jane_smith.docx',
        'cv_ahmed_khan.pdf',
        'resume_sarah_williams.docx',
        'cv_mohammed_ali.pdf'
    ];

    private $statuses = ['pending', 'pending', 'pending', 'shortlisted', 'rejected', 'hired'];

    public function run()
    {
        $jobSeekers = User::where('role', 'job_seeker')->get();
        $jobs = Job::where('status', 'active')->get();
        $employers = User::where('role', 'employer')->get();

        if ($jobs->isEmpty() || $jobSeekers->isEmpty()) {
            return;
        }

        foreach ($jobSeekers as $jobSeeker) {
            // Each job seeker applies to 1-5 jobs
            $applyCount = rand(1, min(5, $jobs->count()));
            $appliedJobIds = [];
            
            for ($i = 0; $i < $applyCount; $i++) {
                // Get a random job that hasn't been applied to yet by this user
                $availableJobs = $jobs->whereNotIn('id', $appliedJobIds);
                if ($availableJobs->isEmpty()) break;
                
                $job = $availableJobs->random();
                $appliedJobIds[] = $job->id;
                
                $status = $this->statuses[array_rand($this->statuses)];
                $reviewedBy = null;
                $reviewedAt = null;
                
                if ($status !== 'pending') {
                    $reviewedBy = $employers->random()->id;
                    $reviewedAt = now()->subDays(rand(1, 30));
                }
                
                Application::create([
                    'user_id' => $jobSeeker->id,
                    'job_id' => $job->id,
                    'cover_letter' => $this->coverLetters[array_rand($this->coverLetters)],
                    'cv_path' => 'cvs/' . $this->cvFilenames[array_rand($this->cvFilenames)],
                    'status' => $status,
                    'reviewed_by' => $reviewedBy,
                    'reviewed_at' => $reviewedAt,
                ]);
            }
        }
    }
}
