<?php

namespace App\Policies;

use App\Models\PaymentVoucher;
use App\Models\User;

/**
 * Payment voucher authorization. Module access (access_erp) is enforced by the
 * route group's page-access:erp middleware; this policy adds tenancy + roles.
 *
 *   view / print ............ any user of the same agency
 *   create / edit (draft) ... any user of the same agency (staff can prepare)
 *   approve / pay / cancel .. agency admin only — money leaves the agency, so
 *   delete (draft only)       it follows the ERP money-OUT rule (Expenses and
 *                             Agent Khata payouts are admin-only too)
 *
 * State checks (draft/approved/…) are repeated here so the UI can hide buttons
 * with @can, and again under a row lock in PaymentVoucherService.
 */
class PaymentVoucherPolicy
{
    private function sameAgency(User $user, PaymentVoucher $voucher): bool
    {
        return $user->agency_id !== null && (int) $user->agency_id === (int) $voucher->agency_id;
    }

    public function view(User $user, PaymentVoucher $voucher): bool
    {
        return $this->sameAgency($user, $voucher);
    }

    public function create(User $user): bool
    {
        return $user->agency_id !== null;
    }

    public function update(User $user, PaymentVoucher $voucher): bool
    {
        return $this->sameAgency($user, $voucher) && $voucher->isEditable();
    }

    public function approve(User $user, PaymentVoucher $voucher): bool
    {
        return $this->sameAgency($user, $voucher) && $voucher->canApprove() && $user->isAgencyAdmin();
    }

    public function pay(User $user, PaymentVoucher $voucher): bool
    {
        return $this->sameAgency($user, $voucher) && $voucher->canPay() && $user->isAgencyAdmin();
    }

    public function cancel(User $user, PaymentVoucher $voucher): bool
    {
        return $this->sameAgency($user, $voucher) && $voucher->canCancel() && $user->isAgencyAdmin();
    }

    public function delete(User $user, PaymentVoucher $voucher): bool
    {
        return $this->sameAgency($user, $voucher) && $voucher->isDeletable() && $user->isAgencyAdmin();
    }
}
