<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\HrProfile;
use Illuminate\Http\Request;

class HrProfileController extends Controller
{
    public function index(Request $request)
    {
        $query = HrProfile::with(['agency', 'agent'])->latest();

        if ($agencyId = $request->input('agency_id')) {
            $query->where('agency_id', $agencyId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name_en', 'like', "%{$search}%")
                  ->orWhere('full_name_ar', 'like', "%{$search}%")
                  ->orWhere('file_number', 'like', "%{$search}%")
                  ->orWhere('nationality', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $hrProfiles = $query->paginate(20)->withQueryString();
        $agencies   = Agency::orderBy('name')->get();

        return view('super-admin.hr.index', compact('hrProfiles', 'agencies'));
    }

    public function show(HrProfile $hr)
    {
        $hr->load(['agency', 'agent', 'passport', 'visa', 'clearance', 'otherInfo', 'createdBy', 'updatedBy']);

        return view('super-admin.hr.show', compact('hr'));
    }
}
