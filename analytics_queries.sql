-- ===========================================
-- USEFUL ANALYTICS QUERIES FOR CMU ARLO SYSTEM
-- PostgreSQL Query Examples
-- ===========================================

-- ===========================================
-- BASIC STATISTICS QUERIES
-- ===========================================

-- Total alumni count
SELECT COUNT(*) as total_alumni FROM users WHERE is_admin = FALSE;

-- Alumni by department
SELECT department, COUNT(*) as count
FROM users
WHERE department IS NOT NULL AND is_admin = FALSE
GROUP BY department
ORDER BY count DESC;

-- Employment statistics
SELECT
    COUNT(*) as total_alumni,
    SUM(CASE WHEN employed_after_grad = 'Yes' THEN 1 ELSE 0 END) as employed,
    SUM(CASE WHEN employed_after_grad = 'No' THEN 1 ELSE 0 END) as unemployed,
    ROUND(
        (SUM(CASE WHEN employed_after_grad = 'Yes' THEN 1 ELSE 0 END)::decimal /
         NULLIF(COUNT(*), 0)) * 100, 2
    ) as employment_rate
FROM users
WHERE is_admin = FALSE;

-- ===========================================
-- EMPLOYMENT ANALYSIS QUERIES
-- ===========================================

-- Employment by industry
SELECT
    industry,
    COUNT(*) as count,
    ROUND((COUNT(*)::decimal / (SELECT COUNT(*) FROM users WHERE employed_after_grad = 'Yes' AND is_admin = FALSE)) * 100, 2) as percentage
FROM users
WHERE employed_after_grad = 'Yes' AND industry IS NOT NULL AND is_admin = FALSE
GROUP BY industry
ORDER BY count DESC;

-- Employment by graduation year
SELECT
    year_graduated,
    COUNT(*) as total_graduates,
    SUM(CASE WHEN employed_after_grad = 'Yes' THEN 1 ELSE 0 END) as employed,
    ROUND(
        (SUM(CASE WHEN employed_after_grad = 'Yes' THEN 1 ELSE 0 END)::decimal /
         NULLIF(COUNT(*), 0)) * 100, 2
    ) as employment_rate
FROM users
WHERE year_graduated IS NOT NULL AND is_admin = FALSE
GROUP BY year_graduated
ORDER BY year_graduated DESC;

-- Average salary by industry
SELECT
    industry,
    COUNT(*) as alumni_count,
    ROUND(AVG(CASE WHEN salary_range = '25,000-35,000' THEN 30000
                   WHEN salary_range = '35,000-45,000' THEN 40000
                   WHEN salary_range = '45,000-55,000' THEN 50000
                   WHEN salary_range = '55,000-65,000' THEN 60000
                   ELSE 0 END), 0) as avg_salary
FROM users
WHERE employed_after_grad = 'Yes' AND industry IS NOT NULL AND salary_range IS NOT NULL AND is_admin = FALSE
GROUP BY industry
ORDER BY avg_salary DESC;

-- ===========================================
-- JOB MARKET ANALYSIS
-- ===========================================

-- Most popular job types
SELECT type, COUNT(*) as count
FROM jobs
WHERE is_active = TRUE
GROUP BY type
ORDER BY count DESC;

-- Job applications by status
SELECT status, COUNT(*) as count
FROM job_applications
GROUP BY status
ORDER BY count DESC;

-- Most applied jobs
SELECT
    j.title,
    j.company,
    COUNT(ja.id) as applications_count,
    j.location,
    j.type
FROM jobs j
LEFT JOIN job_applications ja ON j.id = ja.job_id
WHERE j.is_active = TRUE
GROUP BY j.id, j.title, j.company, j.location, j.type
ORDER BY applications_count DESC
LIMIT 10;

-- ===========================================
-- ANNOUNCEMENT ENGAGEMENT
-- ===========================================

-- Announcement performance
SELECT
    type,
    COUNT(*) as total_announcements,
    AVG(views_count) as avg_views,
    MAX(views_count) as max_views,
    MIN(views_count) as min_views
FROM announcements
WHERE is_published = TRUE
GROUP BY type
ORDER BY avg_views DESC;

-- Recent announcements with engagement
SELECT
    title,
    type,
    views_count,
    created_at,
    published_at
FROM announcements
WHERE is_published = TRUE
ORDER BY created_at DESC
LIMIT 5;

-- ===========================================
-- TRACER STUDY ANALYSIS
-- ===========================================

-- Tracer response rate
SELECT
    COUNT(DISTINCT tr.user_id) as responded,
    (SELECT COUNT(*) FROM users WHERE is_admin = FALSE) as total_alumni,
    ROUND(
        (COUNT(DISTINCT tr.user_id)::decimal /
         (SELECT COUNT(*) FROM users WHERE is_admin = FALSE)) * 100, 2
    ) as response_rate
