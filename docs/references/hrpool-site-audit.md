# ksaofficemng.com — Live Site Audit

**Audited:** 2026-08-30
**Site:** https://www.ksaofficemng.com (branded "Embassy File App")
**Account used:** Agency login — *Anwar General Services Establishment* (RL-1001), email `sabbiranwargeneral@gmail.com`
**Method:** Read-only browsing via Playwright MCP. No records created, edited, deleted, or submitted.
**Screenshots:** `docs/references/site-screenshots/`

> ⚠️ **Important context:** This live site is a **separate/newer product build** (React/Radix-UI SPA, routes like `/hrpool/all`, `/erp`, `/attendance`). Our local **VisaDeskPro** is Laravel 12 + Blade with different routes (`/hr`, `/embassy-lists`, `/dashboard`). This audit treats the live site as the **feature reference/target** we want to reach parity with. It is NOT the same codebase.

---

## 0. Global Navigation Map

Top header (agency portal):

| Nav item | Type | Target(s) |
|---|---|---|
| Dashboard | link | `/` |
| HR Pool | dropdown | `/hrpool/add` (Add New HR), `/hrpool/all` (All HR) |
| Embassy List | dropdown | `/embassy/add` (Add New), `/embassy/all` (All Embassy List) |
| ERP | link | `/erp` (opens ERP suite with its own sidebar — 13 sub-pages) |
| Attendance | link | `/attendance` (tabbed module — 7 tabs) |
| License | link | `/license` |
| P.C. Verify | link | `/verify` (placeholder — "Coming soon") |
| Other Link | dropdown | Qatar Visa Check → external `portal.moi.gov.qa` |
| *Account menu* (agency name) | dropdown | `/settings`, `/staff`, `/change-password`, Sign out |

---

## 1. Dashboard — `/`
*Screenshot: `02-dashboard.png`*

- **Purpose:** Account overview + quick passenger status lookup.
- **Shows:** Agency name + RL number; stat cards — **Total HR**, **Total Embassy List**, **Expiry Date** (license).
- **Features:**
  - "যাত্রীর স্ট্যাটাস দেখুন" — search passenger by **Passport / Name**.
  - **Support** block (phone + tel: link).
  - **Notice Board** (admin broadcast messages).
  - Tutorial cards ("Let's make an embassy file", "Let's make an embassy list").

## 2. HR Pool

### 2a. All HR — `/hrpool/all`
*Screenshot: `01-hrpool-all.png`*
- **Purpose:** Master list of all HR/candidate records.
- **Columns:** `# | Name | MOFA ID | Passport No | Visa No | Sponsor ID | Action | Sponsor Name | Profession | Agent`
- **Actions per row:** PRINT, EDIT, DELETE. Header: **ADD NEW HR**.
- **Filters:** Show N entries (10/25/50/100), Search box, pagination.
- Profession cell shows paired English + Arabic.

### 2b. Add New HR — `/hrpool/add`
*Screenshot: `03-hrpool-add.png`*
- **Purpose:** Full candidate intake form (48 inputs) + **Passport Auto-Fill** (upload passport JPG/PNG ≤8MB → OCR autofill).
- **Fields:** Name*, Father, Mother, Date of Birth, MOFA Application ID (New Mofa / Old Mofa), Place of Birth, Previous Nationality, Present Nationality, Sex (M/F), Marital Status (Married/Unmarried), Sect, Religion (Muslim/Non-muslim), Passport Issue Place, Passport No*, Passport Issue Date, Passport Validity (5/10 Years), Visa No* (10-digit), Visa Date*, Sponsor Name* (EN+AR), Sponsor ID*, Place of Issue (EN+AR), Qualification, Profession (EN+AR + select), Travel Purpose (Work…), Musaned, Wakala, PC Reference No, License Type (select), Duration of Stay, Date of Arrival, Date of Departure, Fingerprint, Experience Certificate (Yes/No), Agent, passport photo upload.
- **Buttons:** RESET, SAVE.

## 3. Embassy List

