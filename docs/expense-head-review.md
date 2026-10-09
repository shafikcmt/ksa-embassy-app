# Expense Heads and Payment Voucher review

Branch: `feature/ajax-filters`. This feature is reviewed for a separate commit after approved AJAX commit `14663ba51100cc26b47119d5c2a7e3614656c7cf`, which remains unchanged. No push or deployment was performed.

## Accounting audit and Agent Payment decision

Manual Expenses previously validated a fixed `Expense::CATEGORIES` key. CSV imports accepted those keys or labels. Paid Payment Vouchers book one read-only Expense in the same transaction as payment; the existing unique `payment_voucher_id`, row locks, state restrictions, and integer-cent voucher calculations remain intact.

Profit/Loss sums Expenses plus reversal-aware Agent Khata debit payouts. Agent credits are repayments, not new income. An Agent Payment head could record a Khata payout again in Expenses. No Agent Payment/Commission head or second agent payout writer was added. Manpower and supplier costs remain ordinary vendor costs; actual agent ledger payouts stay in Agent Khata.

## Schema and defaults

`ExpenseHead` owns `agency_id`, `name`, stable `code`, `is_active`, protected `is_system`, `sort_order`, and timestamps. Names and codes are unique per agency. Both `expenses` and `payment_vouchers` gain nullable `expense_head_id` foreign keys with restrictive deletion. Existing category strings, amounts, voucher lines/adjustments, dates, statuses, and payment links are retained.

Existing agencies receive defaults during migration; new agencies receive them through the Agency creation event. Defaults are not recreated on every settings visit, so renames, disabled heads, and deleted unused heads remain managed choices.

Active defaults (19):

1. Manpower Cost
2. Salary
3. Staff Advance
4. Office Rent
5. Utility Bill
6. Transport / Conveyance
7. Food / Entertainment
8. Medical Expense
9. Embassy / Visa Processing
10. BMET / Government Fee
11. Ticket / Travel Expense
12. Supplier Payment
13. Office Supplies
14. Maintenance / Repair
15. Printing / Stationery
16. Marketing
17. Bank Charge
18. Enjaz Dollar
19. Miscellaneous / Others

An additional inactive system head, Payment Voucher, preserves unclassified historical voucher records. It cannot be activated, renamed, or deleted through management.

## Management and forms

Agency Admins reach Expense Heads from ERP Settings, Expenses, or the voucher form. They can add, rename, activate/deactivate, change order numbers, and delete unused heads. The bulk reorder endpoint validates every ID before writing. Heads referenced by Expenses or any voucher, including soft-deleted drafts, cannot be deleted. Codes remain stable when names change.

Manual Expenses and new vouchers use the same active, agency-owned head source. Existing records can retain their current inactive head. Ownership checks run before management mutations; expense/voucher validation rejects another agency's IDs. Voucher saves recheck the selected head against the locked current draft, so a stale draft cannot bypass inactive-head restrictions.

The voucher form now has date, one Expense Head / Reason of Costing selector, recipient, amount, payment method, reference, optional remarks, and method-specific cheque/bank details. It keeps the existing draft/approve/pay workflow. A single internal quantity-one line passes the amount through the existing cent calculation service. Historical line data stays intact until a draft is deliberately edited; the form explains replacement of historical lines/adjustments. Historical purpose and recipient type are retained during simple edits. Approved/paid vouchers remain locked.

The PDF retains the agency header/logo/barcode and professional meta boxes, but removes the item/Qty/SL table. It shows recipient, head, amount, reference/remarks, Taka/Paisa words, and Recipient / Accountant / Proprietor signature lines. Historical tax/discount amounts remain visible when present.

## Legacy migration, reports, and CSV

The additive migration backfills known Expense category keys into matching agency heads without updating any existing financial columns or category strings. Unknown categories remain nullable and render through legacy labels. Historical vouchers start on the inactive archive head; where a linked Expense already has a known meaningful category, its head is carried to the voucher. Free-text descriptions are never guessed into categories.

Expense lists filter by heads, including inactive historical heads, or remaining legacy categories. Print/CSV links retain these filters. Dashboard/report breakdowns display managed head labels; unfiltered totals and P&L calculations retain their existing source and arithmetic. A report head filter applies only to its expense slice, not revenue or Agent Khata.

Expense CSV keeps `expense_date,category,amount,paid_via,note`. Imports accept active agency head codes/current names and known old labels; inactive, unknown, and system categories fail validation. Preview and commit both validate the complete file. Auto-booked voucher rows export their head label with a `voucher:` marker and cannot be imported as another outflow. This preserves the prior protection that rejected generic Payment Voucher exports after introducing meaningful heads.

Migration/backfill and rollback/reapply were exercised against isolated SQLite fixtures. Backfill tests compare every pre-existing Expense/voucher column; rollback/reapply on a copied full schema preserves Expense money. Rollback removes the new head definitions/links, so those new classifications require a backup if rollback is ever chosen.

