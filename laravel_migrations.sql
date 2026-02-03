-- ===========================================
-- LARAVEL MIGRATIONS FOR POSTGRESQL
-- Equivalent to the schema above
-- ===========================================

-- Create users table migration
CREATE TABLE IF NOT EXISTS migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);

-- 2024_01_01_000000_create_users_table.php
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    department VARCHAR(100) NULL,
    degree VARCHAR(200) NULL,
    year_graduated INTEGER NULL,
    employed_after_grad VARCHAR(10) NULL CHECK (employed_after_grad IN ('Yes', 'No')),
    industry VARCHAR(100) NULL,
    current_position VARCHAR(200) NULL,
    company VARCHAR(200) NULL,
    location VARCHAR(200) NULL,
    salary_range VARCHAR(50) NULL,
    linkedin_profile VARCHAR(500) NULL,
    skills TEXT NULL,
    achievements TEXT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2024_01_01_000001_create_jobs_table.php
CREATE TABLE jobs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    title VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    description TEXT NULL,
    requirements TEXT NULL,
    location VARCHAR(255) NULL,
    type VARCHAR(50) NULL CHECK (type IN ('Full-time', 'Part-time', 'Contract', 'Internship')),
    salary_min DECIMAL(10,2) NULL,
    salary_max DECIMAL(10,2) NULL,
    application_deadline DATE NULL,
    contact_email VARCHAR(255) NULL,
    contact_phone VARCHAR(20) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    views_count INTEGER DEFAULT 0,
    applications_count INTEGER DEFAULT 0,
    created_by UUID NULL REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2024_01_01_000002_create_announcements_table.php
CREATE TABLE announcements (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    type VARCHAR(50) NULL CHECK (type IN ('Event', 'News', 'Update', 'Alert', 'Job')),
    priority VARCHAR(20) DEFAULT 'Medium' CHECK (priority IN ('Low', 'Medium', 'High', 'Urgent')),
    target_audience VARCHAR(100) DEFAULT 'All',
    image_url VARCHAR(500) NULL,
    attachment_url VARCHAR(500) NULL,
    event_date TIMESTAMP NULL,
    event_location VARCHAR(255) NULL,
    is_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    views_count INTEGER DEFAULT 0,
    created_by UUID NULL REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2024_01_01_000003_create_job_applications_table.php
CREATE TABLE job_applications (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    job_id UUID NOT NULL REFERENCES jobs(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    cover_letter TEXT NULL,
    resume_url VARCHAR(500) NULL,
    status VARCHAR(50) DEFAULT 'Pending' CHECK (status IN ('Pending', 'Under Review', 'Interview', 'Accepted', 'Rejected')),
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by UUID NULL REFERENCES users(id),
    notes TEXT NULL,
    UNIQUE(job_id, user_id)
);

-- 2024_01_01_000004_create_tracer_records_table.php
CREATE TABLE tracer_records (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    tracer_date DATE NOT NULL,
    is_employed BOOLEAN NULL,
    employment_type VARCHAR(50) NULL CHECK (employment_type IN ('Full-time', 'Part-time', 'Self-employed', 'Unemployed')),
    current_position VARCHAR(200) NULL,
    current_company VARCHAR(200) NULL,
    monthly_income DECIMAL(10,2) NULL,
    industry VARCHAR(100) NULL,
    job_satisfaction INTEGER NULL CHECK (job_satisfaction >= 1 AND job_satisfaction <= 5),
    skills_used TEXT NULL,
    further_education BOOLEAN DEFAULT FALSE,
    education_level VARCHAR(100) NULL,
    challenges_faced TEXT NULL,
    recommendations TEXT NULL,
    contact_permission BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2024_01_01_000005_create_feedback_table.php
CREATE TABLE feedback (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NULL REFERENCES users(id),
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NULL CHECK (type IN ('Bug Report', 'Feature Request', 'General Feedback', 'Support')),
    status VARCHAR(50) DEFAULT 'Open' CHECK (status IN ('Open', 'In Progress', 'Resolved', 'Closed')),
    priority VARCHAR(20) DEFAULT 'Medium' CHECK (priority IN ('Low', 'Medium', 'High')),
    assigned_to UUID NULL REFERENCES users(id),
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2024_01_01_000006_create_system_logs_table.php
CREATE TABLE system_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NULL REFERENCES users(id),
    action VARCHAR(255) NOT NULL,
    description TEXT NULL,
    ip_address INET NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_department ON users(department);
CREATE INDEX idx_users_year_graduated ON users(year_graduated);
CREATE INDEX idx_jobs_company ON jobs(company);
CREATE INDEX idx_jobs_type ON jobs(type);
CREATE INDEX idx_jobs_is_active ON jobs(is_active);
CREATE INDEX idx_announcements_type ON announcements(type);
CREATE INDEX idx_announcements_is_published ON announcements(is_published);
CREATE INDEX idx_job_applications_status ON job_applications(status);
CREATE INDEX idx_tracer_records_user_id ON tracer_records(user_id);
CREATE INDEX idx_tracer_records_tracer_date ON tracer_records(tracer_date);
CREATE INDEX idx_system_logs_user_id ON system_logs(user_id);
CREATE INDEX idx_system_logs_created_at ON system_logs(created_at);

-- Create triggers for updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_jobs_updated_at BEFORE UPDATE ON jobs FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_announcements_updated_at BEFORE UPDATE ON announcements FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_tracer_records_updated_at BEFORE UPDATE ON tracer_records FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_feedback_updated_at BEFORE UPDATE ON feedback FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();