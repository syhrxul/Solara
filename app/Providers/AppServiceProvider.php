<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ClassAssignment;
use App\Models\ClassSchedule;
use App\Models\FinanceTransaction;
use App\Models\Goal;
use App\Models\Habit;
use App\Models\Note;
use App\Models\Task;
use App\Observers\UserOwnedObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerObservers();

        // Fix Mixed Content Livewire error on Production (forcing HTTPS)
        if (str_starts_with(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }

    /**
     * Register model observers for auto user_id assignment.
     */
    protected function registerObservers(): void
    {
        Task::observe(UserOwnedObserver::class);
        Habit::observe(UserOwnedObserver::class);
        Note::observe(UserOwnedObserver::class);
        FinanceTransaction::observe(UserOwnedObserver::class);
        Goal::observe(UserOwnedObserver::class);
        Category::observe(UserOwnedObserver::class);
        ClassSchedule::observe(UserOwnedObserver::class);
        ClassAssignment::observe(UserOwnedObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