Local MySQL `ksa_embassy` was subsequently verified: the migration is recorded as Ran in batch 2, `expense_heads` exists, and both Expense and Payment Voucher links are nullable unsigned bigint foreign keys. `php artisan migrate` returned Nothing to migrate. The existing agency, 51, has all 19 active defaults and the inactive protected archive; no recognized-category Expenses or vouchers have missing links. Authenticated GET checks for Expenses, Payment Vouchers, the create form and Expense Heads settings returned HTTP 200. No production migration or deployment was performed.

## Files

- `app/Http/Controllers/Erp/ExpenseHeadController.php` (new)
- `app/Http/Controllers/Erp/ExpenseController.php`
- `app/Http/Controllers/Erp/PaymentVoucherController.php`
- `app/Http/Controllers/Erp/ReportController.php`
- `app/Http/Requests/PaymentVoucherRequest.php`
- `app/Models/ExpenseHead.php` (new)
- `app/Models/Agency.php`
- `app/Models/Expense.php`
- `app/Models/PaymentVoucher.php`
- `app/Services/ErpReportService.php`
- `app/Services/PaymentVoucherService.php`
- `database/migrations/2026_10_08_000001_create_expense_heads.php` (new)
- `resources/views/erp/expense-heads/index.blade.php` (new)
- `resources/views/erp/expense/index.blade.php`
- `resources/views/erp/payment-vouchers/_form.blade.php`
- `resources/views/erp/payment-vouchers/create.blade.php`
- `resources/views/erp/payment-vouchers/index.blade.php`
- `resources/views/erp/payment-vouchers/show.blade.php`
- `resources/views/erp/settings/index.blade.php`
- `resources/views/erp/dashboard.blade.php`
- `resources/views/erp/reports/index.blade.php`
- `resources/views/prints/payment-voucher.blade.php`
- `routes/erp.php`
- `tests/Feature/ExpenseHeadTest.php` (new)
- `tests/Feature/PaymentVoucherTest.php`
- `public/build/manifest.json`
- New CSS `public/build/assets/app-DXMqBK2D.css`; obsolete `app-CJsv2ek2.css` removed. The approved app JS and landing bundle remain unchanged. Compiled output is explicitly tracked for shared hosting.
- `docs/expense-head-review.md` (this document)

All browser scripts, screenshots, PDFs, logs, and SQLite fixtures are ignored under `scratch/`.

## Validation results

- Final full PHP suite: **268 passed; 5 pre-existing ProfileTest failures; 2,216 assertions**. All five failures concern the absent `/profile` routes and reproduce on the clean-master baseline established in the preceding review. No Profile routes/controllers were changed.
- Relevant coverage within that full run: **15 ExpenseHead tests, 13 PaymentVoucher tests, 3 AgentKhata tests, and the 359-assertion AJAX rendering contract all passed**. ExpenseHead coverage includes report/P&L invariance, CSV double-book prevention, migration/backfill, authorization, inactive heads, stale drafts, and PDF parsing.
- JavaScript: **5 passed**, including the approved IME regression test; the AJAX engine is unchanged.
- Browser: Add/Rename/Order, manual Expense creation/edit modal, simple cheque voucher, inactive-head exclusion/retention, and four screens at **1440 and 390 pixels** passed. No page errors or document-width overflow. External CDN requests were stubbed; destructive/paid transitions are covered by PHP tests on isolated test data.
- The generated voucher PDF was parsed and visually inspected: one A4 page, no Qty/Description/SL item table, correct amount/Taka words and three signature lines.
- Isolated full-schema migration rollback/reapply preserved Expense amounts; backfill comparisons preserved every existing Expense/voucher column.
- Final `npm.cmd run build`, `php artisan view:cache`, and `git diff --check` passed. Build hashes are deterministic; app JS and landing assets are unchanged from the approved commit.
- Pint checks passed for the new PHP files. After formatting, the 15 ExpenseHead tests passed again with 208 assertions.
- Final review rerun: **32 affected tests passed, 364 assertions**, covering Expense Heads, vouchers, Expenses, Agent Khata and report/P&L invariance. The full-suite figures above are from the earlier full run.
- Final PDF polish: aligned compact header, equal-height metadata cards, one highlighted head row, light amount summary and words panel, and three equal signature columns. Eight synthetic samples, including draft/approved, long recipient/head/remarks, large amount, combined stress and historical paid adjustments, remained one page. Samples were inspected at 100%; no clipping or overlap was observed.
- Final build after refreshing the compiled Blade views is repeatable (`app-DXMqBK2D.css`). A parsed comparison of all CSS rules against the approved asset found two added selectors, three removed obsolete wizard selectors, and no changed declarations. Every manifest target exists; app/landing JS are unchanged. PowerShell blocks `npm.ps1`, so builds use the equivalent `npm.cmd run build` command.

Commit this feature separately with `Add agency-managed expense heads and simplify payment vouchers`; do not amend the approved AJAX commit, push or deploy.