### 3a. All Embassy List — `/embassy/all`
*Screenshot: `04-embassy-all.png`*
- **Purpose:** List of embassy submission batches.
- **Columns:** `# | Submit Date | No. of New Stamping | No. of Cancel Stamping | No. of Re-stamping | Action | Created at | Last Update`
- **Actions:** ADD NEW EMBASSY LIST; Show N entries; Search.

### 3b. Add New Embassy List — `/embassy/add`
*Screenshot: `05-embassy-add.png`*
- **Purpose:** Build a batch by adding passports under a submission date.
- **Fields:** Submit Date; category toggle (**Re-stamping / New Stamping / Cancellation**); Passport No + **ADD**.
- **Buttons:** ADD (add row), CREATE LIST.

## 4. ERP Suite — `/erp/*`
Full accounting/operations ERP with its own left sidebar. All pages bilingual (English + Bangla). Global toolbar: **Daily Summary PDF**, month selector + **Monthly Summary PDF**, passenger status, **Backup Excel**.

### 4.1 ERP Dashboard — `/erp`
*Screenshot: `06-erp-dashboard.png`*
- KPI blocks: Yearly summary (Total MOFA / Delivery / Stamping / Expense), Previous-month card (MOFA, Stamping, Manpower, Delivery, Income, Expense, Due, Profit/Loss, Starting/Ending Balance), Monthly card (Today MOFA, This-month MOFA/Stamping/Delivery, Pending Delivery, Income, Expense, Due, Profit/Loss, Opening/Final Balance).
- **Charts:** MOFA Growth, Income vs Expense, Delivery Trend, Due Collection.
- **Breakdown drilldowns:** `/erp/breakdown/income`, `/erp/breakdown/expense`, `/erp/breakdown/profit`, `/erp/breakdown/balance` (*see `income breakdown` cols: Date, Source, Name/Agent, Note, Amount*).
- Smart Notes preview widget.

### 4.2 MOFA Entry — `/erp/mofa`
*Screenshot: `07-erp-mofa.png`*
- **Purpose:** Log MOFA applications.
- **Form fields:** MOFA Date, MOFA Number, Visa Serial, Full Name*, Passport No*, Reference Name, Payment Method (Company Account / Card Payment / No Payment), WhatsApp Number, Payment Note.
- **Columns:** `Y# | M# | Date | MOFA # | Visa Serial | Name | Passport | Reference | Payment`
- **Actions:** Import Excel, Print, Add Entry. Range filter (This/Last Month, This/Last Year, All Time). Live counter "Stamping বাকি".

### 4.3 Double MOFA — `/erp/double-mofa`
*Screenshot: `08-erp-double-mofa.png`*
- **Purpose:** Passports with MOFA done more than once; tracks extra billing (@ BDT 3,000/person).
- **KPIs:** Total Double MOFA, Unpaid, Outstanding amount.
- **Columns:** `Total# | MOFA Y# | MOFA Date | Name | Visa Serial | Passport | Reference | Paid | Unpaid | Status`
- **Actions:** Print, Receive Payment.

### 4.4 Stamping — `/erp/stamping`
*Screenshot: `09-erp-stamping.png`*
- **Purpose:** Visa stamping tracking.
- **Fields:** Date, Visa Serial, Full Name*, Passport No*, Visa Number, ID Number, Reference, Status (Pending/Processing/Stamped).
- **Columns:** `Y# | M# | Date | Visa Serial | Name | Passport | Visa No | ID | Reference | Status`
- **Actions:** Import Excel, Print, Add Entry. Live "Manpower বাকি".

### 4.5 Manpower Complete — `/erp/manpower`
*Screenshot: `10-erp-manpower.png`*
- **Fields:** Date, Customer Name*, Passport Number*, Agent Name.
- **Columns:** `Total Serial | Date | Customer Name | Passport Number | Agent Name`
- **Actions:** Print, Add Entry. Live "Delivery বাকি".

### 4.6 Delivery — `/erp/delivery`
*Screenshot: `11-erp-delivery.png`*
- **Purpose:** File delivery + payment collection.
- **Fields:** Delivery Date, Full Name*, Passport No*, Visa Serial, Reference, Total Amount, Paid Amount, Status (Pending/Ready/Delivered).
- **Columns:** `M# | Date | Name | Passport | Visa | Ref | Total | Paid | Due | Status`
- **Actions:** Fix Serial, Print, Receive Payment, Import Excel, Add Delivery. Totals: Collected / Due.

