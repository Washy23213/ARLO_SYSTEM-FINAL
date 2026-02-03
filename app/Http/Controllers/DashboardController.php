<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // Assuming alumni data is in User model or create Alumni model

class DashboardController extends Controller
{
    public function index()
    {
        // Get alumni statistics
        $totalAlumni = User::count(); // Replace with actual alumni count
        $employed = 3300; // Replace with actual data
        $selfEmployed = 250;
        $unemployed = 292;

        // Get recent alumni records (replace with actual query)
        $recentAlumni = User::latest()->take(5)->get(); // Assuming User model has alumni data

        // Chart data - replace with actual database queries
        $employmentTrends = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'employed' => [3200, 3250, 3300, 3280, 3350, 3400, 3380, 3420, 3450, 3480, 3500, 3520],
            'selfEmployed' => [200, 220, 240, 230, 250, 260, 270, 280, 290, 300, 310, 320],
            'unemployed' => [442, 430, 410, 420, 400, 380, 390, 340, 320, 300, 280, 260]
        ];

        $industryData = [
            'labels' => ['Technology', 'Healthcare', 'Education', 'Finance', 'Manufacturing', 'Others'],
            'data' => [1200, 800, 600, 500, 400, 342]
        ];

        $tracedGraduates = [
            'labels' => ['2019', '2020', '2021', '2022', '2023', '2024'],
            'data' => [450, 520, 680, 750, 820, 890]
        ];

        $collegeTrends = [
            'labels' => ['Engineering', 'Business', 'Arts & Sciences', 'Education', 'Nursing'],
            'employed' => [450, 380, 320, 280, 250],
            'selfEmployed' => [50, 45, 35, 30, 25],
            'unemployed' => [20, 25, 30, 35, 40]
        ];

        // New chart data
        $employabilityData = [
            'labels' => ['2019', '2020', '2021', '2022', '2023', '2024'],
            'data' => [85, 82, 88, 90, 92, 94]
        ];

        $globalData = [
            'labels' => ['Philippines', 'USA', 'Canada', 'Australia', 'UK', 'Others'],
            'data' => [1200, 300, 150, 100, 80, 200]
        ];

        $feedbacksData = [
            'labels' => ['Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied', 'Very Dissatisfied'],
            'data' => [450, 380, 120, 50, 20]
        ];

        $reasonsData = [
            'labels' => ['Lack of Experience', 'No Job Opportunities', 'Family Reasons', 'Further Studies', 'Health Issues', 'Other'],
            'data' => [120, 95, 60, 45, 25, 30]
        ];

