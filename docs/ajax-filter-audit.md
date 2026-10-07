# AJAX filter audit and review

Base: `97f175e`, branch `feature/ajax-filters`. Initial working tree and diff were clean. This records the pre-commit review; no push, deployment, branch switch, reset, or backend change was made.

## Converted pages: 20

Every path below is relative to the repository and is a changed file.

| Area | Page | Blade file |
| --- | --- | --- |
| ERP | MOFA | `resources/views/erp/mofa/index.blade.php` |
| ERP | BMET | `resources/views/erp/bmet/index.blade.php` |
| ERP | Medical | `resources/views/erp/medical/index.blade.php` |
| ERP | Visa Stamping | `resources/views/erp/visa-stamping/index.blade.php` |
| ERP | Delivery | `resources/views/erp/delivery/index.blade.php` |
| ERP | Double MOFA | `resources/views/erp/double-mofa/index.blade.php` |
| ERP | Invoices | `resources/views/erp/invoices/index.blade.php` |
| ERP | Payment Vouchers | `resources/views/erp/payment-vouchers/index.blade.php` |
| ERP | Due List | `resources/views/erp/due-list/index.blade.php` |
| ERP | Reports | `resources/views/erp/reports/index.blade.php` |
| ERP | Profit / Loss | `resources/views/erp/profit-loss/index.blade.php` |
| Agency | HR | `resources/views/agency/hr/index.blade.php` |
| Agency | Agents | `resources/views/agency/agents/index.blade.php` |
| Agency | Embassy Lists | `resources/views/agency/embassy-lists/index.blade.php` |
| Agency | Notes | `resources/views/agency/notes/index.blade.php` |
| Super Admin | Agencies | `resources/views/super-admin/agencies/index.blade.php` |
| Super Admin | Agents | `resources/views/super-admin/agents/index.blade.php` |
| Super Admin | HR | `resources/views/super-admin/hr/index.blade.php` |
| Super Admin | Embassy Lists | `resources/views/super-admin/embassy-lists/index.blade.php` |
| Super Admin | Subscriptions | `resources/views/super-admin/subscriptions/index.blade.php` |

HR has two GET filter forms. The other converted pages each have one; Notes hides its form on the Trash tab. Discovery also found these GET forms, which remain unchanged:

| File | Purpose / exclusion |
| --- | --- |
| `resources/views/agency/attendance/index.blade.php` | Reports range and employee filters. Attendance has a shared tabbed Alpine root, multiple operational forms/dialogs, and a Chart.js instance created by a pushed page script from server JSON. Its report exports/range labels and tab state need a separately scoped integration and chart lifecycle review. The generic engine does not rerun page scripts; replacing this page's shared root would be unsafe. Attendance remains deliberately excluded. |
| `resources/views/agency/dashboard.blade.php` | Dashboard filters and page-specific chart script; outside the requested list-page scope. |
| `resources/views/erp/dashboard.blade.php` | Dashboard year filter and monthly PDF GET form; outside scope, with PDF navigation preserved. |
| `resources/views/layouts/agency.blade.php` | Global HR navigation/search form; remains normal navigation rather than opting the layout into every page. |

## Shared implementation

`resources/js/ajax-filters.js` is imported once by `resources/js/app.js`. Delegated input, change, submit, click, and history listeners serve every opted-in page. Forms use `data-ajax-filter` and `data-ajax-group`; named, non-nested replacement regions use `data-ajax-region` and the same group. Reset/status/tab links opt in with `data-ajax-link`. Pagination is recognized only inside an opted-in region's pagination navigation and only for the current origin/path.

Text input debounces for 300ms; selects and dates apply immediately. Query merging starts with the current URL, applies hidden fallbacks, then current editable fields, then the named submitter. Empty field values remove their parameters, page resets on filter changes, and duplicate parameters collapse. Pagination merges current controls, including edits pending debounce, before applying its target page. Reset links use their authoritative URL, preserving Notes' selected view where applicable.

