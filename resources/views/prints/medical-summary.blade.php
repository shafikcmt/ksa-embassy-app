{{-- Medical Summary — reference column sequence, A4 landscape. Shell: prints/partials/erp-summary/layout. --}}
@use('App\Support\ErpPrintTheme')
@php($module = 'medical')
@extends('prints.partials.erp-summary.layout')

@section('table')
<table class="grid">
    <thead>
        <tr>
            {{-- No fixed widths: mPDF auto-sizes each column to at least its longest unbreakable token
                 (dates, numbers, one-word headers) and wraps only free text. --}}
            <th class="l">SL</th>
            <th>Name</th>
            <th>Father's Name</th>
            <th>Passport No</th>
            <th>D.O.B</th>
            <th>Age</th>
            <th>Medical Center</th>
            <th>Country</th>
            <th>Code No</th>
            <th>M. Issu. D.</th>
            <th>M. E. D.</th>
            <th>Me.St.</th>
            <th>Mobile No</th>
            <th>Reference</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        @forelse($entries as $e)
            @php([$bg, $fg] = ErpPrintTheme::status('medical', $e->medical_status))
            <tr class="{{ $loop->even ? 'alt' : '' }}">
                <td class="num">{{ $loop->iteration }}</td>
                <td>{{ $e->full_name }}</td>
                <td>{{ $e->father_name }}</td>
                <td class="c nw">{{ $e->passport_no }}</td>
                <td class="c nw">{{ ErpPrintTheme::date($e->date_of_birth) }}</td>
                <td class="num">{{ $e->currentAge() }}</td>
                <td>{{ $e->medical_center_name }}</td>
                <td class="c">{{ $e->country }}</td>
                <td class="c nw">{{ $e->medical_code }}</td>
                <td class="c nw">{{ ErpPrintTheme::date($e->medical_issue_date) }}</td>
                <td class="c nw">{{ ErpPrintTheme::date($e->medical_expire_date) }}</td>
                <td class="c"><span class="badge" style="background:{{ $bg }}; color:{{ $fg }};">{{ $e->statusLabel() }}</span></td>
                <td class="c nw">{{ $e->mobile_no }}</td>
                <td>{{ $e->reference }}</td>
                <td>{{ $e->remarks }}</td>
            </tr>
        @empty
            <tr><td class="empty" colspan="15"><span style="font-style:normal;">&#8505;</span>&nbsp; No medical records found.</td></tr>
        @endforelse
    </tbody>
    @if($entries->isNotEmpty())
        <tfoot><tr><td colspan="15">Total: {{ number_format($entries->count()) }} {{ \Illuminate\Support\Str::plural('record', $entries->count()) }}</td></tr></tfoot>
    @endif
</table>
@endsection