FROM tracer_records tr;

-- Job satisfaction analysis
SELECT
    job_satisfaction,
    COUNT(*) as count,
    ROUND((COUNT(*)::decimal / (SELECT COUNT(*) FROM tracer_records WHERE job_satisfaction IS NOT NULL)) * 100, 2) as percentage
FROM tracer_records
WHERE job_satisfaction IS NOT NULL
GROUP BY job_satisfaction
ORDER BY job_satisfaction;

-- Skills utilization
SELECT
    industry,
    COUNT(*) as alumni_count,
    AVG(job_satisfaction) as avg_satisfaction,
    ROUND(AVG(monthly_income), 2) as avg_income
FROM tracer_records
WHERE industry IS NOT NULL AND is_employed = TRUE
GROUP BY industry
ORDER BY avg_income DESC;

-- ===========================================
-- TIME-BASED ANALYTICS
-- ===========================================

-- Monthly user registrations
SELECT
    DATE_TRUNC('month', created_at) as month,
    COUNT(*) as registrations
FROM users
WHERE is_admin = FALSE
GROUP BY DATE_TRUNC('month', created_at)
ORDER BY month DESC;

-- Job postings by month
SELECT
    DATE_TRUNC('month', created_at) as month,
    COUNT(*) as jobs_posted
FROM jobs
GROUP BY DATE_TRUNC('month', created_at)
ORDER BY month DESC;

-- Announcement publishing trends
SELECT
    DATE_TRUNC('month', published_at) as month,
    COUNT(*) as announcements_published
FROM announcements
WHERE is_published = TRUE
GROUP BY DATE_TRUNC('month', published_at)
ORDER BY month DESC;

-- ===========================================
-- GEOGRAPHIC ANALYSIS
-- ===========================================

-- Alumni distribution by location
SELECT
    location,
    COUNT(*) as count,
    ROUND((COUNT(*)::decimal / (SELECT COUNT(*) FROM users WHERE location IS NOT NULL AND is_admin = FALSE)) * 100, 2) as percentage
FROM users
WHERE location IS NOT NULL AND is_admin = FALSE
GROUP BY location
ORDER BY count DESC;

-- Job opportunities by location
SELECT
    location,
    COUNT(*) as job_count,
    AVG(salary_min) as avg_min_salary,
    AVG(salary_max) as avg_max_salary
FROM jobs
WHERE is_active = TRUE AND location IS NOT NULL
GROUP BY location
ORDER BY job_count DESC;

-- ===========================================
-- SYSTEM USAGE ANALYTICS
-- ===========================================

-- User activity log summary
SELECT
    DATE_TRUNC('day', created_at) as date,
    COUNT(*) as total_activities,
    COUNT(DISTINCT user_id) as active_users
FROM system_logs
GROUP BY DATE_TRUNC('day', created_at)
ORDER BY date DESC;

-- Most active users
SELECT
    u.firstname,
    u.lastname,
    u.email,
    COUNT(sl.id) as activity_count
FROM users u
LEFT JOIN system_logs sl ON u.id = sl.user_id
WHERE u.is_admin = FALSE
GROUP BY u.id, u.firstname, u.lastname, u.email
ORDER BY activity_count DESC
LIMIT 10;

-- ===========================================
-- DASHBOARD SUMMARY QUERIES
-- ===========================================

-- Complete dashboard statistics
SELECT
    (SELECT COUNT(*) FROM users WHERE is_admin = FALSE) as total_alumni,
    (SELECT COUNT(*) FROM jobs WHERE is_active = TRUE) as active_jobs,
    (SELECT COUNT(*) FROM announcements WHERE is_published = TRUE) as published_announcements,
    (SELECT COUNT(*) FROM job_applications) as total_applications,
    (SELECT ROUND(AVG(views_count), 0) FROM announcements WHERE is_published = TRUE) as avg_announcement_views,
    (SELECT COUNT(*) FROM tracer_records) as tracer_responses;

-- Recent activities for dashboard
SELECT
    'New Alumni Registration' as activity_type,
    CONCAT(u.firstname, ' ', u.lastname) as description,
    u.created_at as activity_date
FROM users u
WHERE u.is_admin = FALSE
UNION ALL
SELECT
    'Job Posted' as activity_type,
    CONCAT(j.title, ' at ', j.company) as description,
    j.created_at as activity_date
FROM jobs j
UNION ALL
SELECT
    'Announcement Published' as activity_type,
    a.title as description,
    a.published_at as activity_date
FROM announcements a
WHERE a.is_published = TRUE
ORDER BY activity_date DESC
LIMIT 10;