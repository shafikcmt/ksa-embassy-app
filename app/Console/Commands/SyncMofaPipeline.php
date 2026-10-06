<?php

namespace App\Console\Commands;

use App\Models\MofaEntry;
use App\Services\MofaSyncService;
use Illuminate\Console\Command;

/**
 * One-time backfill: give existing MOFA entries their Visa Stamping, BMET and
 * Delivery rows (existing rows for the same passport are linked, not duplicated).
 */
class SyncMofaPipeline extends Command
{
    protected $signature = 'erp:sync-mofa-pipeline {--agency= : Only this agency ID}';

    protected $description = 'Link/create Stamping, BMET and Delivery rows for existing MOFA entries';

    public function handle(MofaSyncService $sync): int
    {
        $count = 0;
        MofaEntry::query()
            ->when($this->option('agency'), fn ($q, $id) => $q->where('agency_id', (int) $id))
            ->orderBy('id')
            ->chunkById(200, function ($entries) use ($sync, &$count) {
                foreach ($entries as $entry) {
                    $sync->backfill($entry);
                    $count++;
                }
            });

        $this->info("Checked {$count} MOFA " . ($count === 1 ? 'entry' : 'entries') . '.');

        return self::SUCCESS;
    }
}