### 4.7 Agent Khata (Ledger) — `/erp/agent-khata`
*Screenshot: `12-erp-agent-khata.png`*
- **Purpose:** Per-agent ledger + payment history + advances.
- **KPIs:** Total Collection, Agent count, Unused advance.
- **Ledger columns:** `Agent | MOFA Paid | MOFA Due | Delivery Paid | Delivery Due | Net Due | Actions`
- **Payment history columns:** `Date | Agent | Category | Received | Allocated | Cleared | Remainder | Notes`
- **Actions:** Receive Payment.

### 4.8 Expenses — `/erp/expenses`
*Screenshot: `13-erp-expenses.png`*
- **KPIs:** Today's Expense, Monthly Expense; "This Month — by Category" breakdown.
- **Fields:** Date, Category, Amount*, Description.
- **Categories:** Office Rent, Salary, Transport, Bank Charge, Embassy Cost, Internet, Marketing, Manpower, Snack, Water Bill, Office Stationary, Kitchen Stationary, Mofa Card Rent, Mofa Dollar, Electric Bill, Clean, BORAK TOWER, BMET MAMLA, Other.
- **Columns:** `Date | Category | Description | Amount`
- **Actions:** Print, Import Excel, Add Expense.

### 4.9 Due List — `/erp/dues`
*Screenshot: `14-erp-dues.png`*
- **KPIs:** Total Outstanding, Overdue Accounts, Total Customers. Tabs: All / Office Rent Due / Delivery Due / MOFA Due / General Due.
- **Fields:** Agent, Category* (Office Rent/Delivery/MOFA/General Due), Customer/Party Name, Reference, Passport No, WhatsApp, Total Amount*, Paid Amount, Due Date, Notes.
- **Columns:** `Category | Customer | Reference | Passport | Total | Paid | Remaining | Due Date`
- **Actions:** Receive Payment, Import Excel, Add Due.

### 4.10 Profit / Loss — `/erp/profit`
*Screenshots: `15a-erp-profit-gate.png`, `15b-erp-profit.png`*
- **Owner-only gate:** "I am the owner — show" (guarded by a Security Code set in ERP Settings). Good privacy pattern.
- **KPIs:** Daily / Monthly / Yearly P&L; 12-Month Profit Trend chart; Best / Worst month.
- Toggle: "Visible to all".

### 4.11 Reports — `/erp/reports`
*Screenshot: `16-erp-reports.png`*
- **Range presets:** Today, 7d, 30d, Month, Year, Custom (from/to).
- **KPIs:** MOFA, Stamping, Delivery, Net, Billed, Collected, Due, Expense; Expense by Category.
- **Exports:** CSV, PDF.

### 4.12 ERP Settings — `/erp/settings`
*Screenshot: `17-erp-settings.png`*
- **Opening Balance** (historical starting balance, +/-).
- **Profit/Loss Security Code** (owner sets secret code; blank = no protection).
- **Profit/Loss visibility** toggle (hides income/P&L/balance from dashboard; owner-only).

### 4.13 Smart Notes — `/erp/notes`
*Screenshot: `18-erp-notes.png`*
- **Purpose:** Notes, reminders, checklists.
- **KPIs:** Total Notes, Pinned, Upcoming Reminders.
- **Categories:** General, Important, Office, Accounts, Embassy, MOFA, Agent, Client, Personal.
- **Priority:** Urgent/High/Medium/Low. **Status:** Pending/Completed/Private.
- **Views:** Active, Reminders, Archived, Trash. Sort options. Print, New Note.

## 5. Attendance — `/attendance`
*Screenshots: `19-attendance-dashboard.png`, `20-attendance-reports.png`*
Tabbed module (single route, client-side tabs): **Dashboard, Employees, Shifts, Settings, Leave, Holidays, Reports**.

