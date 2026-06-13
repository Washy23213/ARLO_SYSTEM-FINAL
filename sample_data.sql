-- ===========================================
-- SAMPLE DATA FOR CMU ARLO SYSTEM
-- Aligned with database_schema.sql
-- ===========================================

BEGIN;

-- Reusable bcrypt hash for the password "password"
-- (Laravel default sample hash)
-- Use real user-specific hashes in production.

-- ===========================================
-- USERS (super admin + admin + alumni records)
-- ===========================================
INSERT INTO users (
    firstname, lastname, email, password, phone, department, degree,
    year_graduated, employed_after_grad, industry, current_position, company,
    location, salary_range, skills, achievements, is_admin, role, email_verified_at
) VALUES
(
    'Arlo', 'Super Admin', 'admin@cmu.edu.ph',
    '$2y$12$ag28.N8/O66GYTThsuipg.CdoesNkAHiG3Kh5EasdyiWQuRlMleCO',
    '09170000001', 'Alumni Office', 'N/A', 2018, 'Yes', 'Education',
    'System Administrator', 'Central Mindanao University', 'Musuan, Bukidnon',
    NULL, 'System administration', 'Managed alumni portal launch', TRUE, 'super_admin', NOW()
),
(
    'OARL', 'Admin', 'oarl.admin@cmu.edu.ph',
    '$2y$12$ag28.N8/O66GYTThsuipg.CdoesNkAHiG3Kh5EasdyiWQuRlMleCO',
    '09170000009', 'Alumni Office', 'N/A', NULL, NULL, 'Education',
    'Alumni Records Administrator', 'Central Mindanao University', 'Musuan, Bukidnon',
    NULL, 'Records management', 'Maintains alumni records and announcements', TRUE, 'admin', NOW()
),
(
    'John Paul', 'Mabuhay', 'john.mabuhay@cmu.edu.ph',
    '$2y$12$ag28.N8/O66GYTThsuipg.CdoesNkAHiG3Kh5EasdyiWQuRlMleCO',
    '09170000002', 'College of Information Sciences and Computing',
    'BS Information Technology', 2023, 'Yes', 'Technology',
    'Junior Software Developer', 'Bukidnon Tech Solutions',
    'Malaybalay City, Bukidnon', '25,000.00 - 35,000.00',
    'PHP, Laravel, PostgreSQL', 'Dean''s Lister 2022', FALSE, 'alumni', NOW()
),
(
    'Maria Luisa', 'Caballero', 'maria.caballero@cmu.edu.ph',
    '$2y$12$ag28.N8/O66GYTThsuipg.CdoesNkAHiG3Kh5EasdyiWQuRlMleCO',
    '09170000003', 'College of Business and Management',
    'BS Business Administration', 2022, 'Yes', 'Banking',
    'Account Officer', 'Land Bank of the Philippines',
    'Cagayan de Oro City', '35,000.00 - 45,000.00',
    'Client management, Excel', 'Best in Internship Award', FALSE, 'alumni', NOW()
),
(
    'Rico', 'Salvador', 'rico.salvador@cmu.edu.ph',
    '$2y$12$ag28.N8/O66GYTThsuipg.CdoesNkAHiG3Kh5EasdyiWQuRlMleCO',
    '09170000004', 'College of Engineering',
    'BS Civil Engineering', 2021, 'Yes', 'Construction',
    'Site Engineer', 'Mindanao Infra Builders',
    'Valencia City, Bukidnon', '45,000.00 - 55,000.00',
    'AutoCAD, Project planning', 'Passed CE board exam 2021', FALSE, 'alumni', NOW()
),
(
    'Anne', 'Dizon', 'anne.dizon@cmu.edu.ph',
    '$2y$12$ag28.N8/O66GYTThsuipg.CdoesNkAHiG3Kh5EasdyiWQuRlMleCO',
    '09170000005', 'College of Human Ecology',
    'BS Nutrition and Dietetics', 2024, 'No', NULL,
    'Job Seeker', NULL, 'Manolo Fortich, Bukidnon', NULL,
    'Nutrition planning, Community work', 'Outstanding practicum performance', FALSE, 'alumni', NOW()
),
(
    'Kevin', 'Alonzo', 'kevin.alonzo@cmu.edu.ph',
    '$2y$12$ag28.N8/O66GYTThsuipg.CdoesNkAHiG3Kh5EasdyiWQuRlMleCO',
    '09170000006', 'College of Agriculture',
    'BS Agriculture', 2020, 'Yes', 'Agribusiness',
    'Field Operations Supervisor', 'AgriGrow Mindanao',
    'Maramag, Bukidnon', '35,000.00 - 45,000.00',
    'Crop planning, Team leadership', 'Regional youth agri awardee', FALSE, 'alumni', NOW()
),
(
    'Jessa', 'Neri', 'jessa.neri@cmu.edu.ph',
    '$2y$12$ag28.N8/O66GYTThsuipg.CdoesNkAHiG3Kh5EasdyiWQuRlMleCO',
    '09170000007', 'College of Education',
    'BSEd Major in English', 2023, 'Yes', 'Education',
    'English Teacher', 'Bukidnon National High School',
    'Malaybalay City, Bukidnon', '25,000.00 - 35,000.00',
    'Classroom management, Writing', 'LET passer 2024', FALSE, 'alumni', NOW()
),
(
    'Paolo', 'Ventura', 'paolo.ventura@cmu.edu.ph',
    '$2y$12$ag28.N8/O66GYTThsuipg.CdoesNkAHiG3Kh5EasdyiWQuRlMleCO',
    '09170000008', 'College of Forestry and Environmental Science',
    'BS Forestry', 2022, 'Yes', 'Environment',
    'Forester I', 'DENR Region X',
    'Cagayan de Oro City', '25,000.00 - 35,000.00',
    'GIS mapping, Field surveys', 'Campus environmental leadership award', FALSE, 'alumni', NOW()
)
ON CONFLICT (email) DO NOTHING;

