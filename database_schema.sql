-- ===========================================
-- CMU ARLO Alumni Management System Database
-- PostgreSQL Schema
-- ===========================================

-- Create database
CREATE DATABASE cmu_arlo_db;
\c cmu_arlo_db;

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ===========================================
-- USERS TABLE (Alumni/Students)
-- ===========================================
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(100),
    degree VARCHAR(200),
    year_graduated INTEGER,
    employed_after_grad VARCHAR(10) CHECK (employed_after_grad IN ('Yes', 'No')),
    industry VARCHAR(100),
    current_position VARCHAR(200),
    company VARCHAR(200),
    location VARCHAR(200),
    salary_range VARCHAR(50),
    linkedin_profile VARCHAR(500),
    skills TEXT,
    achievements TEXT,
    is_admin BOOLEAN DEFAULT FALSE,
    email_verified_at TIMESTAMP,
    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===========================================
-- JOBS TABLE
-- ===========================================
CREATE TABLE jobs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    title VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    description TEXT,
    requirements TEXT,
    location VARCHAR(255),
    type VARCHAR(50) CHECK (type IN ('Full-time', 'Part-time', 'Contract', 'Internship')),
    salary_min DECIMAL(10,2),
    salary_max DECIMAL(10,2),
    application_deadline DATE,
    contact_email VARCHAR(255),
    contact_phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    views_count INTEGER DEFAULT 0,
    applications_count INTEGER DEFAULT 0,
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===========================================
-- ANNOUNCEMENTS TABLE
-- ===========================================
CREATE TABLE announcements (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    type VARCHAR(50) CHECK (type IN ('Event', 'News', 'Update', 'Alert', 'Job')),
    priority VARCHAR(20) DEFAULT 'Medium' CHECK (priority IN ('Low', 'Medium', 'High', 'Urgent')),
    target_audience VARCHAR(100) DEFAULT 'All',
    image_url VARCHAR(500),
    attachment_url VARCHAR(500),
    event_date TIMESTAMP,
    event_location VARCHAR(255),
    is_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP,
    expires_at TIMESTAMP,
    views_count INTEGER DEFAULT 0,
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===========================================
-- JOB APPLICATIONS TABLE
-- ===========================================
CREATE TABLE job_applications (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    job_id UUID NOT NULL REFERENCES jobs(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    cover_letter TEXT,
    resume_url VARCHAR(500),
    status VARCHAR(50) DEFAULT 'Pending' CHECK (status IN ('Pending', 'Under Review', 'Interview', 'Accepted', 'Rejected')),
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP,
    reviewed_by UUID REFERENCES users(id),
    notes TEXT,
    UNIQUE(job_id, user_id)
);

-- ===========================================
-- ALUMNI TRACER RECORDS TABLE
-- ===========================================
CREATE TABLE tracer_records (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    tracer_date DATE NOT NULL,
    is_employed BOOLEAN,
    employment_type VARCHAR(50) CHECK (employment_type IN ('Full-time', 'Part-time', 'Self-employed', 'Unemployed')),
    current_position VARCHAR(200),
    current_company VARCHAR(200),
    monthly_income DECIMAL(10,2),
    industry VARCHAR(100),
    job_satisfaction INTEGER CHECK (job_satisfaction >= 1 AND job_satisfaction <= 5),
    skills_used TEXT,
    further_education BOOLEAN DEFAULT FALSE,
    education_level VARCHAR(100),
    challenges_faced TEXT,
    recommendations TEXT,
    contact_permission BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===========================================
-- FEEDBACK TABLE
-- ===========================================
CREATE TABLE feedback (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES users(id),
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) CHECK (type IN ('Bug Report', 'Feature Request', 'General Feedback', 'Support')),
    status VARCHAR(50) DEFAULT 'Open' CHECK (status IN ('Open', 'In Progress', 'Resolved', 'Closed')),
    priority VARCHAR(20) DEFAULT 'Medium' CHECK (priority IN ('Low', 'Medium', 'High')),
    assigned_to UUID REFERENCES users(id),
    resolved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===========================================
-- SYSTEM LOGS TABLE
-- ===========================================
CREATE TABLE system_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES users(id),
    action VARCHAR(255) NOT NULL,
    description TEXT,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===========================================
-- INDEXES FOR PERFORMANCE
-- ===========================================
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

-- ===========================================
-- TRIGGERS FOR UPDATED_AT
-- ===========================================
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