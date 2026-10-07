<style>
.bmet { --bm-ink:#0f172a; --bm-line:#e5e7eb; --bm-focus:#3b82f6; color:#1f2937; }
.bmet h1,.bmet h2 { color:#172554; }
.bmet :focus-visible { outline:3px solid #3b82f6; outline-offset:2px; }
.bm-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; min-height:38px; padding:7px 13px; border:1px solid var(--bm-line); border-radius:7px; background:white; font-size:13px; font-weight:600; transition:background .2s,box-shadow .2s; }
.bm-btn:hover { background:#f3f4f6; box-shadow:0 2px 5px #0f172a12; }
.bm-primary { background:#047857; border-color:#047857; color:white; }
.bm-primary:hover { background:#065f46; }
.bm-btn:disabled { background:#e5e7eb; color:#6b7280; cursor:not-allowed; box-shadow:none; }
.bm-card { padding:16px; border:1px solid var(--bm-line); border-radius:10px; background:white; }
.bm-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; }
.bm-label { display:block; margin-bottom:6px; font-size:13px; font-weight:600; }
.bm-input { width:100%; min-height:38px; padding:7px 10px; border:1px solid #cbd5e1; border-radius:6px; background:white; color:#1f2937; font-size:14px; transition:border-color .15s,box-shadow .15s; }
.bm-input:focus { border-color:#3b82f6; box-shadow:0 0 0 3px #3b82f61a; }
.bm-input[readonly] { background:#f3f4f6; color:#4b5563; }
.bm-input[aria-invalid=true] { border-color:#ef4444; }
.bm-hint { font-size:12px; color:#6b7280; margin-top:5px; }
.bm-row { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; }
.bm-table { width:100%; border-collapse:collapse; font-size:12px; line-height:1.35; }
.bm-table th { padding:8px 7px; text-align:left; white-space:nowrap; font-size:11.5px; font-weight:600; background:#f3f4f6; }
.bm-table td { padding:7px 7px; border-bottom:.5px solid #e5e7eb; max-width:170px; overflow-wrap:anywhere; vertical-align:middle; }
.bm-table tbody tr:nth-child(even) { background:#f9fafb; }
.bm-table tbody tr:hover { background:#f3f4f6; }
.bm-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 7px; border-radius:5px; font-size:11px; font-weight:600; white-space:nowrap; }
.bm-cleared { color:#065f46; background:#d1fae5; }
.bm-pending { color:#92400e; background:#fef3c7; }
.bm-expired { color:#991b1b; background:#fee2e2; }
.bm-hold { color:#9a3412; background:#fed7aa; }
.bm-total { color:#1e40af; background:#dbeafe; }
.bm-actions { display:flex; gap:2px; }
.bm-actions a,.bm-actions button { display:grid; place-items:center; height:28px; width:28px; border-radius:5px; }
.bm-actions a:hover,.bm-actions button:hover { background:#dbeafe; }
@media(max-width:1199px) { .bm-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:767px) { .bm-stats,.bm-row { grid-template-columns:1fr; } .bm-table:not(.bm-all) { min-width:660px; } .bm-table:not(.bm-all) .bm-secondary { display:none; } }
@media(prefers-reduced-motion:reduce) { .bm-btn,.bm-input { transition:none; } }
</style>
