# Required Files to Add in Claude Project Knowledge

Upload or include these project files/folders for best Claude output.

## Must Add
- `README.md`
- `composer.json`
- `package.json`
- `.env.example`
- `routes/web.php`
- `routes/agency.php`
- `routes/super-admin.php`
- `routes/auth.php`
- `app/Models/`
- `app/Http/Controllers/Agency/`
- `app/Http/Controllers/SuperAdmin/`
- `app/Http/Middleware/`
- `app/Http/Requests/`
- `app/Services/`
- `app/Support/`
- `database/migrations/`
- `database/seeders/`
- `resources/views/layouts/`
- `resources/views/agency/`
- `resources/views/super-admin/`
- `resources/views/prints/`
- `resources/css/`
- `resources/js/`
- `docs/references/`
- `docs/images/`

## Very Important for PDF Tasks
- `resources/views/prints/hr/partials/application-body.blade.php`
- all files inside `resources/views/prints/hr/`
- all files inside `resources/views/prints/hr/partials/`
- `app/Services/PdfGeneratorService.php`
- `app/Services/BarcodeService.php`
- `app/Support/PrintDataMapper.php`
- `docs/references/ksa-complete-file-reference-0001.pdf`
- `docs/images/ksa-application-reference-0001.jpg`
- `docs/images/ksa-application-reference-0002.jpg`
- `docs/images/ksa-application-reference-0003.jpg`
- `docs/images/ksa-application-reference-0004.jpg`

## Useful but Optional
- `.cpanel.yml`
- `DEPLOYMENT.md`
- `CPANEL_DEPLOYMENT.md`
- `SERVER_COMMANDS.md`
- `BACKUP_AND_RESTORE.md`
- `PRODUCTION_CHECKLIST.md`

## Do Not Upload as Main Knowledge Unless Needed
- `vendor/`
- `node_modules/`
- `storage/logs/`
- `bootstrap/cache/`
- `.env` with real secrets
- large generated PDFs unless being used as reference
