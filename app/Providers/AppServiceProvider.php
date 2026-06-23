<?php

namespace App\Providers;

use App\Models\Agent;
use App\Models\EmbassyList;
use App\Models\HrProfile;
use App\Policies\AgentPolicy;
use App\Policies\EmbassyListPolicy;
use App\Policies\HrProfilePolicy;
use App\View\Composers\NotificationComposer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Agent::class, AgentPolicy::class);
        Gate::policy(HrProfile::class, HrProfilePolicy::class);
        Gate::policy(EmbassyList::class, EmbassyListPolicy::class);

        // Feed the notification bell on the Tailwind agency layout.
        View::composer('layouts.agency-app', NotificationComposer::class);
    }
}
