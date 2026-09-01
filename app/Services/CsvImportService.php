<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

/**
 * Module-agnostic CSV import engine (E7b). READ-ONLY — it NEVER writes to the
 * database; it only parses + validates and hands back a structured, preview-ready
 * result. The calling controller owns the actual insert (inside its own
 * all-or-nothing transaction) so tenancy/creator stamping stays in one place.
 *
 * Each caller supplies:
 *   headers   string[]  expected columns, in order (also the downloadable template)
 *   rules     array     the SAME Laravel rules the module's manual "Add" uses
 *   normalize callable  fn(array $rawAssoc): array — trim, enum key/label mapping,
 *                       date normalisation → the attributes to validate & insert
 *   notices   ?callable fn(array $attrs): string[] — non-blocking notes (e.g. a
 *                       duplicate-passport heads-up); notices NEVER block a row
 *
 * Parsing is BOM-safe and skips fully-blank lines; extra columns are ignored and
 * missing expected columns are a file-level error (nothing is processed).
 */
class CsvImportService
{
    /** Hard cap on data rows per import (bounds memory for the pilot). */
    public const MAX_ROWS = 1000;

    /**
     * @return array{fileError: ?string, total: int, validCount: int, errorCount: int, noticeCount: int, ok: bool, rows: array<int, array{line:int, attrs:array, errors:string[], notices:string[]}>}
     */
    public function process(string $absolutePath, array $config): array
    {
        $headers   = $config['headers'];
        $rules     = $config['rules'];
        $normalize = $config['normalize'];
        $notices   = $config['notices'] ?? null;
        $messages  = $config['messages'] ?? []; // optional custom validation messages

        if (! is_file($absolutePath) || filesize($absolutePath) === 0) {
            return $this->fileError('The file is empty.');
        }

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            return $this->fileError('The file could not be read.');
        }

        // Header row — strip a UTF-8 BOM off the first cell, then lower/trim.
        $rawHeader = fgetcsv($handle);
        if ($rawHeader === false || $rawHeader === [null]) {
            fclose($handle);
            return $this->fileError('The file has no header row.');
        }
        $rawHeader[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $rawHeader[0]);
        $fileHeaders  = array_map(fn ($h) => strtolower(trim((string) $h)), $rawHeader);

        $missing = array_diff($headers, $fileHeaders);
        if ($missing) {
            fclose($handle);
            return $this->fileError('Missing column(s): ' . implode(', ', $missing) . '.');
        }

        $rows = [];
        $errorCount = $validCount = $noticeCount = 0;
        $line = 1; // header consumed above

        while (($cells = fgetcsv($handle)) !== false) {
            $line++;

            // Skip completely blank lines.
            if ($cells === [null] || count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);
                return $this->fileError('Too many rows (max ' . self::MAX_ROWS . ' per import).');
            }

            // Map cells to the expected columns by header name (ignore extras).
            $assoc = [];
            foreach ($headers as $key) {
                $idx = array_search($key, $fileHeaders, true);
                $assoc[$key] = ($idx !== false && array_key_exists($idx, $cells)) ? (string) $cells[$idx] : '';
            }

            $attrs      = $normalize($assoc);
            $errs       = Validator::make($attrs, $rules, $messages)->errors()->all();
            $rowNotices = $notices ? $notices($attrs) : [];

            $errs ? $errorCount++ : $validCount++;
            $noticeCount += count($rowNotices);

            $rows[] = ['line' => $line, 'attrs' => $attrs, 'errors' => $errs, 'notices' => $rowNotices];
        }
        fclose($handle);

        if (count($rows) === 0) {
            return $this->fileError('No data rows found.');
        }

        return [
            'fileError'   => null,
            'total'       => count($rows),
            'validCount'  => $validCount,
            'errorCount'  => $errorCount,
            'noticeCount' => $noticeCount,
            'ok'          => $errorCount === 0,   // all-or-nothing gate
            'rows'        => $rows,
        ];
    }

    private function fileError(string $msg): array
    {
        return [
            'fileError' => $msg, 'total' => 0, 'validCount' => 0, 'errorCount' => 0,
            'noticeCount' => 0, 'ok' => false, 'rows' => [],
        ];
    }

    /**
     * Shared date normaliser for module import normalizers: turns common inputs
     * (Y-m-d, d/m/Y, d-m-Y, Y/m/d) into Y-m-d; leaves anything unparseable as-is
     * so the module's `date` rule rejects it with a clear message. Blank → ''.
     */
    public static function toYmd(string $v): string
    {
        $v = trim($v);
        if ($v === '') {
            return '';
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $fmt) {
            $d = \DateTime::createFromFormat('!' . $fmt, $v);
            if ($d && $d->format($fmt) === $v) {
                return $d->format('Y-m-d');
            }
        }

        $ts = strtotime($v);
        return $ts ? date('Y-m-d', $ts) : $v;
    }
}