        return view('alumni-dashboard', compact(
            'totalAlumni',
            'employed',
            'selfEmployed',
            'unemployed',
            'recentAlumni',
            'employmentTrends',
            'industryData',
            'tracedGraduates',
            'collegeTrends',
            'employabilityData',
            'globalData',
            'feedbacksData',
            'reasonsData'
        ));
    }

    public function alumniRecords()
    {
        // Get all alumni records
        $alumni = User::all(); // Assuming User model contains alumni data

        return view('alumni-records', compact('alumni'));
    }

    public function jobs()
    {
        // Placeholder for jobs data
        $jobs = [
            [
                'id' => 1,
                'title' => 'Software Engineer',
                'company' => 'Tech Corp',
                'location' => 'Manila',
                'posted' => '2 days ago',
                'type' => 'Full-time',
                'description' => 'Join our innovative team to develop cutting-edge software solutions...',
                'tags' => ['PHP', 'Laravel', 'JavaScript', 'React']
            ],
            [
                'id' => 2,
                'title' => 'Marketing Specialist',
                'company' => 'Marketing Inc',
                'location' => 'Cebu',
                'posted' => '1 week ago',
                'type' => 'Full-time',
                'description' => 'Drive brand awareness and customer engagement through creative campaigns...',
                'tags' => ['Digital Marketing', 'Social Media', 'Content Creation']
            ],
            [
                'id' => 3,
                'title' => 'Data Analyst',
                'company' => 'Data Solutions',
                'location' => 'Davao',
                'posted' => '3 days ago',
                'type' => 'Full-time',
                'description' => 'Analyze complex datasets to provide actionable business insights...',
                'tags' => ['SQL', 'Python', 'Data Visualization', 'Analytics']
            ],
        ];

        return view('jobs', compact('jobs'));
    }

    public function settings()
    {
        return view('settings');
    }

    public function profile()
    {
        // Get current user data
        $user = auth()->user();

        return view('profile', compact('user'));
    }

    public function jobDetail($id)
    {
        // Dummy job data - in real app this would come from database
        $jobs = [
            [
                'id' => 1,
                'title' => 'Software Engineer',
                'company' => 'Tech Solutions Inc.',
                'location' => 'Makati City, Philippines',
                'type' => 'Full-time',
                'posted' => '2 days ago',
                'description' => 'We are looking for a skilled Software Engineer to join our dynamic team. You will be responsible for developing and maintaining high-quality software solutions.',
                'requirements' => [
                    'Bachelor\'s degree in Computer Science or related field',
                    '3+ years of experience in software development',
                    'Proficiency in PHP, JavaScript, and modern frameworks',
                    'Experience with Laravel and React',
                    'Strong problem-solving skills'
                ],
                'responsibilities' => [
                    'Develop and maintain web applications',
                    'Collaborate with cross-functional teams',
                    'Write clean, maintainable code',
                    'Participate in code reviews',
                    'Troubleshoot and debug applications'
                ],
                'benefits' => [
                    'Competitive salary package',
                    'Health insurance',
                    'Professional development opportunities',
                    'Flexible working hours',
                    'Modern office environment'
                ],
                'contact_email' => 'careers@techsolutions.com',
                'application_deadline' => 'March 15, 2026'
            ],
            [
                'id' => 2,
                'title' => 'Marketing Specialist',
                'company' => 'Global Marketing Corp',
                'location' => 'Cebu City, Philippines',
                'type' => 'Full-time',
                'posted' => '1 week ago',
                'description' => 'Join our marketing team to help drive brand awareness and customer engagement through innovative marketing campaigns.',
                'requirements' => [
                    'Bachelor\'s degree in Marketing, Communications, or related field',
                    '2+ years of marketing experience',
                    'Proficiency in social media platforms',
                    'Experience with Adobe Creative Suite',
                    'Strong communication and analytical skills'
                ],
                'responsibilities' => [
                    'Develop and execute marketing campaigns',
                    'Manage social media accounts',
                    'Create engaging content',
                    'Analyze campaign performance',
                    'Collaborate with design and sales teams'
                ],
                'benefits' => [
                    'Competitive salary',
                    'Performance bonuses',
                    'Creative work environment',
                    'Learning and development programs',
                    'Work-life balance initiatives'
                ],
                'contact_email' => 'hr@globalmarketing.com',
                'application_deadline' => 'March 20, 2026'
            ],
            [
                'id' => 3,
                'title' => 'Data Analyst',
                'company' => 'Data Insights Ltd.',
                'location' => 'Davao City, Philippines',
                'type' => 'Full-time',
                'posted' => '3 days ago',
                'description' => 'We are seeking a detail-oriented Data Analyst to help us make data-driven decisions and provide valuable insights.',
                'requirements' => [
                    'Bachelor\'s degree in Statistics, Mathematics, or related field',
                    'Experience with SQL and data visualization tools',
                    'Proficiency in Excel, Python, or R',
                    'Knowledge of statistical analysis methods',
                    'Strong attention to detail'
                ],
                'responsibilities' => [
                    'Analyze large datasets to identify trends',
                    'Create reports and dashboards',
                    'Collaborate with business teams',
                    'Present findings to stakeholders',
                    'Maintain data quality and integrity'
                ],
                'benefits' => [
                    'Competitive compensation',
                    'Health and wellness programs',
                    'Professional certification support',
                    'Remote work options',
                    'Career growth opportunities'
                ],
                'contact_email' => 'recruitment@datainsights.com',
                'application_deadline' => 'March 10, 2026'
            ]
        ];

        // Find the job by ID
        $job = collect($jobs)->firstWhere('id', $id);

        if (!$job) {
            abort(404, 'Job not found');
        }

        return view('job-detail', compact('job'));
    }

    public function announcementDetail($id)
    {
        // Dummy announcement data - mix of job-related and general announcements
        $announcements = [
            [
                'id' => 1,
                'title' => 'Software Engineer Position Available',
                'company' => 'Tech Company Inc.',
                'type' => 'job',
                'posted' => '2 days ago',
                'content' => 'We are excited to announce a new Software Engineer position in our growing development team. This role offers competitive compensation and opportunities for professional growth.',
                'details' => [
                    'Position: Senior Software Engineer',
                    'Location: Makati City, Philippines',
                    'Salary Range: ₱50,000 - ₱80,000',
                    'Requirements: 3+ years experience, PHP/Laravel expertise',
                    'Benefits: Health insurance, flexible hours, remote work options'
                ],
                'contact_info' => 'Apply at careers@techcompany.com',
                'deadline' => 'March 15, 2026'
            ],
            [
                'id' => 2,
                'title' => 'Alumni Networking Event',
                'company' => 'CMU Alumni Association',
                'type' => 'event',
                'posted' => '1 week ago',
                'content' => 'Join us for our annual Alumni Networking Event! Connect with fellow graduates, meet industry leaders, and explore new career opportunities.',
                'details' => [
                    'Date: March 20, 2026',
                    'Time: 6:00 PM - 9:00 PM',
                    'Venue: CMU Grand Ballroom',
                    'Dress Code: Business Casual',
                    'RSVP Required: alumni@cmu.edu.ph'
                ],
                'contact_info' => 'For more information: events@cmu.edu.ph',
                'deadline' => null
            ],
            [
                'id' => 3,
                'title' => 'Data Analyst Opening',
                'company' => 'Data Solutions Ltd.',
                'type' => 'job',
                'posted' => '3 days ago',
                'content' => 'New opportunity for experienced Data Analysts! Join our analytics team and help drive data-driven decision making across the organization.',
                'details' => [
                    'Position: Data Analyst',
                    'Location: Cebu City, Philippines',
                    'Salary Range: ₱35,000 - ₱55,000',
                    'Requirements: SQL, Python, Excel expertise',
                    'Benefits: Learning opportunities, modern tools, team collaboration'
                ],
                'contact_info' => 'Send resume to hr@datasolutions.com',
                'deadline' => 'March 10, 2026'
            ],
            [
                'id' => 4,
                'title' => 'CMU Career Fair 2026',
                'company' => 'CMU Career Services',
                'type' => 'event',
                'posted' => '5 days ago',
                'content' => 'The annual CMU Career Fair is back! Meet representatives from top companies, learn about internship and job opportunities, and network with industry professionals.',
                'details' => [
                    'Date: April 5, 2026',
                    'Time: 9:00 AM - 5:00 PM',
                    'Venue: CMU Gymnasium',
                    'Participating Companies: 50+ companies expected',
                    'Registration: Free for alumni, bring your resume!'
                ],
                'contact_info' => 'Register at careers@cmu.edu.ph',
                'deadline' => null
            ],
            [
                'id' => 5,
                'title' => 'Scholarship Opportunities',
                'company' => 'CMU Financial Aid Office',
                'type' => 'announcement',
                'posted' => '1 week ago',
                'content' => 'Several scholarship opportunities are now available for continuing education. Whether you\'re pursuing a master\'s degree or professional certification, explore these funding options.',
                'details' => [
                    'Available Scholarships:',
                    '- Academic Excellence Scholarship',
                    '- Leadership Scholarship',
                    '- Professional Development Grant',
                    '- Alumni Continuing Education Fund'
                ],
                'contact_info' => 'Contact finaid@cmu.edu.ph for applications',
                'deadline' => 'April 30, 2026'
            ]
        ];

        // Find the announcement by ID
        $announcement = collect($announcements)->firstWhere('id', $id);

        if (!$announcement) {
            abort(404, 'Announcement not found');
        }

        return view('announcement-detail', compact('announcement'));
    }
}