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
use Illuminate\Support\Facades\Schema;
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
        // Older cPanel MySQL/MariaDB caps index keys at 767/1000 bytes. With
        // utf8mb4 a varchar(255) index overflows that, so cap default string
        // length to 191 (191 * 4 bytes = 764) for indexed/primary string columns.
        Schema::defaultStringLength(191);

        Gate::policy(Agent::class, AgentPolicy::class);
        Gate::policy(HrProfile::class, HrProfilePolicy::class);
        Gate::policy(EmbassyList::class, EmbassyListPolicy::class);

        // Feed the notification bell on the Tailwind agency layout.
        View::composer('layouts.agency-app', NotificationComposer::class);
    }
}
