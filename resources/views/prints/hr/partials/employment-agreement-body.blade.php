{{--
  SHARED Employment Agreement body. Used by BOTH the single preview/PDF
  (prints.hr.employment-agreement) and the Complete File (prints.hr.full-file,
  page 3) so the layout is identical everywhere. Fully self-contained with
  inline styles + .ksa-letter scoped wrapper.
--}}
<div class="ksa-letter" style="font-size:13pt;line-height:1.6;">

{{-- Blank space for pre-printed letterhead (reduced from 65mm so the bottom
     signature row is never pushed into the cut/unprintable area) --}}
<div style="height:30mm;"></div>

{{-- Title --}}
<div style="text-align:center;margin-bottom:16pt;">
  <span style="font-size:14pt;font-weight:bold;text-decoration:underline;letter-spacing:1pt;">EMPLOYMENT AGREEMENT</span>
</div>

{{-- Party info — bottom border only --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:14pt;font-size:10pt;">
  <tbody>
    <tr>
      <td style="width:50%;border-bottom:1px solid #000;padding:5pt 4pt;"><strong>NAME OF COMPANY :</strong></td>
      <td style="width:50%;border-bottom:1px solid #000;padding:5pt 4pt;">
        {{ $agency_name }}
      </td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;"><strong>HEREBY APPOINTED :</strong></td>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;">{{ $full_name_en }}</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;"><strong>PASSPORT NO WITH ISSUE DATE :</strong></td>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;">{{ $passport_no ?: '—' }}@if($passport_issue_date) &nbsp; Date: {{ $passport_issue_date }}@endif</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;"><strong>PASSPORT HOLDER :</strong></td>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;">{{ $nationality }}</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;"><strong>PROFESSION :</strong></td>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;">{{ $profession_en ?: ($occupation ?: '—') }}</td>
    </tr>
  </tbody>
</table>

{{-- Terms heading --}}
<div style="text-align:center;margin-bottom:10pt;">
  <span style="font-size:12pt;font-weight:bold;text-decoration:underline;">UNDER THE FOLLOWING TERMS AND CONDITIONS :</span>
</div>

{{-- Terms table --}}
<table style="width:100%;margin-bottom:18pt;font-size:10pt;border-collapse:collapse;">
  <colgroup>
    <col style="width:8%">
    <col style="width:56%">
    <col style="width:36%">
  </colgroup>
  <tbody>
    <tr>
      <td style="border:1px solid #000;padding:4pt 6pt;text-align:center;font-weight:bold;">1</td>
      <td style="border:1px solid #000;padding:4pt 6pt;"><strong>MONTHLY SALARY</strong></td>
      <td style="border:1px solid #000;padding:4pt 6pt;">{{ $salary ?: '—' }}</td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:4pt 6pt;text-align:center;font-weight:bold;">2</td>
      <td style="border:1px solid #000;padding:4pt 6pt;"><strong>FOOD AND ACCOMMODATION</strong></td>
      <td style="border:1px solid #000;padding:4pt 6pt;">200/= SR</td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:4pt 6pt;text-align:center;font-weight:bold;">3</td>
      <td style="border:1px solid #000;padding:4pt 6pt;"><strong>AIR PASSAGE</strong></td>
      <td style="border:1px solid #000;padding:4pt 6pt;">BORNE BY THE EMPLOYER</td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:4pt 6pt;text-align:center;font-weight:bold;">4</td>
      <td style="border:1px solid #000;padding:4pt 6pt;"><strong>DUTY HOUR</strong></td>
      <td style="border:1px solid #000;padding:4pt 6pt;">8 HOURS DAILY</td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:4pt 6pt;text-align:center;font-weight:bold;">5</td>
      <td style="border:1px solid #000;padding:4pt 6pt;"><strong>HOLIDAY</strong></td>
      <td style="border:1px solid #000;padding:4pt 6pt;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:4pt 6pt;text-align:center;font-weight:bold;">6</td>
      <td style="border:1px solid #000;padding:4pt 6pt;"><strong>LEAVE</strong></td>
      <td style="border:1px solid #000;padding:4pt 6pt;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:4pt 6pt;text-align:center;font-weight:bold;">7</td>
      <td style="border:1px solid #000;padding:4pt 6pt;"><strong>OVERTIME &amp; OTHER BENEFIT</strong></td>
      <td style="border:1px solid #000;padding:4pt 6pt;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:4pt 6pt;text-align:center;font-weight:bold;">8</td>
      <td style="border:1px solid #000;padding:4pt 6pt;"><strong>MEDICAL FACILITIES</strong></td>
      <td style="border:1px solid #000;padding:4pt 6pt;">FREE</td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:4pt 6pt;text-align:center;font-weight:bold;">9</td>
      <td style="border:1px solid #000;padding:4pt 6pt;"><strong>PERIOD OF CONTRACT</strong></td>
      <td style="border:1px solid #000;padding:4pt 6pt;">{{ $contract_period ?: ($duration_stay_en ?: 'TWO/ONE YEARS') }}</td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:4pt 6pt;text-align:center;font-weight:bold;">10</td>
      <td style="border:1px solid #000;padding:4pt 6pt;font-size:9pt;"><strong>REPATRIATION ARRANGEMENT INCLUDING RETURN OF DEAD BODY &amp; SERVICE BENEFIT TO THE LEGAL HEIR OF THE EMPLOYEE</strong></td>
      <td style="border:1px solid #000;padding:4pt 6pt;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
  </tbody>
</table>

{{-- Signatures — kept together so the row is never split across pages --}}
<div class="ksa-signature" style="page-break-inside:avoid;break-inside:avoid;">
  <div style="height:12mm;"></div>
  <table style="width:100%;border-collapse:collapse;font-size:11pt;">
    <tr>
      <td style="width:50%;text-align:center;padding:0 6pt;vertical-align:bottom;">
        <div style="border-top:1px solid #000;padding-top:4pt;">
          <strong>SIGNATURE OF FIRST PARTY</strong>
        </div>
      </td>
      <td style="width:50%;text-align:center;padding:0 6pt;vertical-align:bottom;">
        <div style="border-top:1px solid #000;padding-top:4pt;">
          <strong>SIGNATURE OF SECOND PARTY</strong>
        </div>
      </td>
    </tr>
  </table>
</div>

</div>{{-- /.ksa-letter --}}
