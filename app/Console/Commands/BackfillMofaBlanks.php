<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/** Kept for compatibility: same as `erp:backfill-blanks --module=mofa`. */
class BackfillMofaBlanks extends Command
{
    protected $signature = 'erp:backfill-mofa-blanks {--agency= : Only this agency ID} {--dry-run : Show what would change, save nothing}';

    protected $description = 'Auto-fill blank fields on existing MOFA entries (alias of erp:backfill-blanks --module=mofa)';

    public function handle(): int
    {
        return $this->call('erp:backfill-blanks', array_filter([
            '--module' => 'mofa',
            '--agency' => $this->option('agency'),
            '--dry-run' => $this->option('dry-run'),
        ]));
    }
}