Every change invalidates older work immediately; AbortController plus a generation check prevents stale HTML from applying. Successful requests parse complete server-rendered HTML and validate all replacement parts before mutation. Results, dependent totals, filter controls, and export URLs refresh without replacing the page shell. Focus/caret are restored. Alpine's mutation observer is suppressed during explicit destruction/initialization of changed trees; persistent parent/modal scopes survive. Initial history is annotated with replaceState, subsequent changes use pushState, and Back/Forward fetch the historical URL without adding history entries. Failed, redirected, or structurally invalid responses fall back to normal GET navigation. Busy attributes clear on completion or cancellation.

POST/create/edit/delete/payment/approval forms are never intercepted. Routes, authorization, tenancy, backend filtering, exports and print/PDF endpoints remain unchanged. Ordinary Apply controls are available in noscript fallbacks; they are absent from the JavaScript-enabled flow.

## Special cases

- Medical: removed inline full-page select submissions; reused the shared engine for search, selects, dates, sorting, reset, and pagination.
- Visa Stamping: submitter direction overrides its hidden carry-forward value exactly once. The next toggle is rendered by the server. A hidden default submit button makes Enter in search apply filters without implicitly toggling direction. The form retains an Alpine scope for Add actions.
- HR: both forms merge visible values before hidden copies; fetched HTML synchronizes both forms. Desktop rows and mobile cards share one result region. Search-clear uses requestSubmit so it reaches the engine. Existing backend page-size choices/default are unchanged: HR already used 10/25/50/100, whereas the ERP lists that used 15 still use 15.
- Agents: confirmed the mobile Blade button still carried @js inside its component attribute bag. Desktop and mobile delete triggers now use escaped name/URL data attributes; the persistent delete dialog keeps its Alpine state.
- Invoices: the delete dialog moved outside the result region and now exists even when the initial result set is empty, so AJAX can populate an empty list without losing delete actions.
- Delivery / Double MOFA: server-side agent filtering and its totals/export URLs use AJAX. Existing client-side search over loaded rows remains intact; no new backend search behavior was introduced. Create and payment/edit/history modal roots remain intact.
- Notes: tabs and conditional filter forms live in the result region so Trash-to-Active transitions work without reloading or requiring an existing filter form.
- BMET: compact fixed-layout table; PP No, Visa No, and ID No use nowrap, ellipsis for unusually long values, and full-value title tooltips. Long name headers can wrap rather than collide. Filters fit one row at tested desktop widths and wrap on smaller screens. Desktop table overflow was checked at 1280/1440/1920. Existing mobile horizontal scrolling remains available for its compact/all-column modes.

## Changed files beyond the 20 page views

- `resources/js/ajax-filters.js` (new shared engine)
- `resources/js/app.js` (single import)
- `resources/views/erp/bmet/_styles.blade.php` (scoped table/filter polish)
- `tests/Feature/AjaxFilterViewsTest.php` (new response/region/no-JS-button contracts across every converted page)
- `tests/JavaScript/ajax-filters.test.mjs` (five dependency-free query/composition regressions)
- `docs/ajax-filter-audit.md` (this review)
- `public/build/manifest.json` and current app CSS/JS assets. Obsolete app asset hashes are removed by Vite. The existing landing bundle is unchanged. Committing built assets is the repository's explicit shared-hosting convention.

QA fixtures, browser scripts, screenshots, and SQLite databases stay in the ignored `scratch/` directory and are not commit candidates. Browser QA uses an isolated SQLite database and a local-only fixture router; production credentials/data were not used or modified. External CDN assets were stubbed, so external font/icon availability was not tested. Actual destructive submissions were covered by the existing PHP suite; browser QA opened dialogs without deleting fixture records.

## Validation