- **Dashboard:** Today at a glance (Present, Late, Absent, On leave, On-time %, Check-ins today); This-week Present vs Late chart; today's check-ins list.
- **Employees:** HR profiles linked to staff logins (so they can check in). Add employee.
- **Shifts:** Company default + alternate shifts. New shift.
- **Settings:** Office start/end, Grace (min), Auto-absent time, Half-day after (min late), Overtime after, Timezone (Dhaka/Riyadh/Dubai/Qatar/Kuwait/UTC), Weekend days, owner email alerts (On late / absent / check-in / check-out).
- **Leave:** Leave types (Paid…) + requests (Employee | Type | Dates | Days | Status | Action); Pending/Approved/Rejected/All.
- **Holidays:** Company holiday calendar (Date, Title).
- **Reports:** Date range + employee filter; export Excel / PDF / CSV.

## 6. License — `/license`
*Screenshot: `21-license.png`*
- **Purpose:** Show license status + renewal instructions/payment info.
- **License Details:** License Holder Name, Company, Address, RL Number, License Code, Last Renewal Date, License Expiry Date, Next Renewal Due Date, Days Remaining, Active badge.
- **Payment Information:** Renewal Amount (৳500), bKash, Nagad, Bank Account details.
- **Renewal Instructions** (text).

## 7. P.C. Verify — `/verify`
- Placeholder page: "Coming soon."

## 8. Account / Settings

### 8a. Settings — `/settings`
*Screenshot: `22-settings.png`*
- **Fields:** Name, Company, Company Type (Recruiting Agency / Consultancy Agency), RL No, Address, Official Email, Phone, Referral Code.
- **Buttons:** Reset, Update.

### 8b. Staff Accounts — `/staff`
*Screenshot: `23-staff-add-permissions.png`*
- **Purpose:** Create sub-users with **granular per-page permissions**.
- **Add Staff form:** Email, Password + permission checkboxes:
  - **General:** HR Pool, Embassy List, Agent, License, PC Verify, Other Link
  - **ERP:** ERP (show tab — required), ERP — All Pages, ERP → Dashboard (all sections) + sub (Yearly / Previous Month / Monthly-Today), MOFA, Double MOFA, Stamping, Manpower Complete, Delivery, Agent Khata, Expenses, Dues, Profit/Loss, Reports, Settings, Smart Notes.
- **Buttons:** Add Staff, Create.

### 8c. Change Password — `/change-password`
- **Fields:** Current Password, New Password, Confirm New Password (min 6 chars).
- **Buttons:** Cancel, Update Password.

## 9. Other Link
- **Qatar Visa Check** → external `https://portal.moi.gov.qa/...` (opens MOI Qatar visa enquiry).

---

## Quick page inventory (URLs)

```
/                     Dashboard
/hrpool/all           All HR
/hrpool/add           Add New HR (+ passport OCR auto-fill)
/embassy/all          All Embassy List
/embassy/add          Add New Embassy List
/erp                  ERP Dashboard
/erp/mofa             MOFA Entry
/erp/double-mofa      Double MOFA
/erp/stamping         Stamping
/erp/manpower         Manpower Complete
/erp/delivery         Delivery
/erp/agent-khata      Agent Khata (ledger)
/erp/expenses         Expenses
/erp/dues             Due List
/erp/profit           Profit / Loss (owner-only)
/erp/reports          Reports
/erp/settings         ERP Settings
/erp/notes            Smart Notes
/erp/breakdown/{income|expense|profit|balance}   drilldowns
/attendance           Attendance (tabs: Dashboard/Employees/Shifts/Settings/Leave/Holidays/Reports)
/license              License info + renewal
/verify               P.C. Verify (coming soon)
/settings             Account/company settings
/staff                Staff sub-users + permissions
/change-password      Change password
```

---

# Part 2 — Comparison vs current VisaDeskPro (local Laravel project)

Sources checked: `routes/web.php`, `routes/agency.php`, `routes/super-admin.php`, `app/Models/*`, `app/Http/Controllers/**`, `database/migrations/*`, `app/Models/User.php`.

## What ALREADY EXISTS locally (feature parity or close)

