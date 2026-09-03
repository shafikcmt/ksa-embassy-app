@extends('layouts.agency-app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

@php
    $authUser  = auth()->user();
    $isAdmin   = method_exists($authUser, 'isAgencyAdmin') ? $authUser->isAgencyAdmin() : true;
    $firstName = \Illuminate\Support\Str::of($authUser->name)->trim()->explode(' ')->first() ?: 'there';
    $hour      = (int) now()->format('H');
    $greeting  = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    $canHr   = $authUser->can('create', \App\Models\HrProfile::class);
    $canList = $authUser->can('create', \App\Models\EmbassyList::class);

    // Banner usage meters (admins with a plan).
    $hrLimit  = $subscription?->plan->max_hr ?? 0;
    $hrPct    = $hrLimit > 0 ? min(100, round($stats['total_hr'] / $hrLimit * 100)) : 0;
    $pdfLimit = $subscription?->plan->max_pdf_monthly ?? 0;
    $pdfPct   = $pdfLimit > 0 ? min(100, round($stats['pdf_downloads_month'] / $pdfLimit * 100)) : 0;

    // ── 3rd stat card: subscription expiry (admins) else licence expiry ──
    if ($isAdmin && $subscription) {
        $exDays  = $subscription->daysRemaining();
        $exDate  = optional($subscription->end_date)->format('d M Y') ?? '—';
        $exLabel = 'Subscription';
        $exHref  = route('subscription.expired');
    } else {
        $exDays  = $agency?->license_expiry_date ? (int) now()->startOfDay()->diffInDays($agency->license_expiry_date, false) : null;
        $exDate  = optional($agency?->license_expiry_date)->format('d M Y') ?? '—';
        $exLabel = 'Licence';
        $exHref  = null;
    }
    $exTone = $exDays === null ? 'slate' : ($exDays <= 3 ? 'rose' : ($exDays <= 7 ? 'amber' : 'emerald'));

    // Literal Tailwind bundles (kept as full strings so the JIT scanner sees them).
    $tones = [
        'brand'   => ['from-brand-50 to-indigo-100/70 ring-brand-100',   'bg-brand-500/15 text-brand-600',     'text-brand-600'],
        'violet'  => ['from-violet-50 to-fuchsia-100/70 ring-violet-100', 'bg-violet-500/15 text-violet-600',   'text-violet-600'],
        'emerald' => ['from-emerald-50 to-teal-100/70 ring-emerald-100',  'bg-emerald-500/15 text-emerald-600', 'text-emerald-600'],
        'amber'   => ['from-amber-50 to-orange-100/70 ring-amber-100',    'bg-amber-500/15 text-amber-600',     'text-amber-600'],
        'rose'    => ['from-rose-50 to-red-100/70 ring-rose-100',         'bg-rose-500/15 text-rose-600',       'text-rose-600'],
        'slate'   => ['from-slate-50 to-slate-100 ring-slate-200',        'bg-slate-500/15 text-slate-600',     'text-slate-600'],
    ];

    $statCards = [
        ['tone' => 'brand',  'icon' => 'bi-person-vcard', 'value' => $stats['total_hr'],            'label' => 'Total HR Records', 'sub' => $stats['active_hr'].' active', 'href' => route('hr.index')],
        ['tone' => 'violet', 'icon' => 'bi-list-ol',      'value' => $stats['total_embassy_lists'], 'label' => 'Embassy Lists',    'sub' => $stats['embassy_lists_month'].' this month', 'href' => route('embassy-lists.index')],
        ['tone' => $exTone,  'icon' => 'bi-patch-check',  'value' => $exDays === null ? '—' : $exDays.'d', 'label' => $exLabel,     'sub' => 'Expires '.$exDate, 'href' => $exHref],
    ];

    // ── Notice Board (active, global or this agency) ──
    $notices = \App\Models\Notice::active()->forAgency($agency?->id)->latest()->take(4)->get();
    $noticeTone = [
        'info'    => ['bg-brand-50 text-brand-600', 'bi-info-circle'],
        'warning' => ['bg-amber-50 text-amber-600', 'bi-exclamation-triangle'],
        'danger'  => ['bg-rose-50 text-rose-600', 'bi-exclamation-octagon'],
        'success' => ['bg-emerald-50 text-emerald-600', 'bi-check-circle'],
    ];

    // ── Support contact (global setting) ──
    $supportEmail = \App\Models\Setting::get('support_email', null, '');

    // ── Upcoming reminders (real dates only) ──
    $reminders = [];
    if ($isAdmin && $subscription && $subscription->end_date) {
        $d = (int) now()->startOfDay()->diffInDays($subscription->end_date, false);
        $reminders[] = ['icon' => 'bi-gem', 'dot' => $d <= 3 ? 'bg-rose-500' : ($d <= 7 ? 'bg-amber-500' : 'bg-emerald-500'),
            'title' => 'Subscription '.($d < 0 ? 'expired' : 'renewal'), 'date' => $subscription->end_date, 'days' => $d, 'href' => route('subscription.expired')];
    }
    foreach (($upcomingExpiries ?? collect()) as $p) {
        $d = (int) now()->startOfDay()->diffInDays($p->expiry_date, false);
        $reminders[] = ['icon' => 'bi-passport', 'dot' => $d <= 30 ? 'bg-rose-500' : 'bg-amber-500',
            'title' => 'Passport · '.($p->hrProfile?->full_name_en ?? 'Candidate'), 'date' => $p->expiry_date, 'days' => $d,
            'href' => route('hr.index', ['filter' => 'passport_expiring'])];
    }
    $reminders = collect($reminders)->sortBy('days')->take(5)->values();
@endphp

{{-- ════════ ① IDENTITY BANNER ════════ --}}
<div class="js-fade-card mb-5 overflow-hidden rounded-2xl bg-gradient-to-br from-brand-600 via-indigo-600 to-violet-700 p-6 text-white shadow-lg shadow-indigo-600/20 sm:p-7">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-medium text-white/70">{{ $greeting }}, {{ $firstName }} 👋</p>
            <h1 class="mt-1 truncate text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $agency?->name ?? 'Your Agency' }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-white/80">
                @if($agency?->rl_number)
                    <span><i class="bi bi-hash"></i> RL <span class="font-semibold text-white">{{ $agency->rl_number }}</span></span>
                @endif
                <span><i class="bi bi-patch-check"></i> Licence <span class="font-semibold text-white">{{ $agency?->license_number ?? '—' }}</span></span>
            </div>

            @if($isAdmin && $subscription)
                <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold ring-1 ring-white/20">
                        <i class="bi bi-gem"></i> {{ $subscription->plan->name ?? 'Plan' }} · {{ $subscription->daysRemaining() }} days left
                    </span>
                    <div class="w-40">
                        <div class="flex justify-between text-[0.7rem] text-white/70"><span>HR Profiles</span><span>{{ $stats['total_hr'] }}/{{ $hrLimit ?: '∞' }}</span></div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-white/20"><div class="h-full rounded-full bg-white" style="width: {{ $hrPct }}%"></div></div>
                    </div>
                    <div class="w-40">
                        <div class="flex justify-between text-[0.7rem] text-white/70"><span>PDFs (month)</span><span>{{ $stats['pdf_downloads_month'] }}/{{ $pdfLimit ?: '∞' }}</span></div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-white/20"><div class="h-full rounded-full bg-white" style="width: {{ $pdfPct }}%"></div></div>
                    </div>
                </div>
            @endif
        </div>

        <div class="flex shrink-0 flex-wrap gap-2">
            @if($canHr)
                <a href="{{ route('hr.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-white px-4 text-sm font-semibold text-brand-700 shadow-sm transition hover:bg-white/90"><i class="bi bi-plus-lg"></i> Add HR</a>
            @endif
            @if($canList)
                <a href="{{ route('embassy-lists.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-white/15 px-4 text-sm font-semibold text-white ring-1 ring-white/30 transition hover:bg-white/25"><i class="bi bi-list-ol"></i> Embassy List</a>
            @endif
            <a href="{{ route('hr.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-white/15 px-4 text-sm font-semibold text-white ring-1 ring-white/30 transition hover:bg-white/25"><i class="bi bi-printer"></i> Print</a>
        </div>
    </div>
</div>

{{-- ════════ ② STAT CARDS (3) ════════ --}}
<div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
    @foreach($statCards as $c)
        @php [$wrap, $badge, $subCls] = $tones[$c['tone']]; @endphp
        <{{ $c['href'] ? 'a' : 'div' }} @if($c['href']) href="{{ $c['href'] }}" @endif
            class="js-fade-card group block rounded-xl bg-gradient-to-br {{ $wrap }} p-5 ring-1 transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="grid h-11 w-11 place-items-center rounded-full {{ $badge }}"><i class="bi {{ $c['icon'] }} text-lg"></i></span>
                @if($c['href'])<i class="bi bi-arrow-up-right text-slate-300 transition group-hover:text-slate-400"></i>@endif
            </div>
            <div class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ $c['value'] }}</div>
            <div class="text-sm font-medium text-slate-600">{{ $c['label'] }}</div>
            <div class="mt-0.5 text-xs {{ $subCls }}">{{ $c['sub'] }}</div>
        </{{ $c['href'] ? 'a' : 'div' }}>
    @endforeach
