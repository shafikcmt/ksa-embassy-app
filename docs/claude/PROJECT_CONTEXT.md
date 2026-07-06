# Project Context — VisaDeskPro

VisaDeskPro is a KSA Embassy/agency management SaaS built with Laravel 12. It supports Super Admin and Agency users.

## Business Purpose
The system helps manpower/recruitment agencies manage:
- Agency profile and license/subscription
- HR/candidate records
- Passport, visa, clearance, and related HR information
- Agents
- Embassy submission lists
- Application PDFs and print files
- Subscription plans and billing controls

## Roles
- Super Admin: controls platform, agencies, plans, subscriptions, global settings, and all records.
- Agency Admin: manages own agency’s HR records, agents, embassy lists, agency settings, and PDFs.
- Agency Staff/User: limited agency operations under assigned agency.

## Main Modules
- Auth and role-based redirect
- Super Admin dashboard
- Agency dashboard
- Agency management
- Subscription plans
- Agency settings
- HR Profile CRUD
- Agent CRUD
- Embassy List CRUD
- PDF/print generation
- Barcode generation
- Notification/alert display

## Important Code Areas
- `routes/web.php`
- `routes/agency.php`
- `routes/super-admin.php`
- `app/Http/Controllers/Agency/*`
- `app/Http/Controllers/SuperAdmin/*`
- `app/Http/Middleware/EnsureAgencyAccess.php`
- `app/Http/Middleware/EnsureSuperAdmin.php`
- `app/Services/PdfGeneratorService.php`
- `app/Services/BarcodeService.php`
- `app/Services/DashboardStatsService.php`
- `app/Support/PrintDataMapper.php`
- `app/Support/HrFieldControls.php`
- `resources/views/prints/hr/*`
- `resources/views/prints/hr/partials/*`
- `resources/views/prints/embassy*`
- `database/migrations/*`
- `database/seeders/*`

## Reference Files
- `docs/references/ksa-complete-file-reference-0001.pdf`
- `docs/references/embassy-list-reference.pdf`
- `docs/images/ksa-application-reference-0001.jpg`
- `docs/images/ksa-application-reference-0002.jpg`
- `docs/images/ksa-application-reference-0003.jpg`
- `docs/images/ksa-application-reference-0004.jpg`
- `docs/images/embassy-list-reference-0001.jpg`
- `docs/images/embassy-list-reference-0002.jpg`
