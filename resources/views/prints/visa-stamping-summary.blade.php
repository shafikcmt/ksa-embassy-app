{{-- Visa Stamping Summary — reference column sequence + Status (no Exp. Date / Left Day / Remarks), A4 landscape. Shell: prints/partials/erp-summary/layout. --}}
@use('App\Support\ErpPrintTheme')
@php($module = 'stamping')
@extends('prints.partials.erp-summary.layout')

@section('table')
<table class="grid">
    <thead>
        <tr>
            {{-- No fixed widths on purpose: mPDF then auto-sizes every column to at least its longest
                 unbreakable token (dates, 10–11 digit numbers, single-word headers) and lets only the
                 name / reference columns wrap between words. Fixed % widths made it split
                 numbers mid-token. --}}
            <th class="l">SL</th>
            <th>Passenger Name</th>
            <th>Father's Name</th>
            <th>Mother's Name</th>
            <th>PP No</th>
            <th>D.O.B</th>
            <th>Age</th>
            <th>Visa No</th>
            <th>Id No</th>
            <th>Mofa No</th>
            <th>Mofa Date</th>
            <th>Issu Visa No</th>
            <th>Issu Date</th>
            <th>Status</th>
            <th>Reference</th>
        </tr>
    </thead>
    <tbody>
        @forelse($entries as $e)
            @php([$bg, $fg] = ErpPrintTheme::status('stamping', $e->status))
            <tr class="{{ $loop->even ? 'alt' : '' }}">
                <td class="num">{{ $loop->iteration }}</td>
                <td>{{ $e->full_name }}</td>
                <td>{{ $e->father_name }}</td>
                <td>{{ $e->mother_name }}</td>
                <td class="c nw">{{ $e->passport_no }}</td>
                <td class="c nw">{{ ErpPrintTheme::date($e->date_of_birth) }}</td>
                <td class="num">{{ $e->age }}</td>
                <td class="c nw">{{ $e->visa_number }}</td>
                <td class="c nw">{{ $e->id_number }}</td>
                <td class="c nw">{{ $e->mofa_number }}</td>
                <td class="c nw">{{ ErpPrintTheme::date($e->mofa_date) }}</td>
                <td class="c nw">{{ $e->issued_visa_number }}</td>
                <td class="c nw">{{ ErpPrintTheme::date($e->issued_date) }}</td>
                <td class="c"><span class="badge" style="background:{{ $bg }}; color:{{ $fg }};">{{ $e->statusLabel() }}</span></td>
                <td>{{ $e->reference }}</td>
            </tr>
        @empty
            <tr><td class="empty" colspan="15"><span style="font-style:normal;">&#8505;</span>&nbsp; No visa stamping records found.</td></tr>
        @endforelse
    </tbody>
    @if($entries->isNotEmpty())
        <tfoot><tr><td colspan="15">Total: {{ number_format($entries->count()) }} {{ \Illuminate\Support\Str::plural('record', $entries->count()) }}</td></tr></tfoot>
    @endif
</table>
@endsection