</div>

{{-- ════════ ③ PASSENGER STATUS SEARCH ════════ --}}
<div class="js-fade-card mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-3.5">
        <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-search text-brand-600"></i> Passenger Status</h2>
        <p class="mt-0.5 text-xs text-slate-500">Search your candidates by name, passport, visa or MOFA number.</p>
    </div>
    <div class="p-4">
        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col gap-2 sm:flex-row">
            <div class="flex flex-1 items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 transition focus-within:border-brand-300 focus-within:bg-white focus-within:ring-2 focus-within:ring-brand-100">
                <i class="bi bi-person-badge text-slate-400"></i>
                <input type="text" name="pq" value="{{ $pq ?? '' }}" placeholder="Passport / Name / Visa / MOFA…"
                       class="h-11 w-full border-0 bg-transparent p-0 text-sm text-slate-700 placeholder:text-slate-400 focus:ring-0">
            </div>
            <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 px-5 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 transition hover:shadow-md">
                <i class="bi bi-search"></i> Search
            </button>
            @if(($pq ?? '') !== '')
                <a href="{{ route('dashboard') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:border-slate-300">
                    <i class="bi bi-x-lg"></i> Clear
                </a>
            @endif
        </form>

        @if(($pq ?? '') !== '')
            @if($passengerResults && $passengerResults->count())
                <div class="mt-4 overflow-x-auto rounded-xl border border-slate-100">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-2.5">Name</th>
                            <th class="px-4 py-2.5">Passport No</th>
                            <th class="px-4 py-2.5">Visa No</th>
                            <th class="hidden px-4 py-2.5 sm:table-cell">Nationality</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="hidden px-4 py-2.5 md:table-cell">Agent</th>
                            <th class="px-4 py-2.5 text-right">Action</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($passengerResults as $p)
                                <tr class="transition-colors hover:bg-brand-50/50">
                                    <td class="px-4 py-2.5">
                                        <a href="{{ route('hr.show', $p) }}" class="font-semibold text-slate-800 hover:text-brand-600">{{ $p->full_name_en }}</a>
                                        @if($p->full_name_ar)<div class="text-xs text-slate-400" dir="rtl">{{ $p->full_name_ar }}</div>@endif
                                    </td>
                                    <td class="px-4 py-2.5 font-mono text-xs text-slate-600">{{ $p->passport?->passport_number ?: '—' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs text-slate-600">{{ $p->visa?->visa_number ?: '—' }}</td>
                                    <td class="hidden px-4 py-2.5 text-slate-500 sm:table-cell">{{ $p->nationality ?: '—' }}</td>
                                    <td class="px-4 py-2.5"><x-ui.status-badge :status="$p->status" /></td>
                                    <td class="hidden px-4 py-2.5 text-slate-500 md:table-cell">{{ $p->agent?->name ?: '—' }}</td>
                                    <td class="px-4 py-2.5 text-right">
                                        <a href="{{ route('hr.show', $p) }}" title="View" class="grid h-7 w-7 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-slate-400">Showing {{ $passengerResults->count() }} result(s) for “{{ $pq }}”.</p>
            @else
                <div class="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-8 text-center">
                    <i class="bi bi-search mb-2 block text-2xl text-slate-300"></i>
                    <p class="text-sm font-medium text-slate-600">No passenger found for “{{ $pq }}”.</p>
                    <p class="mt-0.5 text-xs text-slate-400">Try a different name, passport, visa or MOFA number.</p>
                </div>
            @endif
        @endif
    </div>
</div>

{{-- ════════ ④ SUPPORT · NOTICE BOARD · REMINDERS ════════ --}}
<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

    {{-- Support --}}
    <div class="js-fade-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-3.5">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-life-preserver text-emerald-500"></i> Support</h2>
        </div>
        <div class="p-5">
            <p class="text-sm text-slate-600">Questions or an issue with your account? Our team is here to help.</p>
            @if($supportEmail)
                <a href="mailto:{{ $supportEmail }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-100 transition hover:bg-emerald-100">
                    <i class="bi bi-envelope"></i> {{ $supportEmail }}
                </a>
            @else
                <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-400">
                    <i class="bi bi-info-circle"></i> Support contact will appear here once configured.
                </div>
            @endif
            <div class="mt-3 text-xs text-slate-400"><i class="bi bi-clock"></i> Typical response within 1 business day.</div>
        </div>
    </div>

    {{-- Notice Board --}}
    <div class="js-fade-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-3.5">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-megaphone text-brand-600"></i> Notice Board</h2>
        </div>
        <div class="p-3">
            @forelse($notices as $n)
                @php [$nCls, $nIcon] = $noticeTone[$n->type] ?? $noticeTone['info']; @endphp
                <div class="flex items-start gap-3 rounded-lg px-2.5 py-2.5 transition hover:bg-slate-50">
                    <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg {{ $nCls }}"><i class="bi {{ $nIcon }}"></i></span>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-slate-800">{{ $n->title }}</div>
                        <div class="mt-0.5 line-clamp-2 text-xs leading-snug text-slate-500">{{ $n->body }}</div>
                        <div class="mt-1 text-[0.68rem] text-slate-400">{{ $n->created_at?->format('d M Y') }}</div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <i class="bi bi-megaphone mb-2 block text-2xl text-slate-300"></i>
                    <p class="text-sm text-slate-500">No notices right now.</p>
                    <p class="mt-0.5 text-xs text-slate-400">Announcements will appear here.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Reminders --}}
    <div class="js-fade-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-3.5">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-bell text-amber-500"></i> Upcoming &amp; Reminders</h2>
        </div>
        <div class="p-3">
            @if($reminders->count())
                <ul class="space-y-1">
                    @foreach($reminders as $r)
                        <li>
                            <a @if($r['href']) href="{{ $r['href'] }}" @endif class="flex items-center gap-3 rounded-lg px-2.5 py-2 transition hover:bg-slate-50 @if(!$r['href']) cursor-default @endif">
                                <span class="relative flex h-2.5 w-2.5 shrink-0">
                                    <span class="absolute inline-flex h-full w-full rounded-full {{ $r['dot'] }} opacity-40"></span>
                                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full {{ $r['dot'] }}"></span>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-slate-700"><i class="bi {{ $r['icon'] }} mr-1 text-slate-400"></i>{{ $r['title'] }}</span>
                                    <span class="block text-xs text-slate-400">{{ $r['date']->format('d M Y') }}</span>
                                </span>
                                <span @class([
                                    'shrink-0 rounded-full px-2 py-0.5 text-[0.68rem] font-semibold',
                                    'bg-rose-50 text-rose-600'    => $r['days'] < 0 || $r['days'] <= 7,
                                    'bg-amber-50 text-amber-600'  => $r['days'] > 7 && $r['days'] <= 30,
                                    'bg-slate-100 text-slate-500' => $r['days'] > 30,
                                ])>
                                    {{ $r['days'] < 0 ? abs($r['days']).'d ago' : 'in '.$r['days'].'d' }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="px-4 py-8 text-center">
                    <i class="bi bi-calendar-check mb-2 block text-2xl text-emerald-400"></i>
                    <p class="text-sm text-slate-500">No upcoming reminders.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.fadeInCards) { window.fadeInCards('.js-fade-card'); }
    });
</script>
@endpush

@endsection
