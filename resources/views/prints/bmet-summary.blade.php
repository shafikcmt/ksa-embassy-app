{{--
    BMET Clearance Summary — BmetController::COLUMNS / FIELDS, A4 landscape.
    Shell: prints/partials/erp-summary/layout. The status badge (and an
    "expires …" note when the EC is within 30 days of expiry) sits under the
    passenger name, as before.
--}}
@use('App\Support\ErpPrintTheme')
@use('App\Http\Controllers\Erp\BmetController')
@php
    $module     = 'bmet';
    $wrapFields = ['full_name', 'father_name', 'reference', 'remarks'];
@endphp
@extends('prints.partials.erp-summary.layout')

@section('table')
<table class="grid">
    <thead>
        <tr>
            {{-- No fixed widths: mPDF auto-sizes columns to their longest unbreakable token. --}}
            @foreach(BmetController::COLUMNS as $i => $column)
                <th class="{{ $i === 0 ? 'l' : '' }}">{{ $column }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($entries as $entry)
            @php [$bg, $fg] = ErpPrintTheme::status('bmet', $entry->effective_status); @endphp
            <tr class="{{ $loop->even ? 'alt' : '' }}">
                <td class="num">{{ $loop->iteration }}</td>
                @foreach(BmetController::FIELDS as $field)
                    @php
                        $value = $field === 'ec_date' ? ErpPrintTheme::date($entry->ec_date) : BmetController::value($entry, $field);
                    @endphp
                    <td class="{{ in_array($field, $wrapFields, true) ? '' : 'c nw' }}">
                        {{ $value }}
                        @if($field === 'full_name')
                            <br><span class="badge" style="background:{{ $bg }}; color:{{ $fg }};">{{ $entry->statusLabel() }}</span>
                            @if($entry->expiring_soon)
                                <br><span style="font-size:7.5pt; color:#92400e;">Expires {{ ErpPrintTheme::date($entry->ec_expiry_date) }}</span>
                            @endif
                        @endif
                    </td>
                @endforeach
            </tr>
        @empty
            <tr><td class="empty" colspan="{{ count(BmetController::COLUMNS) }}"><span style="font-style:normal;">&#8505;</span>&nbsp; No BMET clearance records found.</td></tr>
        @endforelse
    </tbody>
    @if($entries->isNotEmpty())
        <tfoot><tr><td colspan="{{ count(BmetController::COLUMNS) }}">Total: {{ number_format($entries->count()) }} {{ \Illuminate\Support\Str::plural('record', $entries->count()) }}</td></tr></tfoot>
    @endif
</table>
@endsection
