# ARLO Entity Relationship Diagram (ERD)

This ERD presents the separated account design of the **A Web-Based Tracer and Employment Analytics System for Alumni Relation and Linkages Office** project. Alumni accounts, regular administrator accounts, and Super Admin accounts are modeled as separate entities.

```mermaid
erDiagram
    USERS {
        UUID id PK
        string firstname
        string lastname
        string email UK
        string phone
        string department
        string degree
        int year_graduated
        string employed_after_grad
        string industry
        string current_position
        string company
        boolean profile_visibility
    }

    ADMINS {
        UUID id PK
        string firstname
        string lastname
        string email UK
        string password
        string phone
        string role "assigned admin access"
        boolean is_active
        timestamp email_verified_at
    }

    SUPER_ADMINS {
        UUID id PK
        string firstname
        string lastname
        string email UK
        string password
        string phone
        timestamp email_verified_at
    }

    TRACER_RECORDS {
        UUID id PK
        UUID user_id FK
        date tracer_date
        boolean is_employed
        string employment_type
        string current_position
        string current_company
        string monthly_income
        string industry
        int job_satisfaction
    }

    JOBS {
        UUID id PK
        string title
        string company
        string description
        string requirements
        string location
        string type
        decimal salary_min
        decimal salary_max
        string salary_range
        date application_deadline
        string contact_email
        boolean is_active
        int views_count
        int applications_count
        UUID created_by FK
        UUID archived_by FK
    }

    JOB_APPLICATIONS {
        UUID id PK
        UUID job_id FK
        UUID user_id FK
        string cover_letter
        string resume_url
        string status
        timestamp applied_at
        timestamp reviewed_at
        UUID reviewed_by FK
    }

    JOB_SUBMISSIONS {
        bigint id PK
        UUID user_id FK
        string title
        string company
        string type
        string location
        string contact_email
        date application_deadline
        string description
        string requirements
        string status
        string admin_notes
        UUID reviewed_by FK
        timestamp reviewed_at
    }

    ANNOUNCEMENTS {
        UUID id PK
        string title
        string content
        string type
        string priority
        string target_audience
        string target_department
        int target_graduation_year
        boolean is_published
        timestamp published_at
        timestamp expires_at
        int views_count
        UUID created_by FK
        UUID archived_by FK
    }

    FEEDBACK {
        UUID id PK
        UUID user_id FK
        string subject
        string message
        string type
        string status
        string priority
        UUID assigned_to FK
        timestamp resolved_at
    }

    NOTIFICATIONS {
        UUID id PK
        string type
        UUID notifiable_id FK
        string notifiable_type
        text data
        timestamp read_at
    }

    SYSTEM_LOGS {
        UUID id PK
        UUID user_id FK
        UUID admin_id FK
        UUID super_admin_id FK
        string action
        string description
        string ip_address
        string user_agent
        timestamp created_at
    }

    SESSIONS {
        string id PK
        UUID user_id FK
        string ip_address
        int last_activity
    }

    USERS ||--o{ TRACER_RECORDS : has
    USERS ||--o{ JOB_APPLICATIONS : submits
    USERS ||--o{ JOB_SUBMISSIONS : proposes
    USERS ||--o{ FEEDBACK : submits
    USERS ||--o{ SYSTEM_LOGS : generates
    USERS ||--o{ SESSIONS : owns
    USERS ||--o{ NOTIFICATIONS : receives
    ADMINS ||--o{ JOB_APPLICATIONS : reviews
    ADMINS ||--o{ JOB_SUBMISSIONS : reviews
    ADMINS ||--o{ JOBS : creates
    ADMINS ||--o{ JOBS : archives
    ADMINS ||--o{ ANNOUNCEMENTS : creates
    ADMINS ||--o{ ANNOUNCEMENTS : archives
    ADMINS ||--o{ FEEDBACK : handles
    ADMINS ||--o{ SYSTEM_LOGS : generates
    SUPER_ADMINS ||--o{ ADMINS : manages_accounts
    SUPER_ADMINS ||--o{ USERS : edits_accounts
    SUPER_ADMINS ||--o{ SYSTEM_LOGS : generates

    JOBS ||--o{ JOB_APPLICATIONS : receives
```

## Main Relationship Notes

- One `USER` can have many `TRACER_RECORDS`.
- One `USER` can apply to many `JOBS` through `JOB_APPLICATIONS`.
- One `JOB` can receive many `JOB_APPLICATIONS`.
- One `USER` represents an alumni account and can submit many proposed job posts through `JOB_SUBMISSIONS`.
- One `ADMIN` represents a regular administrative account stored in the `admins` table. Its `role` field describes the assigned administrator access level.
- One `SUPER_ADMIN` represents a separate super administrator account stored in the `super_admins` table.
- One `SUPER_ADMIN` can create, update, delete, and assign access roles to many `ADMIN` accounts.
- One `SUPER_ADMIN` can edit `USER` account details when account correction or account control is required.
- Regular `ADMIN` accounts can manage alumni records, tracer-related records, job postings, announcements, feedback, reports, and audit logs according to granted access.
- One `USER` can receive many `NOTIFICATIONS`.
- One `USER` can submit many `FEEDBACK` records, while an `ADMIN` may be assigned to handle feedback.
- `SYSTEM_LOGS` and `SESSIONS` support monitoring and access tracking.

## Suggested Use In Paper

You can label this as:

**Figure X. Entity Relationship Diagram of the Proposed Web-Based Tracer and Employment Analytics System**
