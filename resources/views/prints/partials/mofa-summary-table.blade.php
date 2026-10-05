{{--
    One MOFA summary table (thead + $rows numbered from $offset + 1, total footer when $last).
    The preview renders it once with every row; the PDF writes it to mPDF one chunk at a
    time (MofaController::PDF_CHUNK_ROWS). Markup is kept compact on purpose: indentation
    was ~75% of the old per-row HTML.
--}}
@use('App\Support\ErpPrintTheme')
@use('App\Http\Controllers\Erp\MofaController')
@php
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
    $widthList = array_values($widths);

    $dateFields = ['date_of_birth', 'issue_date', 'expiry_date', 'mofa_issue_date', 'mofa_date'];
    $numFields  = ['age'];
    $wrapFields = ['full_name', 'father_name', 'mother_name', 'reference'];
@endphp
<table class="grid mofa-print">
<thead>
<tr>
@foreach($columns as $col => $label)
<th nowrap="nowrap" style="width: {{ $widthList[$col] }}%;">{{ $label }}</th>
@endforeach
</tr>
</thead>
<tbody>
@forelse($rows as $entry)
@php
    [$bg, $fg] = ErpPrintTheme::status('mofa', $entry->status);
    $sl = $offset + $loop->iteration;
@endphp
<tr class="{{ $sl % 2 === 0 ? 'alt' : '' }}"><td class="num">{{ $sl }}</td>
@foreach($fields as $field)
@php
    $value = in_array($field, $dateFields, true)
        ? ErpPrintTheme::date($field === 'mofa_issue_date' ? $entry->displayMofaIssueDate() : $entry->$field)
        : MofaController::value($entry, $field);
    $class = in_array($field, $numFields, true) ? 'num' : (in_array($field, $wrapFields, true) ? 'text' : (in_array($field, $dateFields, true) ? 'c nw' : 'c text'));
@endphp
<td class="{{ $class }}"@if(! in_array($field, $wrapFields, true)) nowrap="nowrap"@endif>{{ $value }}@if($field === 'full_name')<br><span class="badge" style="background:{{ $bg }}; color:{{ $fg }};">{{ $entry->statusLabel() }}</span>@endif</td>
@endforeach
</tr>
@empty
<tr><td class="empty" colspan="{{ count($columns) }}">No MOFA records found.</td></tr>
@endforelse
</tbody>
@if($last && $total > 0)
<tfoot><tr><td colspan="{{ count($columns) }}">Total: {{ number_format($total) }} {{ \Illuminate\Support\Str::plural('record', $total) }}</td></tr></tfoot>
@endif
</table>
