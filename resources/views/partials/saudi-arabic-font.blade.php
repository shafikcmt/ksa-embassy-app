{{--
    Saudi-style Arabic (Naskh) for Arabic text on HR record screens (form, list, profile).
    "Sakkal Majalla" / "Traditional Arabic" are the faces used on Saudi official documents
    (built into Windows); "Amiri" (Google Fonts) is the matching Naskh fallback elsewhere.
    Screen UI only — print/PDF templates keep their own reference fonts.
--}}
@once
@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
<style>
    :root { --saudi-ar: "Sakkal Majalla", "Traditional Arabic", "Amiri", "Noto Naskh Arabic", serif; }
    .ar-input, .saudi-ar, [lang="ar"], td[dir="rtl"], div[dir="rtl"], p[dir="rtl"], span[dir="rtl"], dd[dir="rtl"] {
        font-family: var(--saudi-ar) !important;
    }
    /* Naskh faces run small next to Latin UI text — nudge the size up a little. */
    .ar-input { font-size: 1.15rem !important; font-weight: 700; }
    [lang="ar"]:not(input), .saudi-ar { font-size: 1.08em; }
</style>
@endpush
@endonce
