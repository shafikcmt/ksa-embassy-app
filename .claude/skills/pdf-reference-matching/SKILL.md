# PDF Reference Matching Skill

Use this skill for VisaDeskPro PDF/print tasks, especially HR Application and Embassy List formats.

## Goal
Make generated PDF/print output visually match the reference PDF/images as closely as possible.

## References
- `docs/references/ksa-complete-file-reference-0001.pdf`
- `docs/references/embassy-list-reference.pdf`
- `docs/images/ksa-application-reference-0001.jpg`
- `docs/images/ksa-application-reference-0002.jpg`
- `docs/images/ksa-application-reference-0003.jpg`
- `docs/images/ksa-application-reference-0004.jpg`
- `docs/images/embassy-list-reference-0001.jpg`
- `docs/images/embassy-list-reference-0002.jpg`

## Procedure
1. Inspect current print route/controller/service/view.
2. Identify the exact Blade partial and CSS that controls the PDF.
3. Compare generated output with reference page-by-page.
4. Fix one section at a time.
5. Do not redesign. Match the reference.
6. Keep browser preview, print preview, and downloaded PDF consistent.
7. After changes, generate preview and verify layout.

## Must Match
- A4 size
- top/bottom/left/right margins
- page breaks
- font family
- font size
- font weight/boldness
- text casing
- Arabic/English alignment
- barcode size and position
- photo box size and position
- table border thickness
- cell padding
- row height
- column width
- footer/header placement

## HR Application Page 1 Notes
- Section 1 must be matched before continuing to other sections.
- Full Name and Mother’s Name values must be bold/dark like reference, but not too tall.
- Do not force uppercase globally. Match reference casing field-by-field.
- Arabic labels are usually lighter than English bold values.
- Business address row should be slightly bolder/darker.
- Header photo/barcode/right-side MOFA area must align like reference.

## If Image Reading Fails
Do not waste time retrying image uploads. Use local reference PDF/images directly. If needed, create small local debug JPGs under 800–1000px width only.

## Do Not
- Do not add extra borders where reference has none.
- Do not add dashed placeholder lines if reference does not have them.
- Do not change unrelated dashboard/form/database logic.
