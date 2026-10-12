{{-- Illustrated KSA scenes for the home hero slider (inline SVG, no external files needed).
     @include('landing._hero-scenes', ['scene' => 'riyadh'|'haram'|'desert'|'flight']) --}}
@switch($scene)
@case('riyadh')
<svg viewBox="0 0 1200 360" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
  <defs><linearGradient id="gRy" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0b3a63" stop-opacity=".0"/><stop offset="1" stop-color="#06233f" stop-opacity=".9"/></linearGradient></defs>
  <g fill="#0a2f52" opacity=".55">
    <rect x="40" y="230" width="60" height="130"/><rect x="110" y="200" width="46" height="160"/><rect x="170" y="250" width="70" height="110"/>
    <rect x="860" y="215" width="54" height="145"/><rect x="925" y="245" width="80" height="115"/><rect x="1020" y="190" width="50" height="170"/><rect x="1085" y="235" width="90" height="125"/>
  </g>
  {{-- Kingdom Centre (arch top) --}}
  <path d="M640 360V110c0-14 10-24 24-24h44c14 0 24 10 24 24v250h-26V150c0-14-8-24-20-24s-20 10-20 24v210z" fill="#0c3b66"/>
  <path d="M640 360V110c0-14 10-24 24-24h44c14 0 24 10 24 24v250" fill="none" stroke="#2bb985" stroke-opacity=".55" stroke-width="2"/>
  {{-- Al Faisaliyah (sphere on a spire) --}}
  <path d="M520 360 548 150h8l28 210z" fill="#0d4274"/><circle cx="552" cy="128" r="24" fill="#12508a"/><rect x="551" y="60" width="3" height="46" fill="#12508a"/>
  <circle cx="552" cy="128" r="24" fill="none" stroke="#2bb985" stroke-opacity=".5" stroke-width="2"/>
  {{-- Mid towers --}}
  <g fill="#0a3157"><rect x="740" y="190" width="58" height="170"/><rect x="805" y="150" width="44" height="210"/><rect x="440" y="210" width="62" height="150"/><rect x="360" y="240" width="70" height="120"/><rect x="280" y="205" width="64" height="155"/></g>
  <g fill="#e8f3ff" opacity=".35"><rect x="752" y="205" width="6" height="8"/><rect x="766" y="205" width="6" height="8"/><rect x="752" y="228" width="6" height="8"/><rect x="814" y="170" width="6" height="8"/><rect x="826" y="170" width="6" height="8"/><rect x="814" y="196" width="6" height="8"/><rect x="452" y="230" width="6" height="8"/><rect x="470" y="230" width="6" height="8"/><rect x="292" y="225" width="6" height="8"/></g>
  <rect y="330" width="1200" height="30" fill="url(#gRy)"/>
</svg>
@break
@case('haram')
<svg viewBox="0 0 1200 360" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
  <g fill="#064e3b" opacity=".85">
    {{-- Minarets --}}
    <g><rect x="520" y="90" width="12" height="270"/><rect x="514" y="130" width="24" height="10"/><rect x="514" y="190" width="24" height="10"/><path d="M518 90l8-34 8 34z"/></g>
    <g><rect x="900" y="90" width="12" height="270"/><rect x="894" y="130" width="24" height="10"/><rect x="894" y="190" width="24" height="10"/><path d="M898 90l8-34 8 34z"/></g>
    <g><rect x="360" y="170" width="10" height="190"/><rect x="355" y="200" width="20" height="8"/><path d="M358 170l7-26 7 26z"/></g>
    <g><rect x="1050" y="170" width="10" height="190"/><rect x="1045" y="200" width="20" height="8"/><path d="M1048 170l7-26 7 26z"/></g>
    {{-- Main dome + hall --}}
    <rect x="590" y="230" width="270" height="130"/>
    <path d="M610 230c0-62 40-96 115-96s115 34 115 96z"/>
    <rect x="722" y="96" width="4" height="40"/><circle cx="724" cy="92" r="6"/>
    <path d="M440 360v-90c0-24 16-40 40-40s40 16 40 40v90z"/>
    <path d="M940 360v-90c0-24 16-40 40-40s40 16 40 40v90z"/>
  </g>
  <g fill="#fcd34d" opacity=".5"><circle cx="724" cy="92" r="6"/><rect x="516" y="56" width="4" height="8"/><rect x="902" y="56" width="4" height="8"/></g>
  <g fill="#d1fae5" opacity=".28"><path d="M650 360v-60c0-14 9-24 22-24s22 10 22 24v60z"/><path d="M754 360v-60c0-14 9-24 22-24s22 10 22 24v60z"/></g>
