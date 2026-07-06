# UI Modernization Skill

Use this skill for Agency dashboard, Super Admin dashboard, settings, HR forms, tables, notification UI, and general interface redesign.

## Goal
Make the UI modern, clean, premium, user-friendly, responsive, and easy for non-technical agency users.

## Design Principles
- Simple SaaS dashboard structure.
- Clear typography and spacing.
- Easy-to-read tables.
- Modern cards and action buttons.
- Notification/alerts should live inside bell/dropdown unless important.
- Avoid clutter and unnecessary messages.
- Keep forms compact but readable.
- Use consistent colors and states.
- Mobile responsive first.

## Procedure
1. Inspect existing Blade and layout structure.
2. Preserve all existing actions, forms, routes, and permissions.
3. Improve layout and styling only where needed.
4. Reduce complexity for HR record forms.
5. Keep Super Admin and Agency UI visually consistent.
6. Test desktop and mobile widths.

## Do Not
- Do not remove existing buttons/actions unless user asked.
- Do not break form names/IDs used by controllers.
- Do not change validation or database logic unless required.
- Do not hide important errors without providing user-friendly feedback.
