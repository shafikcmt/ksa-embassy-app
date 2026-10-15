<?php

namespace App\Console\Commands;

use App\Http\Controllers\Erp\MofaController;
use App\Models\MofaEntry;
use App\Services\ErpPassportDataService;
use Illuminate\Console\Command;

/**
 * One-time backfill: fill BLANK candidate fields (father/mother name, DOB, passport dates,
 * visa no, ID no) on existing MOFA entries from the same passport's other ERP / HR records.
 * Only blanks are touched; every agency is processed on its own data.
 */
class BackfillMofaBlanks extends Command
{
    protected $signature = 'erp:backfill-mofa-blanks {--agency= : Only this agency ID} {--dry-run : Show what would change, save nothing}';

    protected $description = 'Auto-fill blank fields on existing MOFA entries from the passport\'s other ERP/HR records';

    public function handle(ErpPassportDataService $service): int
    {
        $entries = $fields = 0;
        $dry = (bool) $this->option('dry-run');

        MofaEntry::query()
            ->when($this->option('agency'), fn ($q, $id) => $q->where('agency_id', (int) $id))
            ->whereNotNull('passport_number')->where('passport_number', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($chunk) use ($service, $dry, &$entries, &$fields) {
                foreach ($chunk as $entry) {
                    $current = $entry->only(array_values(MofaController::AUTOFILL_MAP));
                    foreach ($current as $k => $v) {
                        $current[$k] = $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : $v;
                    }
                    $filled = $service->fillMissing($current, (int) $entry->agency_id, $entry->passport_number, MofaController::AUTOFILL_MAP, ['mofa', $entry->id]);
                    $changes = array_filter($filled, fn ($v, $k) => blank($current[$k] ?? null) && filled($v), ARRAY_FILTER_USE_BOTH);
                    if (! $changes) {
                        continue;
                    }
                    $entries++;
                    $fields += count($changes);
                    $this->line(sprintf('#%d %s: %s', $entry->id, $entry->passport_number, implode(', ', array_keys($changes))));
                    if (! $dry) {
                        $entry->forceFill($changes)->saveQuietly();
                    }
                }
            });

        $this->info(sprintf('%s %d field(s) on %d MOFA entr%s.', $dry ? 'Would fill' : 'Filled', $fields, $entries, $entries === 1 ? 'y' : 'ies'));

        return self::SUCCESS;
    }
}