-- ===========================================
-- JOBS
-- ===========================================
INSERT INTO jobs (
    title, company, description, requirements, location, type,
    salary_range, application_deadline, contact_email, contact_phone,
    is_active, created_at, updated_at
) VALUES
(
    'Laravel Developer', 'Bukidnon Tech Solutions',
    'Develop and maintain web modules for alumni and student services.',
    'BSIT/BSCS graduate, Laravel experience, SQL fundamentals.',
    'Malaybalay City, Bukidnon', 'Full-time',
    '25,000.00 - 34,999.00', '2026-04-30', 'hr@bktech.ph', '0888130011',
    TRUE,
    NOW() - INTERVAL '6 days', NOW() - INTERVAL '6 days'
),
(
    'Junior Agribusiness Analyst', 'AgriGrow Mindanao',
    'Support farm operations reporting and value-chain analysis.',
    'Agriculture or related degree, Excel skills, strong communication.',
    'Maramag, Bukidnon', 'Full-time',
    '25,000.00 - 34,999.00', '2026-05-15', 'careers@agrigrow.ph', '0888130022',
    TRUE,
    NOW() - INTERVAL '3 days', NOW() - INTERVAL '3 days'
),
(
    'Science Teacher', 'Bukidnon National High School',
    'Teach science subjects for junior and senior high school.',
    'Education graduate, LET passer preferred.',
    'Malaybalay City, Bukidnon', 'Part-time',
    '15,000.00 - 24,999.00', '2026-04-20', 'principal@bnhs.edu.ph', '0888130033',
    TRUE,
    NOW() - INTERVAL '9 days', NOW() - INTERVAL '9 days'
)
ON CONFLICT DO NOTHING;

-- ===========================================
-- ANNOUNCEMENTS
-- ===========================================
INSERT INTO announcements (
    title, content, type, priority, target_audience, is_published, published_at,
    event_date, event_location, views_count, created_at, updated_at
) VALUES
(
    'CMU Alumni Homecoming 2026',
    'All CMU alumni are invited to the annual homecoming and networking day.',
    'Event', 'High', 'All', TRUE, NOW() - INTERVAL '2 days',
    NOW() + INTERVAL '20 days', 'CMU University Gym, Musuan, Bukidnon',
    143,
    NOW() - INTERVAL '2 days', NOW() - INTERVAL '2 days'
),
(
    'New Job Board Filters Released',
    'You can now filter job posts by location, salary range, and employment type.',
    'Update', 'Medium', 'All', TRUE, NOW() - INTERVAL '5 days',
    NULL, NULL, 97,
    NOW() - INTERVAL '5 days', NOW() - INTERVAL '5 days'
),
(
    'Graduate Tracer Form Reminder',
    'Please complete the tracer form to help CMU improve programs and employment support.',
    'News', 'Medium', 'Alumni', TRUE, NOW() - INTERVAL '1 day',
    NULL, NULL, 63,
    NOW() - INTERVAL '1 day', NOW() - INTERVAL '1 day'
)
ON CONFLICT DO NOTHING;

