<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ErpPassportDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /erp/autofill/{passport} — everything the ERP already knows about one
 * passport (Medical → MOFA → Stamping → BMET, HR profile as fallback), for the
 * module modals' auto-fill + "Linked to" tags.
 *
 * Registered inside the ERP route group, so it runs behind session auth,
 * agency-access (tenancy) and page-access:erp. agency_id always comes from the
 * signed-in user, never the client. Read-only.
 *
 * ?exclude=<module>:<id> leaves out the record currently being edited.
 */
class ErpAutoFillController extends Controller
{
    public function getErpDataByPassport(Request $request, string $passportNumber, ErpPassportDataService $erp): JsonResponse
    {
        $passport = strtoupper(trim($passportNumber));
        abort_unless(preg_match('/^[A-Z0-9]{5,20}$/', $passport) === 1, 422, 'Invalid passport number.');

        $exclude = null;
        if (preg_match('/^(' . implode('|', ErpPassportDataService::MODULES) . '):(\d+)$/', (string) $request->query('exclude', ''), $m)) {
            $exclude = [$m[1], (int) $m[2]];
        }

        return response()->json($erp->forPassport((int) $request->user()->agency_id, $passport, $exclude));
    }
}
