# VisaDeskPro — Claude Project Instructions

## Project Summary
VisaDeskPro is a Laravel 12 SaaS application for Saudi Arabia recruitment/visa processing agencies. It manages agencies, subscriptions/plans, HR/candidate records, agents, embassy lists, settings, barcode/PDF generation, and super-admin/agency dashboards.

## Tech Stack
- PHP 8.2+
- Laravel 12.60+
- MySQL for production/local preferred; SQLite may exist for quick local testing
- Blade templates
- Bootstrap/Tailwind/Vite assets depending on page
- mPDF for PDF generation
- Picqer barcode generator
- Spatie Laravel Permission
- Laravel Breeze session auth

## Main User Goal
Keep improving the system with safe, incremental changes while preserving existing business logic. Most priority areas are:
1. HR Record Application PDF must match KSA reference files exactly.
2. Embassy List PDF/print must match reference PDF exactly.
3. Agency dashboard and Super Admin dashboard should be modern, easy, and user-friendly.
4. Agency settings fields should match reference and include all required fields.
5. Multi-tenant agency access, subscription, and role logic must never break.

## Critical Reference Files
Use these files before changing print/PDF formats:
- `docs/references/ksa-complete-file-reference-0001.pdf`
- `docs/references/embassy-list-reference.pdf`
- `docs/images/ksa-application-reference-0001.jpg`
- `docs/images/ksa-application-reference-0002.jpg`
- `docs/images/ksa-application-reference-0003.jpg`
- `docs/images/ksa-application-reference-0004.jpg`
- `docs/images/embassy-list-reference-0001.jpg`
- `docs/images/embassy-list-reference-0002.jpg`

## Important Rules
- Do not delete existing features, routes, controllers, models, views, migrations, or seeders.
- Do not create duplicate fields without checking migrations/models/request validation first.
- Do not change database schema for print layout tasks unless absolutely required.
- Do not make broad rewrites when a small Blade/CSS/service change is enough.
- Before editing, inspect the related route, controller, model, service, request, view, and migration.
- Always give a changed-file plan before modifying many files.
- For risky changes, ask for approval before execution.
- Keep browser preview, print preview, and downloaded PDF output visually consistent.
- Protect multi-tenancy: agency users must only access their own agency data.
- Super admin routes and agency routes must stay separated.
- Never pass null agency_id to services that require an integer agency ID.

## Work Style
For every task:
1. Understand the exact issue.
2. Inspect related files first.
3. Identify root cause.
4. Explain changed-file plan briefly.
5. Make minimal safe changes.
6. Run/mention relevant tests or commands.
7. Summarize changed files and how to verify.

## Local Commands
Common local commands:
```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed
php artisan serve
npm run build
```

Common debug commands:
```bash
php artisan route:list
php artisan config:clear
php artisan view:clear
php artisan cache:clear
php artisan migrate:status
```

## Deployment Context
The project may be deployed on shared hosting/cPanel with `.cpanel.yml`. Avoid server-specific assumptions. When deployment is requested, provide simple terminal commands and avoid Docker unless asked.

## PDF/Print Quality Standard
For PDFs, “similar” is not enough. Match reference files page-by-page:
- A4 size
- exact margins
- exact top spacing
- exact font family, size, boldness, and casing
- exact table border thickness
- exact row height/cell padding
- exact barcode/photo placement
- exact Arabic/English alignment
- no overflow, cutting, scaling, or wrong page break

## Current PDF Priority Notes
For HR application PDF Page 1 Section 1:
- Use reference image/PDF as source of truth.
- Full Name and Mother’s Name values should be bold/dark like reference, but not too tall.
- Do not force uppercase globally. Match reference casing only.
- Arabic text should not become overly bold if reference Arabic is lighter.
- Business address row should be slightly bolder/darker.
- Header barcode/photo/MOFA/embassy text alignment must match reference.

## Output Format
When responding to the user:
- Use clear Bangla + simple English technical terms.
- Provide copy-paste-ready prompts when requested.
- For code tasks, list changed files and exact verification steps.
