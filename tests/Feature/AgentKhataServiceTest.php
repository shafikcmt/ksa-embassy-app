<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\AgentTransaction;
use App\Models\User;
use App\Services\AgentKhataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Focused coverage for the Agent Khata balance math.
 *
 * Sign convention (see AgentTransaction): amount is stored POSITIVE; `type`
 * gives the sign when summing — debit ("Paid", money OUT to agent) = +amount,
 * credit ("Received", money IN from agent) = −amount. An agent's balance is
 * live-computed as opening_balance + signed-sum(ledger):
 *   positive = agent owes agency (receivable)
 *   negative = agency owes agent (payable)
 *
 * These tests must run on MySQL (project setup): a pre-existing MySQL-only
 * migration breaks the SQLite default for RefreshDatabase feature tests.
 */
class AgentKhataServiceTest extends TestCase
{
    use RefreshDatabase;

    private AgentKhataService $khata;

    /** Valid users.id for the ledger's recorded_by FK. */
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->khata = app(AgentKhataService::class);
        $this->userId = User::factory()->create()->id;
    }

    private function makeAgency(string $slug): Agency
    {
        return Agency::create([
            'name'           => ucfirst($slug) . ' Agency',
            'slug'           => $slug,
            'status'         => 'active',
            'license_number' => 'LIC-' . strtoupper($slug) . '-0001',
        ]);
    }

    private function makeAgent(Agency $agency, string $name, float $opening = 0.0): Agent
    {
        return Agent::create([
            'agency_id'       => $agency->id,
            'name'            => $name,
            'phone'           => '0000000000',
            'address'         => 'N/A',
            'status'          => 'active',
            'opening_balance' => $opening,
        ]);
    }

    public function test_balance_for_applies_the_debit_plus_credit_minus_sign_convention(): void
    {
        $agency = $this->makeAgency('sign');
        $agent  = $this->makeAgent($agency, 'Sign Test', 0.0);

        // Debit ("Paid", money out to agent) → +1000 → agent owes agency.
        $this->khata->record($agent, AgentTransaction::TYPE_DEBIT, 1000, null, $this->userId);
        $this->assertSame(1000.00, $this->khata->balanceFor($agent->fresh()));

        // Credit ("Received", money in from agent) 1500 → 1000 - 1500 = -500 → agency owes agent.
        $this->khata->record($agent, AgentTransaction::TYPE_CREDIT, 1500, null, $this->userId);
        $this->assertSame(-500.00, $this->khata->balanceFor($agent->fresh()));
    }

    public function test_balances_for_agency_totals_split_into_receivable_payable_and_net(): void
    {
        $agency = $this->makeAgency('totals');

        // Positive (+1000), positive (+2000), negative (-500), settled (0).
        $recvA = $this->makeAgent($agency, 'Recv A', 0.0);
        $this->khata->record($recvA, AgentTransaction::TYPE_DEBIT, 1000, null, $this->userId);

        $recvB = $this->makeAgent($agency, 'Recv B', 0.0);
        $this->khata->record($recvB, AgentTransaction::TYPE_DEBIT, 2000, null, $this->userId);

        $payA = $this->makeAgent($agency, 'Pay A', 0.0);
        $this->khata->record($payA, AgentTransaction::TYPE_CREDIT, 500, null, $this->userId);

        $settled = $this->makeAgent($agency, 'Settled', 0.0);
        $this->khata->record($settled, AgentTransaction::TYPE_DEBIT, 750, null, $this->userId);
        $this->khata->record($settled, AgentTransaction::TYPE_CREDIT, 750, null, $this->userId);

        $balances = $this->khata->balancesForAgency($agency->id);

        // Same aggregation the controller/index performs over the balances collection.
        $receivable = (float) $balances->filter(fn ($b) => $b > 0)->sum();
        $payable    = abs((float) $balances->filter(fn ($b) => $b < 0)->sum());
        $net        = $receivable - $payable;

        $this->assertSame(3000.00, $receivable);        // 1000 + 2000
        $this->assertSame(500.00, $payable);            // abs(-500)
        $this->assertSame(2500.00, $net);               // 3000 - 500
        $this->assertSame(0.00, (float) $balances[$settled->id]);
    }

    public function test_balances_are_isolated_per_agency(): void
    {
        $agency1 = $this->makeAgency('tenant-one');
        $agency2 = $this->makeAgency('tenant-two');

        $agentA = $this->makeAgent($agency1, 'Agency 1 Agent', 0.0);
        $this->khata->record($agentA, AgentTransaction::TYPE_DEBIT, 1000, null, $this->userId);

        $agentB = $this->makeAgent($agency2, 'Agency 2 Agent', 0.0);
        $this->khata->record($agentB, AgentTransaction::TYPE_DEBIT, 9999, null, $this->userId);

        // Per-agent balance is scoped and unaffected by the other tenant.
        $this->assertSame(1000.00, $this->khata->balanceFor($agentA->fresh()));

        // Agency-wide balances only contain agency 1's agent.
        $balances = $this->khata->balancesForAgency($agency1->id);
        $this->assertCount(1, $balances);
        $this->assertTrue($balances->has($agentA->id));
        $this->assertFalse($balances->has($agentB->id));
        $this->assertSame(1000.00, (float) $balances[$agentA->id]);
        $this->assertSame(1000.00, (float) $balances->sum());
    }
}
