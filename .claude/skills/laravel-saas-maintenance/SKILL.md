# Laravel SaaS Maintenance Skill

Use this skill when working on VisaDeskPro Laravel code, bugs, routes, auth, dashboard, CRUD, database, middleware, and role/permission issues.

## Goal
Make safe incremental changes without breaking existing SaaS operations.

## Procedure
1. Identify the affected module: Super Admin, Agency, HR, Agent, Embassy List, Subscription, Settings, PDF, Auth.
2. Inspect related files before editing:
   - route
   - controller
   - model
   - middleware/policy
   - request validation
   - service/support class
   - Blade view
   - migration/seeder
3. Find root cause before changing code.
4. Prefer minimal edits over broad rewrites.
5. Preserve existing routes, names, actions, and database records.
6. After editing, list changed files and verification commands.

## Safety Rules
- Never delete existing functionality unless the user explicitly asks.
- Never create duplicate database fields without checking migrations.
- Never bypass authorization or tenancy checks.
- Never let agency users access another agency’s data.
- Never pass null agency_id to a method requiring int.
- Super Admin and Agency routes must stay separated.

## Verification Commands
```bash
php artisan optimize:clear
php artisan route:list
php artisan migrate:status
php artisan test
npm run build
```
