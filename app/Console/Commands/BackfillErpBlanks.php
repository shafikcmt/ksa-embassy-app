<?php

namespace App\Console\Commands;

use App\Services\ErpPassportDataService;
use Illuminate\Console\Command;

/**
 * One-time backfill: fill BLANK candidate fields on existing ERP rows (Medical, MOFA, Visa Stamping,
 * BMET, Delivery, Double MOFA) from the same passport's other ERP / HR records.
 * Only blanks are touched, never another agency's data, never entry-specific values
 * (see ErpPassportDataService::AUTOFILL for exactly which fields).
 */
class BackfillErpBlanks extends Command
{
    protected $signature = 'erp:backfill-blanks {--module=all : medical|mofa|stamping|bmet|delivery|double_mofa|all} {--agency= : Only this agency ID} {--dry-run : Show what would change, save nothing}';

    protected $description = 'Auto-fill blank fields on existing ERP rows from the passport\'s other ERP/HR records';

    public function handle(ErpPassportDataService $service): int
    {
        $only = (string) $this->option('module');
        $modules = $only === 'all' ? array_keys(ErpPassportDataService::AUTOFILL) : [$only];
        if (array_diff($modules, array_keys(ErpPassportDataService::AUTOFILL))) {
            $this->error('Unknown module. Use: '.implode(', ', array_keys(ErpPassportDataService::AUTOFILL)).' or all.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $rows = $fields = 0;

        foreach ($modules as $module) {
            ['model' => $model, 'passport' => $passportCol, 'map' => $map] = ErpPassportDataService::AUTOFILL[$module];
            $attrs = array_values($map);

            $model::query()
                ->when($this->option('agency'), fn ($q, $id) => $q->where('agency_id', (int) $id))
                ->whereNotNull($passportCol)->where($passportCol, '!=', '')
                ->orderBy('id')
                ->chunkById(200, function ($chunk) use ($service, $module, $passportCol, $attrs, $dry, &$rows, &$fields) {
                    foreach ($chunk as $row) {
                        $current = [];
                        foreach ($attrs as $a) {
                            $v = $row->{$a};
                            $current[$a] = $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : $v;
                        }
                        $filled = $service->fillForModule($module, $current, (int) $row->agency_id, (string) $row->{$passportCol}, (int) $row->id);
                        $changes = array_filter($filled, fn ($v, $k) => blank($current[$k] ?? null) && filled($v), ARRAY_FILTER_USE_BOTH);
                        if (! $changes) {
                            continue;
                        }
                        $rows++;
                        $fields += count($changes);
                        $this->line(sprintf('%s #%d %s: %s', $module, $row->id, $row->{$passportCol}, implode(', ', array_keys($changes))));
                        if (! $dry) {
                            $row->forceFill($changes)->saveQuietly();
                        }
                    }
                });
        }

        $this->info(sprintf('%s %d field(s) on %d row(s).', $dry ? 'Would fill' : 'Filled', $fields, $rows));

        return self::SUCCESS;
    }
}
