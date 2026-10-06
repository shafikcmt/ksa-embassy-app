<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\BmetEntry;
use App\Models\Delivery;
use App\Models\DoubleMofa;
use App\Models\MofaEntry;
use App\Models\VisaStamping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Pushes a MOFA entry's candidate data down the ERP pipeline:
 * Double MOFA, Visa Stamping, BMET Clearance and Delivery.
 *
 * Rules (MOFA is the source of truth for the shared candidate fields):
 *  - New MOFA entry  → Stamping / BMET / Delivery: the passport's existing row in
 *    that module is adopted (updated + linked), otherwise a new pending row is
 *    created. Double MOFA: a row is created only when this passport already had
 *    an earlier MOFA entry (the module tracks repeat MOFAs).
 *  - MOFA entry edit → only rows linked to it are updated; nothing is created,
 *    so a downstream row the user deleted is not brought back.
 *  - Blank MOFA values never wipe a downstream value.
 *  - Module-own fields (stamping date/status, EC number/date, delivery date,
 *    amounts, payments, old MOFA number) are never touched on update.
 *  - MOFA entry deleted → rows are only unlinked, never deleted.
 *
 * Always scoped to the entry's own agency.
 */
class MofaSyncService
{
    /** Sync after a MOFA entry was created. */
    public function created(MofaEntry $entry): void
    {
        $this->run(function () use ($entry) {
            $this->syncDoubleMofa($entry, true);

            // Stamping / BMET / Delivery hold one row per passport; they follow
            // the passport's newest MOFA entry.
            if ($this->isLatestForPassport($entry)) {
                $this->syncStamping($entry, true);
                $this->syncBmet($entry, true);
                $this->syncDelivery($entry, true);
            }
        });
    }

    /** Sync after a MOFA entry was edited: update linked rows only. */
    public function updated(MofaEntry $entry): void
    {
        $this->run(function () use ($entry) {
            $this->syncDoubleMofa($entry, false);
            $this->syncStamping($entry, false);
            $this->syncBmet($entry, false);
            $this->syncDelivery($entry, false);
        });
    }

    /**
     * One-off backfill for MOFA entries saved before this sync existed: links or
     * creates the Stamping / BMET / Delivery rows of the passport's newest entry.
     * Double MOFA is left alone (its old rows were entered manually with billing).
     * Safe to re-run: linked/existing rows are updated, never duplicated.
     */
    public function backfill(MofaEntry $entry): void
    {
        if (! $this->isLatestForPassport($entry)) {
            return;
        }
        $this->run(function () use ($entry) {
            $this->syncStamping($entry, true);
            $this->syncBmet($entry, true);
            $this->syncDelivery($entry, true);
        });
    }

    /** MOFA entry removed: keep the downstream rows, just drop the link. */
    public function unlink(MofaEntry $entry): void
    {
        foreach (['double_mofas', 'stampings', 'manpower_completions', 'deliveries'] as $table) {
            DB::table($table)->where('mofa_entry_id', $entry->id)->update(['mofa_entry_id' => null]);
        }
    }

    // ── Modules ──────────────────────────────────────────────────────────

    private function syncDoubleMofa(MofaEntry $entry, bool $create): void
    {
        $row = DoubleMofa::forAgency($this->agencyId($entry))->where('mofa_entry_id', $entry->id)->first();

        if (! $row) {
            $previous = $create ? $this->previousEntry($entry) : null;
            if (! $previous) {
                return;
            }
            $row = new DoubleMofa([
                'agency_id'       => $entry->agency_id,
                'mofa_entry_id'   => $entry->id,
                'old_mofa_number' => $previous->mofa_number,
                'mofa_date'       => $entry->mofa_date ?? today(),
                'billing_amount'  => 0,
                'status'          => 'unpaid',
                'created_by'      => auth()->id(),
            ]);
        }

        $this->apply($row, [
            'mofa_date'   => $entry->mofa_date,
            'full_name'   => $entry->full_name,
            'passport_no' => $entry->passport_no,
            'visa_serial' => $entry->visa_serial,
            'reference'   => $entry->reference_name,
        ]);
    }