| Live feature | Local equivalent | Notes |
|---|---|---|
| Dashboard + stats | `Agency\DashboardController`, `DashboardStatsService` | Has Total HR / Embassy / license expiry logic already. |
| HR Pool (all/add/edit/print) | `Agency\HrProfileController` + `hr_profiles`, `passports`, `visas`, `clearances`, `hr_other_infos` | Full CRUD + PDF print/download. **Richer schema than live** (paired EN/AR, print fields). |
| Passport lookup | `hr.lookup-by-passport` route | Exists (used for embassy quick-add). Live has OCR auto-fill on upload — **local has lookup, not OCR**. |
| Embassy List (all/add/edit/print/finalize/cancel) | `Agency\EmbassyListController` + `embassy_lists`, `embassy_list_items` | `embassy_lists` already has `total_new`, `total_restamping`, `total_cancellation` — **matches live columns**. |
| Agents | `Agency\AgentController` + `agents` | Full CRUD. (Live de-emphasizes Agent in header but still uses it in ERP/permissions.) |
| Settings (company profile) | `Agency\SettingsController` + `agencies` (`owner_name`, `print_logo`, `company type`?) | Exists. Verify "Company Type" + "Referral Code" fields (see gaps). |
| License data | `agencies.license_number/rl_number/license_expiry_date` + `CheckLicenseExpiry` command + `license-expiring` email | Data exists; **no dedicated agency `/license` view page**. |
| Notices / Notice Board | `Notice` model + `NotificationComposer` | Exists. |
| Subscriptions / Plans | `Subscription`, `Plan` + both portals | Exists (BDT currency). Live "renewal amount ৳500" maps to plan pricing. |
| Change password | Breeze `PasswordController` / `ProfileController` | Exists. |
| Roles foundation | `User` uses Spatie `HasRoles`; `agency_admin` / `agency_staff` roles; policies for HR/Embassy/Agent | **Foundation exists**, but no Staff CRUD UI or granular per-page permission matrix. |
| Multi-tenancy | `EnsureAgencyAccess` middleware, `agency_id` on every table, policies | Solid. Any new module MUST follow this. |

## What is MISSING locally (in live, not in ours)

1. **ERP suite** (biggest gap) — MOFA Entry, Double MOFA, Stamping, Manpower Complete, Delivery, Agent Khata (ledger), Expenses, Due List, Profit/Loss, Reports, ERP Settings, Smart Notes, breakdown drilldowns, Daily/Monthly summary PDFs, Excel backup, Import Excel. **No tables, models, controllers, or routes exist for any of this.**
2. **Attendance module** — Employees(check-in), Shifts, Leave, Holidays, Attendance Settings, Reports. None exists locally.
3. **Staff Accounts UI + granular permissions** — sub-user creation with per-page checkboxes. Role scaffolding exists (Spatie) but no UI/permission map.
4. **Dedicated License page** (`/license`) — renewal instructions + payment channels (bKash/Nagad/Bank). Data exists; view does not.
5. **Passport OCR auto-fill** on HR add (image → field extraction). Local only has passport-number lookup.
6. **P.C. Verify** — placeholder even on live ("Coming soon"). Lowest priority.
7. Minor: **Company Type** (Recruiting/Consultancy) + **Referral Code** on Settings; **Qatar Visa Check** external link.

## Possible DUPLICATES / overlaps to avoid

- **Do NOT add new embassy stamping columns** — `embassy_lists.total_new/total_restamping/total_cancellation` already exist.
- **Do NOT add license columns** — `agencies` already has `license_number`, `rl_number`, `license_expiry_date`. A `/license` page only needs a view + controller reading existing data (+ maybe renewal payment settings, which may belong in super-admin `settings`).
- **Do NOT add a new roles system** — reuse Spatie `HasRoles` already wired into `User`; extend with permissions rather than a parallel table.
- **HR "MOFA ID / Sponsor / Profession EN+AR"** already exist across `visas`/`clearances`/`hr_other_infos` — check `PrintDataMapper` and the reference-fields migrations before adding MOFA fields for ERP; ERP MOFA Entry may reference existing HR data instead of duplicating.

---

# Part 3 — Prioritized changed-file PLAN (no code yet — awaiting approval)

> Grouped by risk. **Medium/High require explicit approval before any code.** All new tables MUST include `agency_id` (FK, cascade), be scoped by `EnsureAgencyAccess`, and never receive a null `agency_id`.

## 🟢 Low risk — new UI/view only (no schema change)

