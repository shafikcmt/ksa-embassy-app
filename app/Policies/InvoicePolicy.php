<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

/**
 * Invoice delete authorization. Module access (access_erp) is enforced by the
 * route group's page-access:erp middleware; tenancy is checked by the
 * controller first (cross-agency → 404). Other invoice actions keep their
 * existing controller checks.
 *
 *   draft ............................ agency admin, or the staff member who created it
 *   pending / paid / cancelled ....... agency admin only
 *
 * Delete is always a soft delete; numbers are never reissued (InvoiceService).
 */
class InvoicePolicy
{
    private function sameAgency(User $user, Invoice $invoice): bool
    {
        return $user->agency_id !== null && (int) $user->agency_id === (int) $invoice->agency_id;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        if (! $this->sameAgency($user, $invoice)) {
            return false;
        }

        if ($user->isAgencyAdmin()) {
            return true;
        }

        return $invoice->status === 'draft'
            && $invoice->created_by !== null
            && (int) $invoice->created_by === (int) $user->id;
    }
}
