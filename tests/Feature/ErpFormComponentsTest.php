<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/** x-erp.section / x-erp.modal: default rendering is unchanged; compact options are opt-in only. */
class ErpFormComponentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
    }

    public function test_section_default_rendering_is_unchanged(): void
    {
        $html = Blade::render('<x-erp.section icon="bi-x" title="T"><input></x-erp.section>');
        $this->assertStringContainsString('class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5"', $html);
        $this->assertStringContainsString('class="mb-4 flex flex-wrap items-center justify-between gap-2"', $html);
        $this->assertStringContainsString('class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"', $html);
        $this->assertStringContainsString('class="grid grid-cols-1 gap-4 sm:grid-cols-2"', Blade::render('<x-erp.section title="T" cols="2"><i></i></x-erp.section>'));
    }

    public function test_section_dense_four_column_is_opt_in(): void
    {
        $html = Blade::render('<x-erp.section icon="bi-x" title="T" cols="4" dense><input></x-erp.section>');
        $this->assertStringContainsString('class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4"', $html);
        $this->assertStringContainsString('class="mb-2.5 flex flex-wrap items-center justify-between gap-2"', $html);
        $this->assertStringContainsString('class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"', $html);
    }

    public function test_modal_default_rendering_is_unchanged_and_wide_dense_is_opt_in(): void
    {
        foreach (['overlay', 'dialog'] as $kind) {
            $default = Blade::render('<x-erp.modal kind="'.$kind.'"><p>b</p></x-erp.modal>');
            $this->assertStringContainsString('sm:max-h-[90vh] sm:max-w-3xl sm:rounded-2xl', $default);
            $this->assertStringContainsString('flex-col gap-4 overflow-y-auto bg-slate-50 p-4 sm:p-5 [&>*]:shrink-0', $default);
            $this->assertStringNotContainsString('max-w-5xl', $default);

            $compact = Blade::render('<x-erp.modal kind="'.$kind.'" size="wide" dense><p>b</p></x-erp.modal>');
            $this->assertStringContainsString('sm:max-h-[90vh] sm:max-w-5xl sm:rounded-2xl', $compact);
            $this->assertStringContainsString('flex-col gap-3 overflow-y-auto bg-slate-50 p-3 sm:p-4 [&>*]:shrink-0', $compact);
        }
    }
}