- **L1. Agency `/license` page** — reads existing `agencies` license fields.
  - `routes/agency.php` (+1 route), `app/Http/Controllers/Agency/LicenseController.php` (new), `resources/views/agency/license/index.blade.php` (new).
  - Renewal payment channel values (bKash/Nagad/Bank/amount) → read from super-admin `settings` (reuse `Setting` model) — no new columns.
- **L2. "Other Link → Qatar Visa Check"** — add external link in agency nav partial (`resources/views/layouts/agency*` / nav partial). Trivial.
- **L3. Dashboard "passenger status" quick-search** — if not already present, add a search box to `agency/dashboard.blade.php` hitting existing `hr.lookup-by-passport`. View + maybe one controller method.

## 🟡 Medium risk — new fields on existing tables / new isolated tables that don't touch tenancy logic

- **M1. Settings: Company Type + Referral Code**
  - Migration: `add_company_type_referral_to_agencies` (only if columns absent — **check first**). `Agency` fillable, `SettingsController@update` validation, settings blade.
- **M2. Staff Accounts UI + granular permissions** (uses existing Spatie)
  - Seeder/config for permission names per page; `resources/views/agency/staff/*` (index/create); `Agency/StaffController` (new); routes; extend `EnsureAgencyAccess`/nav to hide pages by permission. No schema change if using Spatie tables (already migrated).
  - *Medium because it touches auth/visibility — must keep super-admin separate and scope staff to their own `agency_id`.*
- **M3. Smart Notes** (self-contained)
  - New table `smart_notes` (agency_id, user_id, title, body, category, priority, status, pinned, reminder_at, archived_at, deleted_at). Model + `Agency/NoteController` + views. Isolated; no cross-module impact.
- **M4. Attendance Settings + Holidays + Shifts + Leave types** (config-like tables)
  - New tables scoped by agency: `attendance_settings`, `shifts`, `holidays`, `leave_types`. Low blast radius (no money logic). Grouped here because it's additive.
- **M5. Passport OCR auto-fill** on HR add
  - New service (OCR provider/API) + endpoint; view JS on `hr/create`. No schema change (writes into existing HR fields). Medium due to external dependency/cost.

## 🔴 High risk — new modules / financial + multi-tenant heavy

- **H1. ERP core ledgers** — new tables, all `agency_id`-scoped:
  `mofa_entries`, `double_mofa` (or derived), `stampings`, `manpower_completions`, `deliveries`, `expenses`, `dues`, `agent_payments` (khata), `erp_settings` (opening balance, P/L security code, P/L visibility).
  - New `app/Http/Controllers/Erp/*` controllers, `app/Models/Erp/*`, `routes/erp.php` (included from web), ERP layout + sidebar views, `app/Services/ErpAccountingService.php` for balance/profit calc.
  - **High risk:** money math, multi-tenant isolation, owner-only Profit/Loss gating, opening-balance carry, cross-links (MOFA→Stamping→Manpower→Delivery "বাকি" counters).
- **H2. ERP Dashboard + Reports + Breakdowns + PDF/Excel** — depends on H1.
  - Chart data endpoints, `/erp/breakdown/*`, Daily/Monthly Summary PDF (mPDF — reuse existing PDF stack), CSV/Excel export (Import Excel too), Backup Excel.
- **H3. Attendance transactional core** — `employees` (link to staff `users`), `attendance_records` (check-in/out, late/absent calc), `leave_requests`.
  - Depends on M4 config tables + M2 staff logins. **High** because check-in identity ties to staff auth + tenancy.
- **H4. Profit/Loss owner-only security** — owner code verification flow, visibility toggle affecting dashboard. Ties into H1 `erp_settings`. Sensitive.

## Suggested sequencing (if approved)
1. L1–L3 (quick wins, zero schema risk).
2. M1, M3 (settings fields + Smart Notes — safe, self-contained).
3. M2 (Staff + permissions) — unlocks Attendance identity + ERP per-page gating.
4. H1 → H2 (ERP data model first, then dashboards/reports/PDF).
5. M4 → H3 (Attendance config, then transactions).
6. M5 (OCR), H4 (P/L security), P.C. Verify last.

**⛔ No code will be written for Medium/High items until you approve specific ones.**

