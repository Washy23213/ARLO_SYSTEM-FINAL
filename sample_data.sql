-- ===========================================
-- SAMPLE DATA FOR CMU ARLO SYSTEM
-- PostgreSQL Insert Queries
-- ===========================================

-- Insert sample users (alumni)
INSERT INTO users (firstname, lastname, email, password, department, degree, year_graduated, employed_after_grad, industry, current_position, company, location) VALUES
('Juan', 'Dela Cruz', 'juan.delacruz@cmu.edu.ph', '$2y$10$hashedpassword1', 'College of Engineering', 'Bachelor of Science in Computer Science', 2024, 'Yes', 'Technology', 'Software Engineer', 'Tech Solutions Inc.', 'Manila'),
('Maria', 'Santos', 'maria.santos@cmu.edu.ph', '$2y$10$hashedpassword2', 'College of Business', 'Bachelor of Science in Business Administration', 2023, 'Yes', 'Finance', 'Financial Analyst', 'BDO Unibank', 'Makati'),
('Pedro', 'Garcia', 'pedro.garcia@cmu.edu.ph', '$2y$10$hashedpassword3', 'College of Engineering', 'Bachelor of Science in Civil Engineering', 2022, 'Yes', 'Construction', 'Project Engineer', 'DMCI Homes', 'Quezon City'),
('Ana', 'Reyes', 'ana.reyes@cmu.edu.ph', '$2y$10$hashedpassword4', 'College of Arts & Sciences', 'Bachelor of Science in Psychology', 2024, 'No', NULL, 'Student', NULL, 'Baguio City'),
('Carlos', 'Mendoza', 'carlos.mendoza@cmu.edu.ph', '$2y$10$hashedpassword5', 'College of Business', 'Bachelor of Science in Accounting', 2021, 'Yes', 'Finance', 'Senior Accountant', 'SGV & Co.', 'Taguig'),
('Elena', 'Torres', 'elena.torres@cmu.edu.ph', '$2y$10$hashedpassword6', 'College of Nursing', 'Bachelor of Science in Nursing', 2023, 'Yes', 'Healthcare', 'Registered Nurse', 'Baguio General Hospital', 'Baguio City'),
('Miguel', 'Flores', 'miguel.flores@cmu.edu.ph', '$2y$10$hashedpassword7', 'College of Engineering', 'Bachelor of Science in Electrical Engineering', 2020, 'Yes', 'Technology', 'Electrical Engineer', 'Philippine Electrical Company', 'Manila'),
('Sofia', 'Castro', 'sofia.castro@cmu.edu.ph', '$2y$10$hashedpassword8', 'College of Education', 'Bachelor of Elementary Education', 2022, 'Yes', 'Education', 'Teacher', 'Baguio City National High School', 'Baguio City'),
('Diego', 'Villanueva', 'diego.villanueva@cmu.edu.ph', '$2y$10$hashedpassword9', 'College of Arts & Sciences', 'Bachelor of Arts in English', 2024, 'No', NULL, 'Freelance Writer', NULL, 'Cebu'),
('Isabella', 'Ramos', 'isabella.ramos@cmu.edu.ph', '$2y$10$hashedpassword10', 'College of Business', 'Bachelor of Science in Marketing', 2023, 'Yes', 'Marketing', 'Marketing Specialist', 'Unilever Philippines', 'Pasig');

-- Insert admin user
INSERT INTO users (firstname, lastname, email, password, is_admin) VALUES
('Admin', 'User', 'admin@cmu.edu.ph', '$2y$10$adminhashedpassword', TRUE);

