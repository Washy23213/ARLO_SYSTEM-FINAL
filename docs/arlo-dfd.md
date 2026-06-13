# ARLO Data Flow Diagram (DFD)

This DFD is aligned with the workflow of the **A Web-Based Tracer and Employment Analytics System for Alumni Relation and Linkages Office**.

## Context Diagram (Level 0)

```mermaid
flowchart LR
    Alumni[Alumni]
    Admin[ARLO Admin]
    SuperAdmin[Super Admin]
    UniversityAdmins[University Administrators]
    System((0\nCMU ARLO Web-Based Tracer\nand Employment Analytics System))

    Alumni -->|registration, profile,\ntracer and feedback data| System
    System -->|announcements,\nnotifications and job posts| Alumni

    Admin -->|alumni, job, announcement\nand review updates| System
    System -->|alumni records, analytics,\nreports and feedback logs| Admin

    SuperAdmin -->|admin account, role\nand security requests| System
    System -->|account, role\nand audit reports| SuperAdmin

    UniversityAdmins -->|monitoring and\nreport requests| System
    System -->|employment analytics\nand summary reports| UniversityAdmins
```

## Level 1 DFD

```mermaid
flowchart LR
    Alumni[Alumni]
    Admin[Administrator]
    SuperAdmin[Super Admin]

    P1((1.0 Authentication\nand Account Management))
    P2((2.0 Alumni Records\nand Tracer Management))
    P3((3.0 Jobs and Job Submissions\nManagement))
    P4((4.0 Announcements\nand Notifications))
    P5((5.0 Analytics\nand Reporting))
    P6((6.0 Feedback\nand Audit Logging))
    P7((7.0 Admin Role\nManagement))

    D1[(D1 Users)]
    D2[(D2 Tracer Records)]
    D3[(D3 Jobs)]
    D4[(D4 Job Submissions)]
    D5[(D5 Announcements)]
    D6[(D6 Notifications)]
    D7[(D7 Feedback)]
    D8[(D8 System Logs / Sessions)]
    D9[(D9 Admins)]
    D10[(D10 Super Admins)]

    Alumni -->|credentials, password reset,\nprivacy/session settings| P1
    Admin -->|credentials, admin access| P1
    SuperAdmin -->|credentials, super admin access| P1
    P1 <--> D1
    P1 <--> D9
    P1 <--> D10
    P1 <--> D8
    P1 -->|authenticated access| Alumni
    P1 -->|authenticated access| Admin
    P1 -->|authenticated access| SuperAdmin

    Alumni -->|profile updates,\ntracer and employment data| P2
    Admin -->|alumni maintenance,\nrecord updates| P2
    P2 <--> D1
    P2 <--> D2
    P2 -->|updated profile,\ntracer status| Alumni
    P2 -->|alumni records| Admin

    Alumni -->|job posting submissions| P3
    Admin -->|job posting, review,\napproval, rejection| P3
    P3 <--> D3
    P3 <--> D4
    P3 -->|job listings,\nsubmission status| Alumni
    P3 -->|job management data| Admin

    Admin -->|publish announcements,\nset target audience| P4
    P4 <--> D5
    P4 <--> D6
    P4 -->|announcements,\npersonal notifications| Alumni
    P4 -->|delivery status,\npublished updates| Admin

    Admin -->|analytics requests,\nreport filters, export actions| P5
    P5 <--> D1
    P5 <--> D2
    P5 <--> D3
    P5 <--> D4
    P5 <--> D5
    P5 -->|analytics dashboards,\nreports, CSV/PDF exports| Admin

    Alumni -->|feedback submissions| P6
    Admin -->|feedback monitoring,\nstatus updates, audit review| P6
    P6 <--> D7
    P6 <--> D8
    P6 -->|submission confirmation| Alumni
    P6 -->|feedback queue,\nresolution tracking, audit trail| Admin

    SuperAdmin -->|create, view, update,\ndelete admin accounts,\nedit alumni accounts| P7
    P7 <--> D1
    P7 <--> D9
    P7 <--> D10
    P7 <--> D8
    P7 -->|admin account list,\nalumni account details,\nrole status, confirmation| SuperAdmin
```

## Suggested Paper Labels

- **Figure X. Context Diagram of the Proposed System**
- **Figure Y. Level 1 Data Flow Diagram of the Proposed System**

## Short Explanation For Your Paper

The context diagram shows the interaction of the main external actors, namely the alumni, ARLO admin, super admin, and university administrators, with the proposed web-based tracer and employment analytics system. The system receives inputs such as registration details, profile updates, tracer information, feedback, job posting submissions, administrative review actions, report requests, and super-admin account and role requests. In return, it provides outputs such as announcements, notifications, job posts, alumni records, analytics, reports, feedback logs, account and role reports, and audit reports.

The Level 1 DFD further breaks down the system into seven major processes: authentication and account management, alumni records and tracer management, jobs and job submissions management, announcements and notifications, analytics and reporting, feedback and audit logging, and admin role management. Authentication includes secure login controls such as password reset and session-aware account access. The Super Admin process is separated from regular administrator operations to show that only the Super Admin can create, view, update, and delete administrator accounts, assign administrator access roles, and edit alumni account details when account correction is required. These account changes are connected to the users, admins, super admins, and system logs data stores for accountability.
