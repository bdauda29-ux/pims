# Personnel Information Management System (PIMS) — Nigeria Immigration Service (NIS)

Date: 2026-04-15  
System: Personnel Information Management System (PIMS)  
Organization Context: Nigeria Immigration Service (NIS)

## 1) Introduction

The Nigeria Immigration Service (NIS) requires accurate, timely, and auditable personnel information to support postings, promotions, workforce planning, and operational readiness across Service Headquarters (SHQ), Directorates, Formations, and Offices. The Personnel Information Management System (PIMS) provides a centralized, role-governed platform to manage these records, reduce manual paperwork, and improve the speed and reliability of administrative decisions. This report describes the system’s goals, functions, features, security controls, and technical requirements.

## 2) Aims and Objectives

- Provide a centralized, authoritative NIS personnel database for biodata, postings, promotions, and retirement information.
- Support SHQ and directorate/formation/office-level administration with properly scoped access.
- Standardize personnel data capture and improve data completeness/quality across the service.
- Enable faster reporting and operational decision-making through dashboards and rank charts.
- Improve accountability through audit logging of critical actions.

## 3) Purpose

- Serve as the official platform for day-to-day personnel information management within the Nigeria Immigration Service.
- Provide a controlled environment for administrative workflows such as registration, updates, role assignment, and retirement processing.
- Provide a structured model of NIS organization (SHQ → Directorates → Formations → Offices) and link personnel to the correct unit.

## 4) Benefits

- Data consistency: single source of truth for personnel records and postings.
- Faster administration: search, filters, bulk import/export, and structured management pages reduce manual work.
- Better oversight: dashboards and rank charts provide quick summaries and trend indicators.
- Controlled delegation: admin responsibilities can be assigned at directorate/formation/office scope.
- Traceability: audit logs help detect and investigate unauthorized or incorrect changes.

## 5) Executive Summary

The Personnel Information Management System (PIMS) is a web-based application designed to support the Nigeria Immigration Service (NIS) in managing personnel records and administrative workflows. It provides structured data management for personnel biodata, postings, promotions, retirements, and organizational structure (Service Headquarters (SHQ), Directorates, Formations, and Offices), with controlled access via role-based and permission-based authorization. The system includes audit logging for sensitive operations and internal notifications for key events.

## 6) Technology Stack

- Backend: PHP 8.3, Laravel 13
- Frontend: Blade templates, Tailwind CSS, Alpine.js, Vite
- PDF: dompdf (used for report/rank chart-style exports)
- Auth scaffolding: Laravel Breeze

## 7) System Architecture

PIMS follows a standard multi-tier web application architecture based on the Laravel MVC framework.

### A. High-Level Architecture (Logical View)

- Presentation layer: browser-based UI rendered with Blade templates and enhanced with Alpine.js; CSS styling via Tailwind.
- Application layer: Laravel controllers, services, middleware, and validation rules implement business logic and workflow enforcement.
- Data layer: relational database stores personnel records, structure data, audit logs, permissions, and notifications.
- Storage layer: file storage for uploads (e.g., personnel photographs) and generated assets.
- Background/scheduled processing: Laravel scheduler runs periodic jobs (e.g., retirement processing).

### B. Request/Response Flow (Runtime View)

1. A user accesses PIMS through a web browser over HTTPS.
2. The web server (Apache/Nginx) forwards the request to the PHP runtime (PHP-FPM/CLI server).
3. Laravel routes map the request to a controller action.
4. Middleware enforces authentication, role checks, ability checks, password-change prompts, and audit rules.
5. Controllers/services read/write to the database and storage as required.
6. Responses are returned as HTML views (or JSON for API endpoints) to the browser.

### C. Component Diagram (Simplified)

```text
Users (Personnel/Admins)
        |
        | HTTPS
        v
Web Server (Apache/Nginx)
        |
        v
PHP Runtime (PHP-FPM / artisan serve)
        |
        v
Laravel Application (Routes → Middleware → Controllers → Services)
        |                         |
        |                         +--> File Storage (uploads, assets)
        v
Database (MySQL/MariaDB/SQLite)
        |
        +--> role_permissions, audit_logs, user_notifications, users, formations, directorates, offices, histories

Scheduler/Cron
        |
        v
Laravel Scheduled Commands (e.g., retirement processing)
```

## 8) System Features

This section summarizes the practical features available to NIS users. Features are grouped into personnel-facing functionality (used by individual personnel accounts) and administrative functionality (used by authorized NIS administrators based on role and scope).

