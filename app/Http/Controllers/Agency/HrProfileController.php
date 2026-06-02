<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHrProfileRequest;
use App\Http\Requests\UpdateHrProfileRequest;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\HrProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrProfileController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', HrProfile::class);

        $agencyId = auth()->user()->agency_id;

        $query = HrProfile::with(['agent'])
            ->forAgency($agencyId)
            ->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name_en', 'like', "%{$search}%")
                  ->orWhere('full_name_ar', 'like', "%{$search}%")
                  ->orWhere('file_number', 'like', "%{$search}%")
                  ->orWhere('nationality', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($agentId = $request->input('agent_id')) {
            $query->where('agent_id', $agentId);
        }

        if ($request->input('filter') === 'passport_expiring') {
            $query->whereHas('passport', fn($q) => $q
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [now(), now()->addMonths(6)])
            );
        }

        $hrProfiles = $query->paginate(15)->withQueryString();

        $agents = Agent::forAgency($agencyId)->active()->orderBy('name')->get();

        $subscription = auth()->user()->agency?->activeSubscription;
        $planLimit    = $subscription?->plan?->max_hr ?? 0;
        $totalHr      = HrProfile::forAgency($agencyId)->count();

        return view('agency.hr.index', compact('hrProfiles', 'agents', 'planLimit', 'totalHr'));
    }

    public function create()
    {
        $this->authorize('create', HrProfile::class);
        $this->enforcePlanLimit();

        $agencyId = auth()->user()->agency_id;
        $agents   = Agent::forAgency($agencyId)->active()->orderBy('name')->get();

        return view('agency.hr.create', compact('agents'));
    }

    public function store(StoreHrProfileRequest $request)
    {
        $this->authorize('create', HrProfile::class);
        $this->enforcePlanLimit();

        $user     = auth()->user();
        $agencyId = $user->agency_id;

        $hrProfile = DB::transaction(function () use ($request, $user, $agencyId) {
            $hrProfile = HrProfile::create([
                'agency_id'            => $agencyId,
                'agent_id'             => $request->agent_id,
                'file_number'          => $request->file_number,
                'full_name_en'         => $request->full_name_en,
                'full_name_ar'         => $request->full_name_ar,
                'father_name'          => $request->father_name,
                'mother_name'          => $request->mother_name,
                'place_of_birth'       => $request->place_of_birth,
                'nationality'          => $request->nationality,
                'previous_nationality' => $request->previous_nationality,
                'mofa_new'             => $request->mofa_new,
                'mofa_old'             => $request->mofa_old,
                'date_of_birth'        => $request->date_of_birth,
                'gender'               => $request->gender,
                'sect'                 => $request->sect,
                'religion'             => $request->religion,
                'marital_status'       => $request->marital_status,
                'occupation'           => $request->occupation,
                'phone'                => $request->phone,
                'home_address'         => $request->home_address,
                'email'                => $request->email,
                'status'               => $request->status,
                'notes'                => $request->notes,
                'created_by'           => $user->id,
                'updated_by'           => $user->id,
            ]);

            $hrProfile->passport()->create([
                'passport_number' => $request->passport_number,
                'passport_type'   => $request->passport_type ?? 'regular',
                'issue_date'      => $request->passport_issue_date,
                'expiry_date'     => $request->passport_expiry_date,
                'validity_years'  => $request->passport_validity_years,
                'issue_place'     => $request->passport_issue_place,
            ]);

            $hrProfile->visa()->create([
                'visa_number'      => $request->visa_number,
                'visa_type'        => $request->visa_type,
                'issue_date'       => $request->visa_issue_date,
                'expiry_date'      => $request->visa_expiry_date,
                'issue_place'      => $request->visa_issue_place,
                'issue_place_ar'   => $request->visa_issue_place_ar,
                'sponsor_name'     => $request->sponsor_name,
                'sponsor_name_ar'  => $request->sponsor_name_ar,
                'sponsor_id'       => $request->sponsor_id,
                'border_number'    => $request->border_number,
                'profession_en'    => $request->profession_en,
                'profession_ar'    => $request->profession_ar,
                'qualification_en' => $request->qualification_en,
                'qualification_ar' => $request->qualification_ar,
                'travel_purpose'   => $request->travel_purpose,
                'musaned_no'       => $request->musaned_no,
                'wakala_no'        => $request->wakala_no,
            ]);

            $hrProfile->clearance()->create([
                'police_clearance_number' => $request->police_clearance_number,
                'pc_qr_code'              => $request->pc_qr_code,
                'license_type'            => $request->license_type,
                'fingerprint'             => $request->fingerprint,
                'clearance_issue_date'    => $request->clearance_issue_date,
                'clearance_expiry_date'   => $request->clearance_expiry_date,
                'clearance_country'       => $request->clearance_country,
                'medical_fit'             => $request->boolean('medical_fit'),
                'medical_date'            => $request->medical_date,
                'medical_center'          => $request->medical_center,
            ]);

            $hrProfile->otherInfo()->create([
                'duration_stay_en'  => $request->duration_stay_en,
                'duration_stay_ar'  => $request->duration_stay_ar,
                'arrival_date'      => $request->arrival_date,
                'arrival_date_ar'   => $request->arrival_date_ar,
                'departure_date'    => $request->departure_date,
                'departure_date_ar' => $request->departure_date_ar,
                'contract_period'     => $request->contract_period,
                'salary'              => $request->salary,
                'work_city'           => $request->work_city,
                'employer_name'       => $request->employer_name,
                'employer_phone'      => $request->employer_phone,
                'remarks'             => $request->remarks,
                'business_address_en' => $request->business_address_en,
                'business_address_ar' => $request->business_address_ar,
                'kingdom_address_en'  => $request->kingdom_address_en,
                'kingdom_address_ar'  => $request->kingdom_address_ar,
            ]);

            return $hrProfile;
        });

        AuditLog::record('create', $hrProfile, [], $hrProfile->toArray());

        return redirect()->route('hr.show', $hrProfile)
            ->with('success', 'HR profile created successfully.');
    }

    public function show(HrProfile $hr)
    {
        $this->authorize('view', $hr);

        $hr->load(['agent', 'passport', 'visa', 'clearance', 'otherInfo', 'createdBy', 'updatedBy',
            'embassyListItems.embassyList']);

        return view('agency.hr.show', compact('hr'));
    }

    public function edit(HrProfile $hr)
    {
        $this->authorize('update', $hr);

        $hr->load(['passport', 'visa', 'clearance', 'otherInfo']);

        $agencyId = auth()->user()->agency_id;
        $agents   = Agent::forAgency($agencyId)->active()->orderBy('name')->get();

        return view('agency.hr.edit', compact('hr', 'agents'));
    }

    public function update(UpdateHrProfileRequest $request, HrProfile $hr)
    {
        $this->authorize('update', $hr);

        $oldValues = $hr->toArray();

        DB::transaction(function () use ($request, $hr) {
            $hr->update([
                'agent_id'             => $request->agent_id,
                'file_number'          => $request->file_number,
                'full_name_en'         => $request->full_name_en,
                'full_name_ar'         => $request->full_name_ar,
                'father_name'          => $request->father_name,
                'mother_name'          => $request->mother_name,
                'place_of_birth'       => $request->place_of_birth,
                'nationality'          => $request->nationality,
                'previous_nationality' => $request->previous_nationality,
                'mofa_new'             => $request->mofa_new,
                'mofa_old'             => $request->mofa_old,
                'date_of_birth'        => $request->date_of_birth,
                'gender'               => $request->gender,
                'sect'                 => $request->sect,
                'religion'             => $request->religion,
                'marital_status'       => $request->marital_status,
                'occupation'           => $request->occupation,
                'phone'                => $request->phone,
                'home_address'         => $request->home_address,
                'email'                => $request->email,
                'status'               => $request->status,
                'notes'                => $request->notes,
                'updated_by'           => auth()->id(),
            ]);

            $hr->passport()->updateOrCreate(
                ['hr_profile_id' => $hr->id],
                [
                    'passport_number' => $request->passport_number,
                    'passport_type'   => $request->passport_type ?? 'regular',
                    'issue_date'      => $request->passport_issue_date,
                    'expiry_date'     => $request->passport_expiry_date,
                    'validity_years'  => $request->passport_validity_years,
                    'issue_place'     => $request->passport_issue_place,
                ]
            );

            $hr->visa()->updateOrCreate(
                ['hr_profile_id' => $hr->id],
                [
                    'visa_number'      => $request->visa_number,
                    'visa_type'        => $request->visa_type,
                    'issue_date'       => $request->visa_issue_date,
                    'expiry_date'      => $request->visa_expiry_date,
                    'issue_place'      => $request->visa_issue_place,
                    'issue_place_ar'   => $request->visa_issue_place_ar,
                    'sponsor_name'     => $request->sponsor_name,
                    'sponsor_name_ar'  => $request->sponsor_name_ar,
                    'sponsor_id'       => $request->sponsor_id,
                    'border_number'    => $request->border_number,
                    'profession_en'    => $request->profession_en,
                    'profession_ar'    => $request->profession_ar,
                    'qualification_en' => $request->qualification_en,
                    'qualification_ar' => $request->qualification_ar,
                    'travel_purpose'   => $request->travel_purpose,
                    'musaned_no'       => $request->musaned_no,
                    'wakala_no'        => $request->wakala_no,
                ]
            );

            $hr->clearance()->updateOrCreate(
                ['hr_profile_id' => $hr->id],
                [
                    'police_clearance_number' => $request->police_clearance_number,
                    'pc_qr_code'              => $request->pc_qr_code,
                    'license_type'            => $request->license_type,
                    'fingerprint'             => $request->fingerprint,
                    'clearance_issue_date'    => $request->clearance_issue_date,
                    'clearance_expiry_date'   => $request->clearance_expiry_date,
                    'clearance_country'       => $request->clearance_country,
                    'medical_fit'             => $request->boolean('medical_fit'),
                    'medical_date'            => $request->medical_date,
                    'medical_center'          => $request->medical_center,
                ]
            );

            $hr->otherInfo()->updateOrCreate(
                ['hr_profile_id' => $hr->id],
                [
                    'duration_stay_en'  => $request->duration_stay_en,
                    'duration_stay_ar'  => $request->duration_stay_ar,
                    'arrival_date'      => $request->arrival_date,
                    'arrival_date_ar'   => $request->arrival_date_ar,
                    'departure_date'    => $request->departure_date,
                    'departure_date_ar' => $request->departure_date_ar,
                    'contract_period'     => $request->contract_period,
                    'salary'              => $request->salary,
                    'work_city'           => $request->work_city,
                    'employer_name'       => $request->employer_name,
                    'employer_phone'      => $request->employer_phone,
                    'remarks'             => $request->remarks,
                    'business_address_en' => $request->business_address_en,
                    'business_address_ar' => $request->business_address_ar,
                    'kingdom_address_en'  => $request->kingdom_address_en,
                    'kingdom_address_ar'  => $request->kingdom_address_ar,
                ]
            );
        });

        AuditLog::record('update', $hr, $oldValues, $hr->fresh()->toArray());

        return redirect()->route('hr.show', $hr)
            ->with('success', 'HR profile updated successfully.');
    }

    public function destroy(HrProfile $hr)
    {
        $this->authorize('delete', $hr);

        AuditLog::record('delete', $hr, $hr->toArray(), []);

        $hr->delete();

        return redirect()->route('hr.index')
            ->with('success', 'HR profile deleted successfully.');
    }

    public function lookupByPassport(Request $request)
    {
        $request->validate(['passport_no' => 'required|string|max:50']);

        $agencyId = auth()->user()->agency_id;

        $hr = HrProfile::with([
                'agent:id,name',
                'passport:id,hr_profile_id,passport_number',
                'visa:id,hr_profile_id,visa_number,profession_en',
            ])
            ->forAgency($agencyId)
            ->whereHas('passport', fn($q) => $q->where('passport_number', $request->passport_no))
            ->first();

        if (! $hr) {
            return response()->json([
                'found'   => false,
                'message' => 'No candidate found with that passport number in your agency.',
            ]);
        }

        return response()->json([
            'found'        => true,
            'id'           => $hr->id,
            'full_name_en' => $hr->full_name_en,
            'full_name_ar' => $hr->full_name_ar ?? '',
            'nationality'  => $hr->nationality,
            'passport_no'  => $hr->passport?->passport_number ?? '',
            'visa_no'      => $hr->visa?->visa_number ?? '',
            'agent_name'   => $hr->agent?->name ?? '',
            'profession'   => $hr->visa?->profession_en ?? ($hr->occupation ?? ''),
        ]);
    }

    private function enforcePlanLimit(): void
    {
        $user         = auth()->user();
        $subscription = $user->agency?->activeSubscription;
        $plan         = $subscription?->plan;

        if (! $plan) {
            abort(403, 'No active subscription.');
        }

        $maxHr = $plan->max_hr;

        if ($maxHr >= 9999) {
            return;
        }

        $currentCount = HrProfile::where('agency_id', $user->agency_id)->count();

        if ($currentCount >= $maxHr) {
            abort(403, "HR profile limit reached ({$maxHr}). Please upgrade your plan.");
        }
    }
}