- Full existing PHP suite: 252 passed, five failed, 1,636 assertions. Failures are all unchanged `ProfileTest` cases targeting the absent `/profile` routes. Routes/controllers for Profile were not changed by this work. Relevant MOFA, BMET, Medical, Visa Stamping, Delivery/Double MOFA, HR, Agents, Invoice, Payment Voucher, attendance, and permission/tenant tests passed.
- New PHP rendering contract: passed, 359 assertions, across all 20 converted pages with ordinary GET and HTML AJAX request headers; includes Notes Trash and excluded Attendance.
- JavaScript tests: five passed with `node --test tests/JavaScript/ajax-filters.test.mjs`.
- Final affected PHP rerun: 164 passed, 1,623 assertions, including the 359-assertion rendering contract.
- Clean-master verification: all five ProfileTest failures reproduced in an isolated archive of master `97f175e`, using its application/test sources and an in-memory SQLite test database.
- Final review found and fixed an IME stale-response regression: composition now immediately cancels pending requests and waits for committed input before fetching. A Chromium reproduction preserved the composing value after the fix; the dependency-free regression covers cancellation and the committed query.
- `npm.cmd run build`: passed. npm's PowerShell wrapper is disabled by host execution policy; npm.cmd runs the same build script.
- `php artisan view:cache`: passed.
- `git diff --check`: passed.
- Browser sweep: all 20 pages were exercised at 1280, 1440, 1920, and 390 pixels for filtering, URL updates, reset, document identity, and cleared busy states. The first sweep passed 78/80; an Agents load overlapped Vite's temporary manifest removal, and mobile HR timed out during concurrent view compilation. Stable-build reruns passed 20/20 checks, covering those two cases and the final BMET/Visa/Super Admin Embassy changes. All 80 page/viewport combinations now have a successful check. Notes reset retains its view parameter; other resets return to the base list URL.
- Targeted browser checks cover filtered pagination, Back/Forward, Visa direction and Enter/sorting, HR hidden-field synchronization/per-page/search-clear, mobile Agent delete dialog and action links after replacement, Medical date change/edit modal, Notes conditional forms, stale responses, fetch failure fallback, and an ordinary no-JS GET submission.
- No JavaScript page errors appeared in the sweep. Targeted checks found no application console/page errors; the deliberately injected HTTP 503 produces the expected resource-error console line while confirming normal GET fallback.

The changes passed pre-commit review, with the five pre-existing Profile suite failures explicitly recorded. The authorized single commit and its final status are reported separately. No push or deployment is authorized.

## Pre-commit git status --short

```text
 D public/build/assets/app-DgjpdNJ9.js
 D public/build/assets/app-T-YRrwKD.css
 M public/build/manifest.json
 M resources/js/app.js
 M resources/views/agency/agents/index.blade.php
 M resources/views/agency/embassy-lists/index.blade.php
 M resources/views/agency/hr/index.blade.php
 M resources/views/agency/notes/index.blade.php
 M resources/views/erp/bmet/_styles.blade.php
 M resources/views/erp/bmet/index.blade.php
 M resources/views/erp/delivery/index.blade.php
 M resources/views/erp/double-mofa/index.blade.php
 M resources/views/erp/due-list/index.blade.php
 M resources/views/erp/invoices/index.blade.php
 M resources/views/erp/medical/index.blade.php
 M resources/views/erp/mofa/index.blade.php
 M resources/views/erp/payment-vouchers/index.blade.php
 M resources/views/erp/profit-loss/index.blade.php
 M resources/views/erp/reports/index.blade.php
 M resources/views/erp/visa-stamping/index.blade.php
 M resources/views/super-admin/agencies/index.blade.php
 M resources/views/super-admin/agents/index.blade.php
 M resources/views/super-admin/embassy-lists/index.blade.php
 M resources/views/super-admin/hr/index.blade.php
 M resources/views/super-admin/subscriptions/index.blade.php
?? docs/ajax-filter-audit.md
?? public/build/assets/app-CJsv2ek2.css
?? public/build/assets/app-hTAtVk5N.js
?? resources/js/ajax-filters.js
?? tests/Feature/AjaxFilterViewsTest.php
?? tests/JavaScript/
```
