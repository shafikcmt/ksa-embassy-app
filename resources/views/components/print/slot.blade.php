{{--
    One half of an A4 sheet for 2-per-page printing (mPDF). A fixed-height box so the page is split
    exactly in the middle: the top slot hugs the top edge, the bottom slot hugs the bottom edge, and
    the leftover white space collects around the centre cut line instead of at the foot of the page.
    printable height = 297 − top margin 6 − bottom margin 12 = 279 mm  →  2 × 138 mm + the cut line.
--}}
@props(['pos' => 'top', 'height' => 138])
<table style="width:100%; height:{{ $height }}mm; border-collapse:collapse;" height="{{ $height }}mm" cellpadding="0" cellspacing="0">
    <tr style="height:{{ $height }}mm;"><td valign="{{ $pos }}" height="{{ $height }}mm" style="padding:0; height:{{ $height }}mm; vertical-align:{{ $pos }};">{{ $slot }}</td></tr>
</table>