-- Insert sample jobs
INSERT INTO jobs (title, company, description, requirements, location, type, salary_min, salary_max, contact_email, created_by) VALUES
('Software Developer', 'TechCorp Philippines', 'We are looking for a skilled software developer to join our growing team. You will be working on exciting projects using modern technologies.', '• Bachelor''s degree in Computer Science or related field
• 2+ years of experience in software development
• Proficiency in PHP, JavaScript, and SQL
• Experience with Laravel framework is a plus', 'Makati City', 'Full-time', 45000.00, 65000.00, 'hr@techcorp.ph', (SELECT id FROM users WHERE email = 'admin@cmu.edu.ph' LIMIT 1)),

('Marketing Coordinator', 'Global Brands Inc.', 'Join our marketing team to help promote our products and build brand awareness in the Philippine market.', '• Bachelor''s degree in Marketing, Business, or Communications
• 1-2 years of marketing experience
• Strong communication and creative skills
• Knowledge of social media marketing', 'Taguig City', 'Full-time', 30000.00, 45000.00, 'careers@globalbrands.ph', (SELECT id FROM users WHERE email = 'admin@cmu.edu.ph' LIMIT 1)),

('Civil Engineer', 'Infrastructure Solutions', 'We need experienced civil engineers for various infrastructure projects across the Philippines.', '• Licensed Civil Engineer
• 3+ years of experience in construction projects
• Knowledge of AutoCAD and engineering software
• Project management skills preferred', 'Quezon City', 'Full-time', 55000.00, 80000.00, 'engineering@infra.ph', (SELECT id FROM users WHERE email = 'admin@cmu.edu.ph' LIMIT 1)),

('Registered Nurse', 'Medical Center Baguio', 'Seeking compassionate and skilled nurses to join our healthcare team in providing quality patient care.', '• Bachelor of Science in Nursing
• PRC license
• 1+ years of clinical experience
• Excellent communication skills', 'Baguio City', 'Full-time', 25000.00, 35000.00, 'hr@medicalbaguio.ph', (SELECT id FROM users WHERE email = 'admin@cmu.edu.ph' LIMIT 1)),

('Data Analyst', 'Analytics Pro', 'Looking for a data analyst to help analyze business data and provide insights for decision making.', '• Bachelor''s degree in Statistics, Mathematics, or related field
• Experience with SQL and Excel
• Knowledge of data visualization tools
• Analytical mindset', 'Cebu City', 'Full-time', 35000.00, 50000.00, 'jobs@analyticspro.ph', (SELECT id FROM users WHERE email = 'admin@cmu.edu.ph' LIMIT 1));

-- Insert sample announcements
INSERT INTO announcements (title, content, type, priority, is_published, published_at, created_by) VALUES
('Alumni Homecoming 2026', 'Join us for the annual CMU Alumni Homecoming on March 15, 2026! This year''s theme is "Building Bridges, Creating Futures". We''ll have reunions, career talks, and special events throughout the weekend.

Activities include:
• Welcome dinner on Friday
• Career networking sessions
• Department reunions
• Sports tournaments
• Cultural performances

Don''t miss this opportunity to reconnect with old friends and make new connections!', 'Event', 'High', TRUE, CURRENT_TIMESTAMP, (SELECT id FROM users WHERE email = 'admin@cmu.edu.ph' LIMIT 1)),

('New Job Portal Features', 'We''ve enhanced our job portal with new features to better serve our alumni community:

• Advanced job search filters
• Resume builder tool
• Application tracking system
• Company reviews and ratings
• Salary insights

These improvements will help you find better career opportunities and make informed decisions about your professional development.', 'Update', 'Medium', TRUE, CURRENT_TIMESTAMP - INTERVAL '2 days', (SELECT id FROM users WHERE email = 'admin@cmu.edu.ph' LIMIT 1)),

('Scholarship Opportunities for Graduate Studies', 'Several scholarship opportunities are now available for CMU alumni pursuing graduate studies:

1. CMU Alumni Scholarship Fund - Up to ₱50,000 for master''s programs
2. Academic Excellence Grant - For graduates with GPA 3.5 and above
3. Professional Development Scholarship - For career advancement courses

Application deadline: April 30, 2026
Contact the Alumni Office for more information.', 'Announcement', 'Medium', TRUE, CURRENT_TIMESTAMP - INTERVAL '5 days', (SELECT id FROM users WHERE email = 'admin@cmu.edu.ph' LIMIT 1)),

('Emergency: Campus Closure Notice', 'Due to the approaching typhoon, CMU campus will be closed starting tomorrow, February 3, 2026, at 12:00 PM until further notice.

All classes, events, and non-essential activities are suspended. Staff and students are advised to stay safe and follow local government directives.

We will provide updates through this portal and our social media channels.', 'Alert', 'Urgent', TRUE, CURRENT_TIMESTAMP - INTERVAL '1 day', (SELECT id FROM users WHERE email = 'admin@cmu.edu.ph' LIMIT 1));

-- Insert sample job applications
INSERT INTO job_applications (job_id, user_id, cover_letter, status) VALUES
((SELECT id FROM jobs WHERE title = 'Software Developer' LIMIT 1), (SELECT id FROM users WHERE email = 'juan.delacruz@cmu.edu.ph' LIMIT 1), 'I am excited to apply for the Software Developer position. With my background in Computer Science from CMU and experience in web development, I believe I would be a great fit for your team.', 'Under Review'),
((SELECT id FROM jobs WHERE title = 'Marketing Coordinator' LIMIT 1), (SELECT id FROM users WHERE email = 'maria.santos@cmu.edu.ph' LIMIT 1), 'I am interested in the Marketing Coordinator role as it aligns perfectly with my marketing degree and passion for brand development.', 'Pending'),
((SELECT id FROM jobs WHERE title = 'Civil Engineer' LIMIT 1), (SELECT id FROM users WHERE email = 'pedro.garcia@cmu.edu.ph' LIMIT 1), 'As a licensed Civil Engineer with experience in construction projects, I am eager to contribute to your infrastructure development initiatives.', 'Interview');

-- Insert sample tracer records
INSERT INTO tracer_records (user_id, tracer_date, is_employed, employment_type, current_position, current_company, monthly_income, industry, job_satisfaction, skills_used) VALUES
((SELECT id FROM users WHERE email = 'juan.delacruz@cmu.edu.ph' LIMIT 1), '2024-06-15', TRUE, 'Full-time', 'Software Engineer', 'Tech Solutions Inc.', 55000.00, 'Technology', 4, 'Programming, Problem Solving, Team Collaboration'),
((SELECT id FROM users WHERE email = 'maria.santos@cmu.edu.ph' LIMIT 1), '2024-05-20', TRUE, 'Full-time', 'Financial Analyst', 'BDO Unibank', 45000.00, 'Finance', 5, 'Financial Analysis, Excel, Communication'),
((SELECT id FROM users WHERE email = 'pedro.garcia@cmu.edu.ph' LIMIT 1), '2024-04-10', TRUE, 'Full-time', 'Project Engineer', 'DMCI Homes', 60000.00, 'Construction', 4, 'Project Management, AutoCAD, Engineering'),
((SELECT id FROM users WHERE email = 'ana.reyes@cmu.edu.ph' LIMIT 1), '2024-07-01', FALSE, 'Unemployed', 'Student', NULL, NULL, NULL, NULL, NULL),
((SELECT id FROM users WHERE email = 'carlos.mendoza@cmu.edu.ph' LIMIT 1), '2024-03-15', TRUE, 'Full-time', 'Senior Accountant', 'SGV & Co.', 52000.00, 'Finance', 4, 'Accounting, Auditing, Tax Planning');

-- Insert sample feedback
INSERT INTO feedback (user_id, subject, message, type, status) VALUES
((SELECT id FROM users WHERE email = 'juan.delacruz@cmu.edu.ph' LIMIT 1), 'Job Portal Enhancement', 'The new job search filters are very helpful. Could you add a feature to save job searches?', 'Feature Request', 'Open'),
((SELECT id FROM users WHERE email = 'maria.santos@cmu.edu.ph' LIMIT 1), 'Profile Update Issue', 'I''m having trouble updating my profile picture. The upload keeps failing.', 'Bug Report', 'In Progress'),
((SELECT id FROM users WHERE email = 'pedro.garcia@cmu.edu.ph' LIMIT 1), 'Great Alumni Network', 'Thank you for organizing the recent networking event. It was very beneficial for career development.', 'General Feedback', 'Resolved');