-- ===========================================
-- JOB SUBMISSIONS
-- ===========================================
INSERT INTO job_submissions (
    user_id, title, company, type, location, contact_email,
    application_deadline, description, requirements, status, admin_notes,
    created_at, updated_at
) VALUES
(
    (SELECT id FROM users WHERE email = 'john.mabuhay@cmu.edu.ph'),
    'Backend Developer', 'Bukidnon Tech Solutions', 'Full-time',
    'Malaybalay City, Bukidnon', 'hr@bktech.ph', '2026-05-30',
    'Suggested opening for Laravel and API development work.',
    'BSIT/BSCS graduate, Laravel experience, SQL fundamentals.',
    'approved', 'Approved as relevant to CMU alumni.',
    NOW() - INTERVAL '4 days', NOW() - INTERVAL '3 days'
),
(
    (SELECT id FROM users WHERE email = 'kevin.alonzo@cmu.edu.ph'),
    'Agribusiness Data Assistant', 'AgriGrow Mindanao', 'Full-time',
    'Maramag, Bukidnon', 'careers@agrigrow.ph', '2026-06-15',
    'Alumni-submitted opportunity for agricultural operations reporting.',
    'Agriculture or related degree, Excel skills, strong communication.',
    'pending', NULL,
    NOW() - INTERVAL '2 days', NOW() - INTERVAL '2 days'
),
(
    (SELECT id FROM users WHERE email = 'jessa.neri@cmu.edu.ph'),
    'Volunteer Science Tutor', 'Bukidnon Learning Center', 'Part-time',
    'Malaybalay City, Bukidnon', 'programs@blc.edu.ph', '2026-05-20',
    'Submitted community tutoring opportunity for education graduates.',
    'Education graduate or LET passer preferred.',
    'rejected', 'Rejected because the posting did not include enough compensation details.',
    NOW() - INTERVAL '5 days', NOW() - INTERVAL '4 days'
)
ON CONFLICT DO NOTHING;

-- ===========================================
-- TRACER RECORDS
-- ===========================================
INSERT INTO tracer_records (
    user_id, tracer_date, is_employed, employment_type, current_position,
    current_company, monthly_income, industry, job_satisfaction, skills_used,
    further_education, education_level, challenges_faced, recommendations,
    contact_permission
) VALUES
(
    (SELECT id FROM users WHERE email = 'john.mabuhay@cmu.edu.ph'),
    '2025-11-15', TRUE, 'Full-time', 'Junior Software Developer',
    'Bukidnon Tech Solutions', 'P30,000.00 to less than P40,000.00', 'Technology', 4,
    'Laravel, SQL, debugging, teamwork', FALSE, NULL,
    'Adjusting to project deadlines', 'Offer more backend API electives', TRUE
),
(
    (SELECT id FROM users WHERE email = 'maria.caballero@cmu.edu.ph'),
    '2025-11-15', TRUE, 'Full-time', 'Account Officer',
    'Land Bank of the Philippines', 'P40,000.00 to less than P50,000.00', 'Banking', 4,
    'Reporting, account management, communication', FALSE, NULL,
    'High workload during reporting periods', 'Add finance software workshops', TRUE
),
(
    (SELECT id FROM users WHERE email = 'anne.dizon@cmu.edu.ph'),
    '2025-11-15', FALSE, 'Unemployed', 'Job Seeker',
    NULL, NULL, NULL, NULL,
    'Nutrition planning, community outreach', TRUE, 'Certificate Program',
    'Limited entry-level openings nearby', 'Strengthen internship-to-hire partnerships', TRUE
)
ON CONFLICT (user_id, tracer_date) DO NOTHING;

-- ===========================================
-- FEEDBACK
-- ===========================================
INSERT INTO feedback (
    user_id, subject, message, type, status, priority
) VALUES
(
    (SELECT id FROM users WHERE email = 'john.mabuhay@cmu.edu.ph'),
    'Job search improvement',
    'Please add a filter for remote-friendly opportunities.',
    'Feature Request', 'Open', 'Medium'
),
(
    (SELECT id FROM users WHERE email = 'anne.dizon@cmu.edu.ph'),
    'Cannot upload resume',
    'Resume upload shows an error on mobile browser.',
    'Bug Report', 'In Progress', 'High'
),
(
    (SELECT id FROM users WHERE email = 'jessa.neri@cmu.edu.ph'),
    'Thanks for alumni event',
    'Networking with fellow graduates was very helpful.',
    'General Feedback', 'Resolved', 'Low'
)
ON CONFLICT DO NOTHING;

-- ===========================================
-- SYSTEM LOGS
-- ===========================================
INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES
(
    (SELECT id FROM users WHERE email = 'john.mabuhay@cmu.edu.ph'),
    'LOGIN', 'User logged in successfully.', '192.168.1.20',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
),
(
    (SELECT id FROM users WHERE email = 'admin@cmu.edu.ph'),
    'CREATE_JOB', 'Posted Laravel Developer opening.', '192.168.1.10',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
),
(
    (SELECT id FROM users WHERE email = 'maria.caballero@cmu.edu.ph'),
    'SUBMIT_TRACER', 'Submitted alumni tracer form.', '192.168.1.30',
    'Mozilla/5.0 (Linux; Android 13)'
);

COMMIT;