    private function syncStamping(MofaEntry $entry, bool $create): void
    {
        $row = $this->findRow(VisaStamping::class, $entry, $create);
        if ($row === false) {
            return;
        }
        $row ??= new VisaStamping([
            'agency_id'     => $entry->agency_id,
            'stamping_date' => today(),
            'status'     => 'pending',
            'created_by' => auth()->id(),
        ]);

        $this->apply($row, [
            'mofa_entry_id' => $entry->id,
            'hr_profile_id' => $entry->hr_profile_id,
            'full_name'     => $entry->full_name,
            'father_name'   => $entry->father_name,
            'mother_name'   => $entry->mother_name,
            'passport_no'   => $entry->passport_no,
            'date_of_birth' => $entry->date_of_birth,
            'visa_number'   => $entry->visa_serial,
            'id_number'     => $entry->id_number,
            'mofa_number'   => $entry->mofa_number,
            'mofa_date'     => $entry->mofa_date,
            'reference'     => $entry->reference_name,
            'agent_id'      => $this->agentIdFor($entry),
        ]);
    }

    private function syncBmet(MofaEntry $entry, bool $create): void
    {
        $row = $this->findRow(BmetEntry::class, $entry, $create);
        if ($row === false) {
            return;
        }
        $row ??= new BmetEntry([
            'agency_id'  => $entry->agency_id,
            'ec_date'    => today(),
            'status'     => 'pending',
            'created_by' => auth()->id(),
        ]);

        $this->apply($row, [
            'mofa_entry_id' => $entry->id,
            'hr_profile_id' => $entry->hr_profile_id,
            'customer_name' => $entry->full_name,
            'father_name'   => $entry->father_name,
            'passport_no'   => $entry->passport_no,
            'visa_number'   => $entry->visa_serial,
            'id_number'     => $entry->id_number,
            'reference'     => $entry->reference_name,
            'agent_id'      => $this->agentIdFor($entry),
        ]);
    }

    private function syncDelivery(MofaEntry $entry, bool $create): void
    {
        $row = $this->findRow(Delivery::class, $entry, $create);
        if ($row === false) {
            return;
        }
        $row ??= new Delivery([
            'agency_id'     => $entry->agency_id,
            'delivery_date' => today(),
            'total_amount'  => 0,
            'status'        => 'pending',
            'created_by'    => auth()->id(),
        ]);

        $this->apply($row, [
            'mofa_entry_id' => $entry->id,
            'full_name'     => $entry->full_name,
            'passport_no'   => $entry->passport_no,
            'visa_serial'   => $entry->visa_serial,
            'reference'     => $entry->reference_name,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * The row to sync: the one linked to this entry, else (create mode only)
     * the passport's existing row in this module. Returns null when a new row
     * should be created, false when nothing should happen (edit with no linked
     * row, or a linked row the user soft-deleted).
     *
     * @param  class-string<Model>  $model
     */
    private function findRow(string $model, MofaEntry $entry, bool $create): Model|null|false
    {
        $agencyId = $this->agencyId($entry);
        $softDeletes = method_exists($model, 'bootSoftDeletes');

        $linked = $model::forAgency($agencyId)->where('mofa_entry_id', $entry->id)
            ->when($softDeletes, fn ($q) => $q->withTrashed())->first();
        if ($linked) {
            return $softDeletes && $linked->trashed() ? false : $linked;
        }
        if (! $create) {
            return false;
        }

        return $model::forAgency($agencyId)->where('passport_no', $entry->passport_no)->orderByDesc('id')->first();
    }

    /** Fill non-blank values (blank MOFA values never wipe downstream data) and save if dirty. */
    private function apply(Model $row, array $values): void
    {
        foreach ($values as $key => $value) {
            if ($value !== null && $value !== '') {
                $row->setAttribute($key, $value);
            }
        }
        if (! $row->exists || $row->isDirty()) {
            $row->setAttribute('updated_by', auth()->id() ?? $row->getAttribute('updated_by'));
            $row->save();
        }
    }

    private function isLatestForPassport(MofaEntry $entry): bool
    {
        return ! MofaEntry::forAgency($this->agencyId($entry))
            ->where('passport_no', $entry->passport_no)
            ->where('id', '>', $entry->id)
            ->exists();
    }

    private function previousEntry(MofaEntry $entry): ?MofaEntry
    {
        return MofaEntry::forAgency($this->agencyId($entry))
            ->where('passport_no', $entry->passport_no)
            ->where('id', '<', $entry->id)
            ->orderByDesc('id')
            ->first();
    }

    /** Agent whose name matches the MOFA reference, so Agent Khata picks the row up. */
    private function agentIdFor(MofaEntry $entry): ?int
    {
        $name = trim((string) $entry->reference_name);

        return $name === '' ? null
            : Agent::forAgency($this->agencyId($entry))->where('name', $name)->value('id');
    }

    private function agencyId(MofaEntry $entry): int
    {
        return (int) $entry->agency_id;
    }

    private function run(\Closure $work): void
    {
        DB::transactionLevel() > 0 ? $work() : DB::transaction($work);
    }
}
