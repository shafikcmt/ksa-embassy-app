# Prompt Templates for VS Code Claude Extension

## General Bug Fix Prompt
I am working on the VisaDeskPro Laravel 12 project. Please inspect the related route, controller, model, middleware, service, request, view, migration, and seeder before editing.

Issue:
[PASTE ERROR / SCREENSHOT DETAILS]

Requirements:
- Find root cause.
- Do not break existing Super Admin or Agency flow.
- Make minimal safe changes.
- Protect multi-tenant agency data.
- After fixing, list changed files and verification commands.

## HR Application PDF Prompt
Update the HR Application PDF to match the reference exactly.

References:
- `docs/references/ksa-complete-file-reference-0001.pdf`
- `docs/images/ksa-application-reference-0001.jpg`
- `docs/images/ksa-application-reference-0002.jpg`
- `docs/images/ksa-application-reference-0003.jpg`
- `docs/images/ksa-application-reference-0004.jpg`

Rules:
- Do not create a new design.
- Match reference page-by-page.
- A4 exact layout.
- Browser preview, print preview, and downloaded PDF must match.
- Match font family, font size, weight, casing, padding, spacing, border, barcode, photo, Arabic/English alignment.
- If image reading fails, use the local PDF/reference files and continue editing template/CSS.
- Only update relevant print Blade/CSS/service files.

Current issue:
[PASTE EXACT ISSUE]

## UI Redesign Prompt
Improve this page UI in VisaDeskPro without changing core business logic.

Page:
[PAGE / ROUTE]

Requirements:
- Modern, clean, easy-to-use SaaS dashboard style.
- Improve spacing, cards, buttons, typography, table readability, mobile responsiveness.
- Use existing Bootstrap/Tailwind style consistently.
- Do not remove existing actions/features.
- Keep Super Admin and Agency UI consistent.
- List changed files and test route.

## Auth/Agency Access Prompt
Fix the agency access/auth flow safely.

Requirements:
- Super Admin should redirect to Super Admin dashboard.
- Agency Admin/Staff must have valid `agency_id`.
- Never pass null agency_id to service methods.
- If agency user has no agency, show friendly message/redirect.
- Middleware must not query agency with null id.
- Demo seeders must assign valid agencies to agency users.
- Protect agency data isolation.
