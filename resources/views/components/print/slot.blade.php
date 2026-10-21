{{--
    One half of an A4 sheet for 2-per-page printing (mPDF): a fixed-height <div> (no wrapper table — mPDF
    shrinks / pushes a table that is wider or taller than its box, which squashed the second document).
    printable height = 297 − top margin 6 − bottom margin 12 = 279 mm → two boxes of 137 mm (2 mm spare so the second box never tips onto a new page); the dashed cut
    line is the bottom edge of the first box, i.e. the exact middle of the printable area.
--}}
@props(['pos' => 'top', 'height' => 137])
<div style="height:{{ $height }}mm; @if($pos === 'top') border-bottom:0.5px dashed #94a3b8; @endif">{{ $slot }}</div>
