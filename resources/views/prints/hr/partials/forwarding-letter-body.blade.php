{{--
  SHARED Forwarding Letter body. Used by BOTH the single preview/PDF
  (prints.hr.forwarding-letter) and the Complete File (prints.hr.full-file,
  page 2) so the layout is identical everywhere. Fully self-contained with
  inline styles + .ksa-letter scoped wrapper — does not depend on host body
  font-size or any .dtbl class.
--}}
<div class="ksa-letter" style="font-size:13pt;line-height:1.6;">

{{-- Blank space for pre-printed letterhead (reduced from 65mm so the bottom
     "Your Faithfully" signature is never pushed into the cut/unprintable area) --}}
<div style="height:35mm;"></div>

{{-- To address --}}
<p style="margin:0;font-size:13pt;">To,</p>
<p style="margin:0;font-size:13pt;">The Chief Of Consular Section,</p>
<p style="margin:0;font-size:13pt;">The Royal Embassy Kingdom Of Saudi Arabia,</p>
<p style="margin:0 0 18pt 0;font-size:13pt;">Gulshan, Dhaka, Bangladesh.</p>

<p style="margin:0 0 10pt 0;font-size:13pt;"><strong>Excellency,</strong></p>

<p style="margin:0 0 14pt 0;font-size:13pt;text-align:justify;">
With Due Respect we are Submitting One Passport for work Visa with all Necessary Documents and Particulars mentioned as below, knowing all instruction and regulation of the consulate section.
</p>

{{-- Details table — bottom border only --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:14pt;font-size:10pt;">
  <tbody>
    <tr>
      <td style="width:50%;border-bottom:1px solid #000;padding:5pt 4pt;"><strong>NAME OF COMPANY</strong></td>
      <td style="width:50%;border-bottom:1px solid #000;padding:5pt 4pt;">
        @if(!empty($sponsor_name_ar))<span class="ar" style="font-weight:bold;">{{ $sponsor_name_ar }}</span>@else{{ $sponsor_name ?: $agency_name }}@endif
      </td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;"><strong>VISA NUMBER &amp; DATE</strong></td>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;">{{ $visa_no ?: '—' }}@if($visa_date) &nbsp; Date: {{ $visa_date }}@endif</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;"><strong>FULL NAME OF THE EMPLOYEE</strong></td>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;">{{ $full_name_en }}</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;"><strong>PASSPORT NO. WITH ISSUE DATE</strong></td>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;">{{ $passport_no ?: '—' }}@if($passport_issue_date) &nbsp; Date: {{ $passport_issue_date }}@endif</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;"><strong>PROFESSION</strong></td>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;">{{ $profession_en ?: ($occupation ?: '—') }}</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;"><strong>RELIGION</strong></td>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;">{{ $religion ?: '—' }}</td>
    </tr>
  </tbody>
</table>

<p style="margin:0 0 12pt 0;font-size:13pt;text-align:justify;">
I do hereby confirm and declare that the region stated in the Visa form and forwarding letter is fully correct. I also undertake with my own responsibility to cancel the Visa and to stop functioning with my office, If the statement is found incorrect.
</p>

<p style="margin:0 0 24pt 0;font-size:13pt;text-align:justify;">
We therefore, Request your Excellency to kindly issue work Visa out of - 01 - Visas and oblige thereby.
</p>

{{-- Signature — kept together so it is never split across pages --}}
<div class="ksa-signature" style="margin-top:10pt;page-break-inside:avoid;break-inside:avoid;">
  <div style="border-top:1px solid #000;width:160pt;padding-top:4pt;font-size:13pt;">
    <strong>Your Faithfully</strong>
  </div>
</div>

</div>{{-- /.ksa-letter --}}
