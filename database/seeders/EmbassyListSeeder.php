<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\EmbassyList;
use App\Models\EmbassyListItem;
use App\Models\HrProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmbassyListSeeder extends Seeder
{
    public function run(): void
    {
        $agency = Agency::where('slug', 'al-noor-recruitment-demo')->first();
        if (! $agency) return;

        $admin = User::where('email', 'admin@alnoor-demo.sa')->first();
        if (! $admin) return;

        // Fetch HR profiles by position (seeder inserts in order: 1..10)
        $hrAll = HrProfile::where('agency_id', $agency->id)->orderBy('id')->get();
        if ($hrAll->count() < 9) return;

        [$hr1, $hr2, $hr3, $hr4, $hr5, $hr6, $hr7, $hr8, $hr9] = $hrAll->take(9)->values();

        DB::transaction(function () use ($agency, $admin, $hr1, $hr2, $hr3, $hr4, $hr5, $hr6, $hr7, $hr8, $hr9) {

            // --- List 1: Draft ---
            $list1 = EmbassyList::create([
                'agency_id'  => $agency->id,
                'list_no'    => 'EL-2024-0001',
                'list_date'  => '2024-03-10',
                'title'      => 'March First Batch',
                'status'     => 'draft',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);

            $list1Items = [
                ['hr' => $hr1, 'category' => 'new'],
                ['hr' => $hr2, 'category' => 'new'],
                ['hr' => $hr3, 'category' => 'restamping'],
                ['hr' => $hr7, 'category' => 'cancellation'],
            ];

            foreach ($list1Items as $i => $entry) {
                EmbassyListItem::create($this->buildItem($list1, $entry['hr'], $entry['category'], $i + 1));
            }

            $list1->recalculateTotals();

            // --- List 2: Finalized ---
            $list2 = EmbassyList::create([
                'agency_id'    => $agency->id,
                'list_no'      => 'EL-2024-0002',
                'list_date'    => '2024-03-20',
                'title'        => 'March Second Batch',
                'status'       => 'finalized',
                'finalized_at' => now(),
                'created_by'   => $admin->id,
                'updated_by'   => $admin->id,
            ]);

            $list2Groups = [
                'new'        => [$hr4, $hr5, $hr6],
                'restamping' => [$hr8, $hr9],
            ];

            $sortOrder = 1;
            foreach ($list2Groups as $category => $hrs) {
                $serial = 1;
                foreach ($hrs as $hr) {
                    EmbassyListItem::create(
                        $this->buildItem($list2, $hr, $category, $sortOrder, $serial)
                    );
                    $serial++;
                    $sortOrder++;
                }
            }

            $list2->recalculateTotals();

            // Mark finalized HR profiles as 'listed'
            HrProfile::whereIn('id', [$hr4->id, $hr5->id, $hr6->id, $hr8->id, $hr9->id])
                ->update(['status' => 'listed']);
        });
    }

    private function buildItem(EmbassyList $list, HrProfile $hr, string $category, int $sortOrder, int $serialNo = 0): array
    {
        return [
            'embassy_list_id'           => $list->id,
            'agency_id'                 => $list->agency_id,
            'hr_profile_id'             => $hr->id,
            'agent_id'                  => $hr->agent_id,
            'category'                  => $category,
            'serial_no'                 => $serialNo,
            'sort_order'                => $sortOrder,
            'snapshot_agent_name'       => $hr->agent?->name,
            'snapshot_candidate_name'   => $hr->full_name_en,
            'snapshot_candidate_name_ar'=> $hr->full_name_ar,
            'snapshot_passport_no'      => $hr->passport?->passport_number,
            'snapshot_visa_no'          => $hr->visa?->visa_number,
            'snapshot_profession_en'    => $hr->occupation,
            'snapshot_sponsor_name'     => $hr->visa?->sponsor_name,
            'snapshot_sponsor_id'       => $hr->visa?->sponsor_id,
            'snapshot_nationality'      => $hr->nationality,
        ];
    }
}
