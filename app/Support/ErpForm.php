<?php

namespace App\Support;

/**
 * Shared Tailwind class strings for ERP Add/Edit form inputs, so every module
 * renders identical controls. Modal/section/field chrome lives in the
 * x-erp.* Blade components; these constants cover the native <input>/<select>
 * elements that modules still write themselves (they carry module-specific
 * Alpine bindings).
 *
 * This file is listed in tailwind.config.js `content` — keep every class a
 * complete literal (no string building) or the build will purge it.
 */
final class ErpForm
{
    /** Base control. No border colour: pair with BORDER_OK / BORDER_ERROR. */
    public const INPUT = 'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20';

    public const BORDER_OK = 'border-slate-300';

    public const BORDER_ERROR = 'border-red-500 focus:border-red-500 focus:ring-red-500/20';

    /** Auto-calculated / locked field (Age, Left Day, …). */
    public const READONLY = 'block w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-medium text-slate-500 shadow-sm focus:outline-none focus:ring-0';

    /** Red "needs attention" state for an auto/readonly field (e.g. Left Day running low). */
    public const READONLY_ALERT = 'block w-full cursor-not-allowed rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 shadow-sm focus:outline-none focus:ring-0';

    public const TEXTAREA = 'block w-full resize-y rounded-lg border bg-white px-3 py-2 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20';

    /** Small red validation line under a field. */
    public const ERROR = 'mt-1 text-xs font-medium text-red-600';
}