### A. Personnel Features

- Personal dashboard with service information and notifications.
- Posting history (formation and office postings) and promotion history.
- Profile completeness checks and required-field prompts (including directorate requirement for SHQ postings).
- Photograph upload (passport photo).
- View own profile and update allowed profile attributes.

### B. Admin Features

- Personnel management: list/search/filter, view profiles, create and update personnel records (subject to permissions).
- Promotions: identify and manage promotions (restricted to Main Admin where configured).
- Import/export: CSV import of personnel and export to CSV/PDF-like formats (subject to permissions).
- Structure management: manage and visualize offices in an organogram-like structure; manage formations and directorates where permitted.
- Role governance:
  - Convert personnel between User and Office Admin (scoped for DCG/PSO/Formation Admin as configured).
  - Manage standalone roles (e.g., PSO and Viewer) through the management console.
  - Privileges management to control abilities per role.
- Management console:
  - Formation Admin and Directorate Admin (DCG) creation workflows.
  - Rank charts by formation or directorate.
  - SHQ personnel summary as total of all directorate personnel counts.
- Audit logs: review tracked changes for accountability.

## 9) Technical Requirements

### A. Server Requirements

- PHP 8.3+
- Composer
- A supported database (e.g., MySQL/MariaDB or SQLite for development)
- Web server (Apache/Nginx) or `php artisan serve` for development

### B. Frontend Build Requirements

- Node.js and npm
- Vite build pipeline for assets (Tailwind CSS + Alpine.js)

### C. Application Services

- Scheduled task runner for daily retirement processing (Laravel scheduler/cron).

## 10) Financial Resources (Go-Live Requirements)

To deploy PIMS for live NIS operations, the following financial resources should be budgeted. Exact costs depend on deployment scale, hosting choice (government data centre vs cloud), user volume, and availability requirements.

### A. Infrastructure and Hosting

- Production server hosting (compute, storage, bandwidth).
- Database hosting (managed database or dedicated database server).
- Storage for user uploads (photographs and documents, including backup copies).
- Domain name and SSL/TLS certificates (if not provided centrally).
- Backup and disaster recovery services (snapshots, offsite backups).

### B. Security and Compliance

- Security hardening and configuration (server hardening, firewall rules, access controls).
- Monitoring and alerting (uptime, logs, intrusion indicators).
- Periodic security review and vulnerability remediation.

### C. Implementation and Deployment

- Production deployment setup (environment configuration, CI/CD where applicable).
- Data migration/import effort (if existing personnel records are moved into PIMS).
- Configuration of role permissions and initial admin accounts.

### D. Training and Change Management

- User training sessions for NIS admins and support staff.
- User guides and internal SOP documentation.
- Onboarding support during rollout period.

### E. Operations and Maintenance (Recurring)

- Ongoing hosting and renewal costs.
- System maintenance (updates, bug fixes, performance tuning).
- Helpdesk/support operations for user issues and account management.
- Periodic database maintenance and archive/cleanup procedures.

## 11) Security Features

- Authentication for protected modules (login required).
- Role-based access control enforced at route level.
- Ability-based access control stored in the database and enforced per request.
- Audit logging for selected state-changing routes (personnel updates, role changes, privilege updates, structure changes).
- CSRF protection for form submissions (Laravel default).
- Password-change prompting for accounts flagged with `must_change_password`.

## 12) Methodology (How The System Works)

### A. Data Capture and Validation

- Personnel data is captured via forms and validated on submission.
- Data completeness rules support “completed vs incomplete” tracking and highlight missing mandatory fields.

### B. Scope-Based Administration

- Administrative users operate within scope determined by their role and assignment:
  - Office Admin: office scope
  - Formation Admin: formation scope
  - DCG/PSO: directorate scope (especially for SHQ-linked structures)
  - Main Admin/Super Admin: broader scope

### C. Permissions and Governance

- Roles control access to modules; abilities control access to actions.
- Permissions can be updated in the Privileges module, and are applied consistently via middleware.

### D. Audit and Accountability

- Important write operations are logged with metadata and optional before/after snapshots.
- This supports compliance, investigations, and operational transparency.

### E. Automation

- The system computes retirement dates and runs a daily process to set retirement status based on policy thresholds.

## 13) Core Modules (What The System Does)

### A. Personnel Registry (NIS Personnel Records)

PIMS maintains a personnel registry with identity and service data fields such as:

