d# ARLO Use Case Diagram

This use case diagram is aligned with the workflow of the **Web-Based Tracer and Employment Analytics System for the Alumni Relations and Linkages Office**.

```mermaid
flowchart LR
    classDef actor fill:#ffffff,stroke:#374151,stroke-width:1.2px,color:#111827;
    classDef usecase fill:#eef2ff,stroke:#4f46e5,stroke-width:1.2px,color:#111827;

    Alumni[Alumni]
    Admin[Administrator]

    class Alumni,Admin actor;

    subgraph System["Web-Based Tracer and Employment Analytics System"]
        direction TB

        UC1([Register Account])
        UC2([Log In])
        UC3([Reset Password])
        UC4([View Alumni Dashboard])
        UC5([Manage Profile and Settings])
        UC6([Submit Tracer Record])
        UC7([Browse Jobs])
        UC8([Apply for Job])
        UC9([Submit Job Opportunity])
        UC10([View Announcements])
        UC11([View Notifications])
        UC12([Submit Feedback])

        UC13([View Admin Dashboard])
        UC14([Manage Alumni Records])
        UC15([Manage Job Postings])
        UC16([Review Job Submissions])
        UC17([Manage Announcements])
        UC18([Manage Feedback])
        UC19([View Analytics and Reports])
        UC20([View Audit Logs])
    end

    class UC1,UC2,UC3,UC4,UC5,UC6,UC7,UC8,UC9,UC10,UC11,UC12,UC13,UC14,UC15,UC16,UC17,UC18,UC19,UC20 usecase;

    Alumni --- UC1
    Alumni --- UC2
    Alumni --- UC3
    Alumni --- UC4
    Alumni --- UC5
    Alumni --- UC6
    Alumni --- UC7
    Alumni --- UC8
    Alumni --- UC9
    Alumni --- UC10
    Alumni --- UC11
    Alumni --- UC12

    Admin --- UC2
    Admin --- UC3
    Admin --- UC13
    Admin --- UC14
    Admin --- UC15
    Admin --- UC16
    Admin --- UC17
    Admin --- UC18
    Admin --- UC19
    Admin --- UC20
```

## Suggested Paper Label

- **Figure X. Use Case Diagram of the Proposed Web-Based Tracer and Employment Analytics System for the Alumni Relations and Linkages Office**

## Short Explanation For Your Paper

The use case diagram presents the main interactions between the two primary actors, namely the alumni and the administrator, and the proposed web-based tracer and employment analytics system. The alumni actor can register an account, log in, reset a password, manage a profile and settings, submit tracer records, browse and apply for jobs, submit job opportunities, view announcements and notifications, and send feedback. On the other hand, the administrator actor can log in, reset a password, access the admin dashboard, manage alumni records, manage job postings, review job submissions, manage announcements, monitor feedback, view analytics and reports, and review audit logs.
