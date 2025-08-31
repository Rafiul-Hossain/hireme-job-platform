<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    private $jobTitles = [
        'Senior Software Engineer',
        'Frontend Developer',
        'Backend Developer',
        'Full Stack Developer',
        'DevOps Engineer',
        'UI/UX Designer',
        'Project Manager',
        'Product Manager',
        'Data Scientist',
        'Mobile App Developer',
        'QA Engineer',
        'System Administrator',
        'Technical Writer',
        'Digital Marketing Specialist',
        'Sales Executive',
        'Customer Support Representative'
    ];

    private $categories = [
        'Software Development',
        'Web Development',
        'Mobile Development',
        'Design',
        'Marketing',
        'Sales',
        'Customer Service',
        'Data Science',
        'DevOps',
        'Project Management'
    ];

    private $locations = [
        'Dhaka',
        'Chittagong',
        'Sylhet',
        'Khulna',
        'Rajshahi',
        'Remote',
        'Anywhere in Bangladesh'
    ];

    public function run()
    {
        $employers = User::where('role', 'employer')->get();
        $jobTypes = ['full-time', 'part-time', 'contract', 'freelance'];
        $statuses = ['active', 'active', 'active', 'inactive', 'closed'];

        foreach ($employers as $employer) {
            $jobCount = rand(3, 10);
            
            for ($i = 0; $i < $jobCount; $i++) {
                $randomDays = rand(1, 60);
                $isFeatured = rand(1, 10) === 1; // 10% chance of being featured
                
                Job::create([
                    'company_id' => $employer->id,
                    'title' => $this->jobTitles[array_rand($this->jobTitles)],
                    'description' => $this->generateJobDescription(),
                    'requirements' => $this->generateRequirements(),
                    'salary' => rand(20000, 200000),
                    'location' => $this->locations[array_rand($this->locations)],
                    'type' => $jobTypes[array_rand($jobTypes)],
                    'category' => $this->categories[array_rand($this->categories)],
                    'status' => $statuses[array_rand($statuses)],
                    'closing_date' => now()->addDays($randomDays),
                    'is_featured' => $isFeatured,
                ]);
            }
        }
    }

    private function generateJobDescription()
    {
        $descriptions = [
            "We are looking for a skilled professional to join our team. The ideal candidate will be responsible for developing and implementing new features and functionality.",
            "Join our dynamic team and work on exciting projects. You'll be part of a collaborative environment where your skills will be valued.",
            "We're seeking a talented individual to help us build innovative solutions. You'll work with cutting-edge technologies and a talented team.",
            "This position offers the opportunity to work on challenging projects and make a real impact. We value creativity and innovation.",
            "Be part of a growing company with a great culture. We offer competitive benefits and opportunities for professional growth."
        ];

        return $descriptions[array_rand($descriptions)];
    }

    private function generateRequirements()
    {
        $requirements = [
            "Bachelor's degree in Computer Science or related field.",
            "Strong problem-solving skills and attention to detail.",
            "Excellent communication and teamwork abilities.",
            "Ability to work in a fast-paced environment.",
            "Experience with version control systems (e.g., Git).",
            "Knowledge of database design and management.",
            "Strong understanding of software development methodologies.",
            "Ability to learn new technologies quickly.",
            "Portfolio or examples of previous work (if applicable).",
            "Relevant certifications (a plus)."
        ];

        shuffle($requirements);
        return implode("\n", array_slice($requirements, 0, rand(4, 8)));
    }
}