</svg>
@break
@case('desert')
<svg viewBox="0 0 1200 360" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
  <circle cx="930" cy="120" r="64" fill="#fde68a" opacity=".85"/><circle cx="930" cy="120" r="92" fill="#fde68a" opacity=".18"/>
  <path d="M0 270c140-60 280-70 420-30s260 40 380-10 260-50 400 10v120H0z" fill="#92400e" opacity=".55"/>
  <path d="M0 310c180-50 330-40 480-5s330 30 720-25v80H0z" fill="#78350f" opacity=".8"/>
  {{-- Rock arch (AlUla-style) --}}
  <path d="M120 330V210c0-40 28-70 62-70s62 30 62 70v120h-34V220c0-20-12-34-28-34s-28 14-28 34v110z" fill="#7c2d12" opacity=".9"/>
  {{-- Palm --}}
  <g fill="#14532d"><path d="M1010 340c4-70 6-110 0-160h8c-4 50-2 90 4 160z" fill="#3f2a14"/>
    <path d="M1014 180c-30-6-52 6-70 24 26-8 48-8 70-8zm0 0c30-6 52 6 70 24-26-8-48-8-70-8zm0 0c-14-26-12-44 0-60 4 20 6 38 0 60zm0 0c-24-14-52-14-74 2 24-4 50-2 74-2zm0 0c24-14 52-14 74 2-24-4-50-2-74-2z"/></g>
  {{-- Camel caravan --}}
  <g fill="#3b1d0a">
    <g transform="translate(470 238)"><path d="M0 40h6l2-26h30l4 10 6-6h12l4 22h6v-8h6l-4 16h-8l-2-12-8 4v8h-6V32H14v10H8z"/><path d="M14 14c2-14 10-14 14 0z"/><path d="M62 6c6-6 10-6 12 0l-2 12-8 2z"/></g>
    <g transform="translate(590 252) scale(.85)"><path d="M0 40h6l2-26h30l4 10 6-6h12l4 22h6v-8h6l-4 16h-8l-2-12-8 4v8h-6V32H14v10H8z"/><path d="M14 14c2-14 10-14 14 0z"/><path d="M62 6c6-6 10-6 12 0l-2 12-8 2z"/></g>
  </g>
</svg>
@break
@default
<svg viewBox="0 0 1200 360" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
  <g fill="#fff" opacity=".85">
    <ellipse cx="180" cy="300" rx="190" ry="46"/><ellipse cx="330" cy="318" rx="170" ry="40"/><ellipse cx="700" cy="320" rx="240" ry="48"/>
    <ellipse cx="1000" cy="300" rx="210" ry="50"/><ellipse cx="1130" cy="322" rx="140" ry="36"/>
  </g>
  <g fill="#fff" opacity=".4"><ellipse cx="520" cy="250" rx="110" ry="22"/><ellipse cx="880" cy="230" rx="130" ry="20"/></g>
  {{-- Airliner --}}
  <g transform="translate(640 96) rotate(-9)">
    <path d="M0 40c0-10 14-20 40-20h210c34 0 66 10 78 22-14 14-48 22-78 22H40c-26 0-40-10-40-24z" fill="#fff"/>
    <path d="M150 38 70 -22h30l110 56z" fill="#e2e8f0"/><path d="M150 54 90 112h26l108-52z" fill="#cbd5e1"/>
    <path d="M0 40c0-6 4-12 10-16l-4-30h18l10 24z" fill="#15497e"/>
    <g fill="#15497e" opacity=".75"><circle cx="262" cy="36" r="4"/><circle cx="244" cy="36" r="4"/><circle cx="226" cy="36" r="4"/><circle cx="208" cy="36" r="4"/><circle cx="190" cy="36" r="4"/></g>
    <rect x="30" y="48" width="260" height="5" fill="#0e9f6e"/>
  </g>
</svg>
@endswitch
