{{--
    MOFA Summary — reference column sequence (MofaController::COLUMNS / FIELDS).
    Shell: prints/partials/erp-summary/layout.

    $layout (from MofaController): landscape = all 17 columns in one table;
    portrait = the same data split into two tables on separate pages
    (personal & passport details, then MOFA processing details). The status
    badge sits under the passenger name, as before.
--}}
@use('App\Support\ErpPrintTheme')
@use('App\Http\Controllers\Erp\MofaController')
@php
    $module      = 'mofa';
    $orientation = ($layout ?? 'landscape') === 'portrait' ? 'portrait' : 'landscape';

    $columns = MofaController::COLUMNS;
    $fields  = MofaController::FIELDS;

    // Landscape widths per column index (sum 100); portrait parts re-normalise them.
    $groups = $orientation === 'portrait'
        ? [['cols' => range(0, 10), 'note' => 'Part 1 of 2 · Personal & passport details'],
           ['cols' => array_merge([0, 1], range(11, 16)), 'note' => 'Part 2 of 2 · MOFA processing details']]
        : [['cols' => range(0, 16), 'note' => null]];

    $dateFields = ['date_of_birth', 'issue_date', 'expiry_date', 'mofa_issue_date', 'mofa_date'];
    $numFields  = ['age', 'left_day'];
    $wrapFields = ['full_name', 'father_name', 'mother_name', 'reference', 'remarks'];
    $sectionNote = $orientation === 'portrait' ? 'Portrait · 2 parts' : null;
@endphp
@extends('prints.partials.erp-summary.layout')

@section('table')
@foreach($groups as $group)
    @php $cols = $group['cols']; @endphp
    @if(! $loop->first)
        <div style="page-break-before: always;"></div>
    @endif
    @if($group['note'])
        <div style="font-size:9pt; font-weight:bold; color:{{ ErpPrintTheme::PRIMARY_DARK }}; margin:0 0 5pt;">{{ $group['note'] }}</div>
    @endif
    <table class="grid">
        <thead>
            <tr>
                {{-- No fixed widths: mPDF auto-sizes each column to at least its longest unbreakable
                     token (dates, 10–11 digit numbers, one-word headers) and wraps only free text. --}}
                @foreach($cols as $col)
                    <th class="{{ $col === 0 ? 'l' : '' }}">{{ $columns[$col] }}</th>
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
                                $class = in_array($field, $numFields, true) ? 'num' : (in_array($field, $wrapFields, true) ? '' : 'c nw');
                                if ($field === 'left_day' && $value !== null && $value !== '' && (int) $value < 30) {
                                    $class .= ' low';
                                }
                            @endphp
                            <td class="{{ $class }}">
                                {{ $value }}
                                @if($field === 'full_name')
                                    <br><span class="badge" style="background:{{ $bg }}; color:{{ $fg }};">{{ $entry->statusLabel() }}</span>
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr><td class="empty" colspan="{{ count($cols) }}"><span style="font-style:normal;">&#8505;</span>&nbsp; No MOFA records found.</td></tr>
            @endforelse
        </tbody>
        @if($entries->isNotEmpty())
            <tfoot><tr><td colspan="{{ count($cols) }}">Total: {{ number_format($entries->count()) }} {{ \Illuminate\Support\Str::plural('record', $entries->count()) }}</td></tr></tfoot>
        @endif
    </table>
@endforeach
@endsection
