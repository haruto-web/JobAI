<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Job;
use App\Models\Application;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test users
        $jobseeker = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'jobseeker@example.com',
            'user_type' => 'jobseeker',
        ]);

        $employer = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'employer@example.com',
            'user_type' => 'employer',
        ]);

        // sample jobs
        $jobs = [
            [
                'title' => 'Senior Software Developer',
                'description' => 'We are looking for an experienced software developer to join our team.',
                'company' => 'Tech Corp',
                'location' => 'New York, NY',
                'type' => 'full-time',
                'salary' => 120000,
                'requirements' => ['PHP', 'Laravel', 'JavaScript', 'React'],
                'user_id' => $employer->id,
                'status' => 'pending_approval',
            ],
            [
                'title' => 'Frontend Developer',
                'description' => 'Join our team as a frontend developer working on modern web applications.',
                'company' => 'Web Solutions Inc',
                'location' => 'San Francisco, CA',
                'type' => 'full-time',
                'salary' => 95000,
                'requirements' => ['JavaScript', 'React', 'CSS', 'HTML'],
                'user_id' => $employer->id,
                'status' => 'pending_approval',
            ],
            [
                'title' => 'Backend Developer',
                'description' => 'Looking for a backend developer with experience in Laravel and API development.',
                'company' => 'Data Systems',
                'location' => 'Austin, TX',
                'type' => 'contract',
                'salary' => 80000,
                'requirements' => ['PHP', 'Laravel', 'MySQL', 'API'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Full Stack Developer',
                'description' => 'Full stack developer needed for exciting startup project.',
                'company' => 'StartupXYZ',
                'location' => 'Remote',
                'type' => 'part-time',
                'salary' => 60000,
                'requirements' => ['JavaScript', 'Node.js', 'React', 'MongoDB'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Online English Teacher',
                'description' => 'Teach English online to students worldwide. Flexible hours and competitive pay.',
                'company' => 'Global Education Hub',
                'location' => 'Remote',
                'type' => 'part-time',
                'salary' => 25000,
                'requirements' => ['TEFL Certification', 'English Proficiency', 'Teaching Experience'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'AI Assistant Developer',
                'description' => 'Develop AI-powered assistants for various applications using machine learning.',
                'company' => 'AI Innovations Ltd',
                'location' => 'Seattle, WA',
                'type' => 'full-time',
                'salary' => 130000,
                'requirements' => ['Python', 'Machine Learning', 'NLP', 'TensorFlow'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Mobile App Developer',
                'description' => 'Create innovative mobile applications for iOS and Android platforms.',
                'company' => 'MobileTech Solutions',
                'location' => 'Los Angeles, CA',
                'type' => 'full-time',
                'salary' => 110000,
                'requirements' => ['React Native', 'Flutter', 'iOS', 'Android'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Data Scientist',
                'description' => 'Analyze large datasets to extract insights and build predictive models.',
                'company' => 'Data Analytics Corp',
                'location' => 'Boston, MA',
                'type' => 'full-time',
                'salary' => 115000,
                'requirements' => ['Python', 'R', 'SQL', 'Statistics'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'UX/UI Designer',
                'description' => 'Design user-friendly interfaces and experiences for web and mobile applications.',
                'company' => 'Design Studio Pro',
                'location' => 'Portland, OR',
                'type' => 'contract',
                'salary' => 70000,
                'requirements' => ['Figma', 'Adobe XD', 'Prototyping', 'User Research'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'DevOps Engineer',
                'description' => 'Manage infrastructure and deployment pipelines for scalable applications.',
                'company' => 'Cloud Systems Inc',
                'location' => 'Denver, CO',
                'type' => 'full-time',
                'salary' => 105000,
                'requirements' => ['AWS', 'Docker', 'Kubernetes', 'CI/CD'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Online Math Tutor',
                'description' => 'Provide one-on-one math tutoring sessions online for students of all levels.',
                'company' => 'Math Masters Academy',
                'location' => 'Remote',
                'type' => 'part-time',
                'salary' => 30000,
                'requirements' => ['Mathematics Degree', 'Teaching Experience', 'Online Tutoring'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Blockchain Developer',
                'description' => 'Develop decentralized applications and smart contracts on blockchain platforms.',
                'company' => 'CryptoTech Ventures',
                'location' => 'Miami, FL',
                'type' => 'full-time',
                'salary' => 125000,
                'requirements' => ['Solidity', 'Ethereum', 'Web3.js', 'Cryptography'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Content Writer',
                'description' => 'Create engaging content for websites, blogs, and marketing materials.',
                'company' => 'Content Creators Co',
                'location' => 'Chicago, IL',
                'type' => 'freelance',
                'salary' => 45000,
                'requirements' => ['Writing Skills', 'SEO Knowledge', 'Creativity'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Cybersecurity Analyst',
                'description' => 'Protect systems and networks from cyber threats and vulnerabilities.',
                'company' => 'SecureNet Solutions',
                'location' => 'Washington, DC',
                'type' => 'full-time',
                'salary' => 95000,
                'requirements' => ['Network Security', 'Ethical Hacking', 'Risk Assessment'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Online Fitness Coach',
                'description' => 'Guide clients through personalized fitness programs via online platforms.',
                'company' => 'FitLife Online',
                'location' => 'Remote',
                'type' => 'part-time',
                'salary' => 35000,
                'requirements' => ['Fitness Certification', 'Nutrition Knowledge', 'Motivation Skills'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Game Developer',
                'description' => 'Create engaging video games for various platforms and audiences.',
                'company' => 'GameStudio Interactive',
                'location' => 'Austin, TX',
                'type' => 'full-time',
                'salary' => 90000,
                'requirements' => ['Unity', 'C#', 'Game Design', '3D Modeling'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Digital Marketing Specialist',
                'description' => 'Manage online marketing campaigns and social media strategies.',
                'company' => 'MarketMasters Agency',
                'location' => 'New York, NY',
                'type' => 'full-time',
                'salary' => 65000,
                'requirements' => ['Google Ads', 'Social Media', 'Analytics', 'SEO'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Online Language Tutor',
                'description' => 'Teach various languages online to learners around the world.',
                'company' => 'LinguaConnect',
                'location' => 'Remote',
                'type' => 'part-time',
                'salary' => 28000,
                'requirements' => ['Language Proficiency', 'Teaching Experience', 'Cultural Knowledge'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Machine Learning Engineer',
                'description' => 'Build and deploy machine learning models for data-driven applications.',
                'company' => 'ML Solutions Ltd',
                'location' => 'San Francisco, CA',
                'type' => 'full-time',
                'salary' => 140000,
                'requirements' => ['Python', 'Scikit-learn', 'Deep Learning', 'MLOps'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Virtual Reality Developer',
                'description' => 'Create immersive VR experiences and applications.',
                'company' => 'VR Innovations',
                'location' => 'Los Angeles, CA',
                'type' => 'full-time',
                'salary' => 100000,
                'requirements' => ['Unity', 'Unreal Engine', 'C#', '3D Development'],
                'user_id' => $employer->id,
            ],
            [
                'title' => 'Online Business Consultant',
                'description' => 'Provide strategic advice to businesses through virtual consultations.',
                'company' => 'BizConsult Online',
                'location' => 'Remote',
                'type' => 'contract',
                'salary' => 75000,
                'requirements' => ['Business Strategy', 'Consulting Experience', 'Communication Skills'],
                'user_id' => $employer->id,
            ],
        ];

        foreach ($jobs as $jobData) {
            Job::create(array_merge($jobData, ['status' => 'pending_approval']));
        }

        // Create sample applications for the jobseeker
        $sampleJobs = Job::take(2)->get(); // Get first 2 jobs
        foreach ($sampleJobs as $job) {
            Application::create([
                'user_id' => $jobseeker->id,
                'job_id' => $job->id,
                'status' => 'pending',
            ]);
        }

        // Create one accepted application
        Application::create([
            'user_id' => $jobseeker->id,
            'job_id' => $sampleJobs->last()->id,
            'status' => 'accepted',
        ]);

        // Create admin user
        User::updateOrCreate(
            ['email' => 'venandrewmirasol@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin12345'),
                'user_type' => 'admin',
            ]
        );
    }
}
