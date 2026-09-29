# BMET clearance

The BMET module is available at `/erp/bmet`. The ERP sidebar now opens this page. Existing `/erp/manpower` URLs and CSV imports continue to work against the same records.

## Storage and compatibility

`BmetEntry` extends the existing `ManpowerCompletion` register. Migration `2026_09_24_000009_add_bmet_fields_to_manpower_completions_table.php` adds BMET fields without copying or replacing records. IDs, original passenger details, agent links, and report queries are preserved.

The public form attributes map to existing columns:

| Form attribute | Stored column |
| --- | --- |
| `full_name` | `customer_name` |
| `passport_number` | `passport_no` |
| `ec_date` | `completed_date` |

All reads, exports, profile searches, agent choices, and mutations are agency-scoped. New form saves and legacy CSV imports serialize uniqueness checks using an agency-row lock. Existing duplicate records are preserved; new duplicate passports are rejected. Deletes are soft deletes.

## Tracking rule

The brief contains conflicting expiry instructions. The implemented assumption is one calendar year from the EC date, with leap-day anniversaries clamped to February 28. An entry remains valid on its anniversary and expires the following day. A warning appears when fewer than 30 days remain.

Automatic status is Pending without an EC number and Cleared with an EC number. Cleared entries display Expired after their tracked expiry. Explicit Pending, Hold, and Expired states are preserved. The same effective status drives badges, counts, filters, sorting, and PDF output. No scheduled job is needed.

The EC date must be today or earlier. Both ISO dates and strict `dd/mm/yyyy` input are accepted. Browser date controls display the user's locale.

## Interface and output

Add and edit links open the list's modal. HR passport lookup supplies passenger name, father name, visa number, and sponsor ID; populated visa fields can be unlocked for manual corrections. Selecting an agent fills an empty reference and displays contact details, while preserving a reference the user typed manually.

The supplied reference sequence has ten columns, including Remarks. All ten appear in CSV and landscape PDF output, with a separate Actions column on screen. Export and print use the current filters and sort across all matching records. On mobile, the table initially shows passenger/status, passport, EC date, and actions; “Show all columns” exposes the remaining fields.

Legacy imports retain their original template and are separate from the summary CSV export. Optional certificate uploads, email reminders, and bulk selections are not included.

## Verification

Run `php artisan test --compact tests/Feature/BmetEntryTest.php` and `npm.cmd run build` on Windows. The feature suite covers CRUD, tenant isolation, validation, expiry boundaries, HR linking, filters, pagination, legacy compatibility, CSV escaping, and a real A4 landscape PDF.
