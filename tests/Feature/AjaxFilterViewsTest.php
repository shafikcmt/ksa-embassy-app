<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AjaxFilterViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_opted_in_lists_return_complete_html_regions_for_ajax_and_normal_gets(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $agency = Agency::create(['name' => 'Filter Agency', 'slug' => 'filter-agency', 'status' => 'active']);
        $plan = Plan::create(['name' => 'Filters', 'slug' => 'filters', 'price' => 0, 'duration_days' => 365, 'is_active' => true]);
        Subscription::create(['agency_id' => $agency->id, 'plan_id' => $plan->id, 'start_date' => now()->subDay(), 'end_date' => now()->addYear(), 'status' => 'active', 'payment_status' => 'paid', 'amount' => 0]);
        $admin = User::factory()->create(['agency_id' => $agency->id, 'is_active' => true, 'is_super_admin' => false]);
        $admin->assignRole('agency_admin');
        $super = User::factory()->create(['is_active' => true, 'is_super_admin' => true]);

        $pages = [
            'erp.mofa' => 'erp-mofa', 'erp.bmet.index' => 'erp-bmet', 'erp.medical' => 'erp-medical',
            'erp.visa-stamping.index' => 'erp-visa-stamping', 'erp.delivery' => 'erp-delivery',
            'erp.double-mofa' => 'erp-double-mofa', 'erp.invoices.index' => 'erp-invoices',
            'erp.payment-vouchers.index' => 'erp-payment-vouchers', 'erp.due-list' => 'erp-due-list',
            'erp.reports' => 'erp-reports', 'erp.profit-loss' => 'erp-profit-loss',
            'hr.index' => 'agency-hr', 'agents.index' => 'agency-agents',
            'embassy-lists.index' => 'agency-embassy-lists', 'notes.index' => 'agency-notes',
            'super-admin.agencies.index' => 'super-admin-agencies', 'super-admin.agents.index' => 'super-admin-agents',
            'super-admin.hr.index' => 'super-admin-hr', 'super-admin.embassy-lists.index' => 'super-admin-embassy-lists',
            'super-admin.subscriptions.index' => 'super-admin-subscriptions',
        ];
        foreach ($pages as $route => $group) {
            $this->actingAs(str_starts_with($route, 'super-admin.') ? $super : $admin);
            foreach ([[], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'text/html']] as $headers) {
                $response = $this->get(route($route), $headers)->assertOk();
                $dom = new DOMDocument();
                @$dom->loadHTML($response->getContent());
                $xpath = new DOMXPath($dom);
                $forms = $xpath->query('//form[@data-ajax-filter and @data-ajax-group="'.$group.'"]');
                $this->assertGreaterThan(0, $forms->length, $route);
                foreach ($forms as $form) {
                    $this->assertSame('get', strtolower($form->getAttribute('method')), $route);
                    $this->assertSame(0, $xpath->query('.//*[@onchange[contains(., "form.submit")]]', $form)->length, $route);
                    $this->assertSame(0, $xpath->query('.//button[@type="submit" and not(@name) and not(@hidden) and not(ancestor::noscript)]', $form)->length, 'Apply buttons should be no-JS only: '.$route);
                }
                $regions = $xpath->query('//*[@data-ajax-region and @data-ajax-group="'.$group.'"]');
                $this->assertGreaterThan(0, $regions->length, $route);
                $parts = [];
                foreach ($regions as $region) {
                    $parts[] = $region->getAttribute('data-ajax-region');
                    $this->assertSame(0, $xpath->query('.//*[@data-ajax-region]', $region)->length, 'Regions must not nest: '.$route);
                }
                $this->assertCount(count(array_unique($parts)), $parts, $route);
            }
        }
        // Modal stays available when AJAX changes an empty invoice list to a populated one.
        $this->actingAs($admin)->get(route('erp.invoices.index'))->assertSee('x-on:invoice-delete.window', false);
        $this->get(route('notes.index', ['view' => 'trash']))->assertOk()->assertSee('data-ajax-region="results"', false);
        $this->get(route('attendance.index'))->assertOk()->assertDontSee('data-ajax-filter', false);
    }
}
