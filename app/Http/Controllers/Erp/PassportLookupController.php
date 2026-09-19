<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DoubleMofa;
use App\Models\ManpowerCompletion;
use App\Models\Medical;
use App\Models\MofaEntry;
use App\Models\Stamping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ERP cross-module passport auto-fill.
 *
 * A single read-only endpoint the Add forms hit (vanilla fetch) when the user
 * tabs out of a Passport Number field. It looks the passport up across ALL six
 * ERP modules for the CURRENT agency and returns the single most-recently
 * touched match, normalised to a stable field contract. The Blade partial then
 * fills only the empty inputs on the form — it never overwrites what the user
 * typed, and only the identity fields below (never money/status/date) are ever
 * returned.
 *
 * Tenancy: agency_id always comes from the authenticated user, never the
 * client. Route middleware (auth + agency-access + page-access:erp) already
 * guards access; each query is additionally scoped by agency_id.
 */
class PassportLookupController extends Controller
{
    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'passport_no' => 'required|string|max:50',
        ]);

        try {
            $agencyId   = (int) auth()->user()->agency_id;
            $passportNo = $data['passport_no'];

            // One freshest candidate per module (agency-scoped), each normalised
            // to the shared contract. Nulls where a module doesn't carry a field.
            // Priority order (tie-breaker on equal updated_at) favours the
            // richest sources first: MOFA → Stamping → Delivery → Double MOFA →
            // Manpower → Medical.
            $candidates = array_filter([
                $this->fromMofa($agencyId, $passportNo),
                $this->fromStamping($agencyId, $passportNo),
                $this->fromDelivery($agencyId, $passportNo),
                $this->fromDoubleMofa($agencyId, $passportNo),
                $this->fromManpower($agencyId, $passportNo),
                $this->fromMedical($agencyId, $passportNo),
            ]);

            if (empty($candidates)) {
                return response()->json(['found' => false]);
            }

            // Single overall best match: most-recently-updated wins. array_filter
            // above preserves the priority order, so usort's stable-enough pick of
            // the first max on ties honours the richest-source tie-breaker.
            usort($candidates, fn ($a, $b) => $b['updated_at'] <=> $a['updated_at']);
            $best = $candidates[0];

            return response()->json([
                'found'       => true,
                'source'      => $best['source'],
                'full_name'   => $best['full_name'],
                'visa_serial' => $best['visa_serial'],
                'id_number'   => $best['id_number'],
                'reference'   => $best['reference'],
            ]);
        } catch (\Throwable $e) {
            // Never surface an exception to the form — degrade to "not found".
            Log::warning('ERP passport lookup failed', ['error' => $e->getMessage()]);

            return response()->json(['found' => false]);
        }
    }

    private function fromMofa(int $agencyId, string $passportNo): ?array
    {
        $row = MofaEntry::where('agency_id', $agencyId)
            ->where('passport_no', $passportNo)
            ->orderByDesc('updated_at')
            ->first();

        return $row ? [
            'source'      => 'mofa',
            'full_name'   => $row->full_name,
            'visa_serial' => $row->visa_serial,
            'id_number'   => $row->id_number,
            'reference'   => $row->reference_name,
            'updated_at'  => $row->updated_at,
        ] : null;
    }

    private function fromStamping(int $agencyId, string $passportNo): ?array
    {
        $row = Stamping::where('agency_id', $agencyId)
            ->where('passport_no', $passportNo)
            ->orderByDesc('updated_at')
            ->first();

        return $row ? [
            'source'      => 'stamping',
            'full_name'   => $row->full_name,
            'visa_serial' => $row->visa_serial,
            'id_number'   => $row->id_number,
            'reference'   => $row->reference,
            'updated_at'  => $row->updated_at,
        ] : null;
    }

    private function fromDelivery(int $agencyId, string $passportNo): ?array
    {
        $row = Delivery::where('agency_id', $agencyId)
            ->where('passport_no', $passportNo)
            ->orderByDesc('updated_at')
            ->first();

        return $row ? [
            'source'      => 'delivery',
            'full_name'   => $row->full_name,
            'visa_serial' => $row->visa_serial,
            'id_number'   => null,
            'reference'   => $row->reference,
            'updated_at'  => $row->updated_at,
        ] : null;
    }

    private function fromDoubleMofa(int $agencyId, string $passportNo): ?array
    {
        $row = DoubleMofa::where('agency_id', $agencyId)
            ->where('passport_no', $passportNo)
            ->orderByDesc('updated_at')
            ->first();

        return $row ? [
            'source'      => 'double_mofa',
            'full_name'   => $row->full_name,
            'visa_serial' => $row->visa_serial,
            'id_number'   => null,
            'reference'   => $row->reference,
            'updated_at'  => $row->updated_at,
        ] : null;
    }

    private function fromManpower(int $agencyId, string $passportNo): ?array
    {
        $row = ManpowerCompletion::where('agency_id', $agencyId)
            ->where('passport_no', $passportNo)
            ->orderByDesc('updated_at')
            ->first();

        return $row ? [
            'source'      => 'manpower',
            'full_name'   => $row->customer_name,
            'visa_serial' => null,
            'id_number'   => null,
            'reference'   => null,
            'updated_at'  => $row->updated_at,
        ] : null;
    }

    private function fromMedical(int $agencyId, string $passportNo): ?array
    {
        $row = Medical::where('agency_id', $agencyId)
            ->where('passport_no', $passportNo)
            ->orderByDesc('updated_at')
            ->first();

        return $row ? [
            'source'      => 'medical',
            'full_name'   => $row->full_name,
            'visa_serial' => null,
            'id_number'   => null,
            'reference'   => null,
            'updated_at'  => $row->updated_at,
        ] : null;
    }
}