- NIS Number (NIS No), names, gender, contact details
- Rank and rank code
- State and LGA of origin
- Dates: date of birth, date of first appointment (DOFA), date of present appointment (DOPA), date of present posting to formation (DOPP)
- Service/administrative status: active/inactive, retirement metadata
- Assignment references: formation, directorate (especially for SHQ), office
- Photograph (passport photo upload)

The registry supports:

- Search and filtering (e.g., by formation/directorate/office, rank, gender, key dates)
- Data-quality views (completed vs incomplete profiles)
- Record creation and editing (subject to role permissions)
- Retirement workflow (manual retire + automated retirement processing)

### B. NIS Structure Management (SHQ, Directorates, Formations, Offices)

PIMS models organizational structure as:

- Service Headquarters (SHQ) as a key reference formation
- Directorates (with code and type)
- Formations (with code, type, and parent-child hierarchy for zonal structures)
- Offices (with optional directorate linkage, type, and hierarchical parent office for organograms)

The system provides an organogram-like view and supports office creation and structural organization under the appropriate formation/directorate.

### C. Dashboards (Personal vs Admin Mode)

PIMS provides a dashboard that can operate in:

- Personal mode: a personnel’s own service information, posting history, promotion history, and notifications, plus prompts for missing required profile fields.
- Admin mode: aggregate views and statistics relevant to the admin’s scope (office/formation/directorate/global), including rank distribution charts and promotion-due indicators.

### D. Role & Permission Governance

PIMS uses two layers of access control:

- Role gating (route-level access based on a user role)
- Ability/permission gating (database-driven permissions per role)

This enables NIS to delegate responsibilities by role, while controlling exactly which actions each role can perform.

### E. Management Console (NIS Admin Workflows)

The management console includes:

- Admin listing/creation for Formation Admins and Directorate Admins (DCGs)
- Standalone role assignment (e.g., PSO and Viewer)
- Rank charts for formations/directorates
- SHQ personnel count shown as an aggregate of all directorate personnel counts

### F. Audit Log (Accountability)

Administrative actions (non-read operations) are selectively logged into an audit log, capturing:

- Actor, route, method, and metadata
- Before/after snapshots for tracked changes when available

This supports accountability and traceability for sensitive operations.

### G. Notifications (Internal)

The system maintains internal user notifications (stored in the database) for events such as role changes and other admin-triggered messages.

## 14) Roles (NIS Operational Context)

PIMS defines operational roles aligned to NIS workflows. Common roles include:

- Super Admin: highest-level technical/admin access
- Main Admin: primary business/system administrator
- Formation Admin: manages operations within a formation scope
- Office Admin: manages operations within an office scope
- DCG: directorate administrator (within directorate scope)
- Principal Staff Officer (PSO): standalone directorate-level administrative oversight role
- Viewer: standalone read-focused role for controlled visibility
- User: standard personnel account

Role scope is enforced through both role middleware and ability permissions. Some administrative views are additionally scoped by formation_id, office_id, or directorate_id.

## 15) Key Data Entities (High-Level)

- Users (Personnel): core table holding personnel and account data
- Formations: organizational units; supports hierarchical relationships
- Directorates: directorate records with codes and types
- Offices: office organograms (hierarchical parent/child + optional directorate linkage)
- Formation Postings / Office Postings: historical posting records
- Promotion Histories: promotion records
- Custom Fields / Custom Field Values: dynamic, configurable fields for personnel profiles
- Role Permissions: ability flags per role
- Audit Logs: records of tracked administrative actions
- User Notifications: internal notifications to users

## 16) Automation & Scheduled Processing

PIMS includes scheduled automation:

- Automatic retirement processing based on age (60) or years of service (35), executed daily.

## 17) Security & Control Notes (Operational)

- Authentication is required for all protected features.
- Access is controlled by role checks and fine-grained abilities.
- Password-change prompting exists for accounts marked with “must_change_password”.
- Audit logs are written only for selected state-changing routes to reduce noise while keeping accountability for important actions.

## 18) Operational Summary (How NIS Uses PIMS)

In practice, PIMS supports NIS by enabling:

- Centralized personnel recordkeeping (SHQ + formations + directorates + offices)
- Controlled administrative workflows for updates, postings, promotions, and retirement handling
- Scoped delegation: office/formation/directorate-level administrators can manage only what they are permitted to manage
- Governance: roles, privileges, and audit logs ensure compliance and accountability
