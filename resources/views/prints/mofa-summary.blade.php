{{-- MOFA Summary: single A4 landscape report, using the existing ERP print shell. --}}
@use('App\Support\ErpPrintTheme')
@use('App\Http\Controllers\Erp\MofaController')
@php
    $module      = 'mofa';
    $orientation = 'landscape';
    // Print-only widths total 100%; normal forms and CSV keep their own fields.
    $widths = [
        'sl' => 2, 'full_name' => 10, 'father_name' => 7, 'mother_name' => 7,
        'passport_number' => 6, 'date_of_birth' => 7, 'age' => 3,
        'issue_date' => 6, 'expiry_date' => 6, 'visa_number' => 8, 'id_number' => 7,
        'mofa_issue_date' => 7, 'mofa_number' => 8, 'mofa_date' => 7, 'reference' => 9,
    ];
    $fields = array_slice(array_keys($widths), 1);
    $columns = ['SL'];
    foreach ($fields as $field) {
        $columns[] = MofaController::COLUMNS[array_search($field, MofaController::FIELDS, true) + 1];
    }
    $columns[array_search('mofa_number', $fields, true) + 1] = 'MOFA No';
    $columns[array_search('mofa_date', $fields, true) + 1] = 'MOFA Date';
    $cols = range(0, count($columns) - 1);

    $dateFields = ['date_of_birth', 'issue_date', 'expiry_date', 'mofa_issue_date', 'mofa_date'];
    $numFields  = ['age'];
    $wrapFields = ['full_name', 'father_name', 'mother_name', 'reference'];

@endphp
@extends('prints.partials.erp-summary.layout')

@section('table')
    <style>
        /* MOFA-only overrides: shared ERP templates and other reports stay unchanged. */
        .hdr td { padding: 6pt 9pt; vertical-align: middle; }
        .logo-svg { width: 13mm; height: 13mm; }
        .agency-name { font-size: 14pt; line-height: 1.2; }
        .agency-rl { font-size: 9pt; margin-top: 2pt; line-height: 1.3; }
        .agency-addr { font-size: 8pt; margin-top: 2pt; line-height: 1.3; }
        .doc-badge { font-size: 9pt; padding: 3pt 7pt; background: #eaf1fc; color: #1e40af; }
        .doc-date { font-size: 8pt; margin-top: 3pt; }
        .divider { margin: 6pt 0; border-top: 0.5pt solid #e2e8f0; }
        .title { font-size: 18pt; line-height: 1.15; }
        .subtitle { font-size: 8pt; color: #64748b; margin-top: 3pt; margin-bottom: 7pt; }
        .mofa-print th { background: #356bc0; background-color: #356bc0; font-size: 6.5pt;
            font-weight: bold; letter-spacing: 0; line-height: 1.25; vertical-align: middle;
            text-align: center; white-space: nowrap; padding: 5pt 2pt; }
        .mofa-print td { font-size: 7pt; padding: 4.5pt 2pt; line-height: 1.35;
            vertical-align: middle; border-bottom: 0.5pt solid #e2e8f0; }
        .mofa-print tr.alt td { background: #f8fafc; }
        .mofa-print td.text { white-space: normal; }
        .mofa-print .badge { font-size: 6.5pt; padding: 1pt 3pt; }
        .mofa-print td.empty { padding: 12pt 6pt; text-align: center; vertical-align: middle;
            font-size: 9pt; font-style: normal; color: #64748b; background: #f8fafc; }
        .mofa-print tfoot td { font-size: 7.5pt; padding: 5pt 6pt; background: #edf3fc;
            border-top: 0.5pt solid #cbd5e1; border-bottom: 0; }
    </style>
    <table class="grid mofa-print">
        <thead>
            <tr>
                @foreach($cols as $col)
                    <th nowrap="nowrap" style="width: {{ array_values($widths)[$col] }}%;">{{ $columns[$col] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                @php [$bg, $fg] = ErpPrintTheme::status('mofa', $entry->status); @endphp
                <tr class="{{ $loop->even ? 'alt' : '' }}">
                    @foreach($cols as $col)
                        @if($col === 0)
                            <td class="num">{{ $loop->parent->iteration }}</td>
                        @else
                            @php
                                $field = $fields[$col - 1];
                                $value = in_array($field, $dateFields, true)
                                    ? ErpPrintTheme::date($field === 'mofa_issue_date' ? $entry->displayMofaIssueDate() : $entry->$field)
                                    : MofaController::value($entry, $field);
                                $class = in_array($field, $numFields, true) ? 'num' : (in_array($field, $wrapFields, true) ? 'text' : (in_array($field, $dateFields, true) ? 'c nw' : 'c text'));
                            @endphp
                            <td class="{{ $class }}" @if(! in_array($field, $wrapFields, true)) nowrap="nowrap" @endif>
                                {{ $value }}
                                @if($field === 'full_name')
                                    <br><span class="badge" style="background:{{ $bg }}; color:{{ $fg }};">{{ $entry->statusLabel() }}</span>
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr><td class="empty" colspan="{{ count($cols) }}">No MOFA records found.</td></tr>
            @endforelse
        </tbody>
        @if($entries->isNotEmpty())
            <tfoot><tr><td colspan="{{ count($cols) }}">Total: {{ number_format($entries->count()) }} {{ \Illuminate\Support\Str::plural('record', $entries->count()) }}</td></tr></tfoot>
        @endif
    </table>
@endsection
