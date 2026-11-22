<?php

namespace App\Providers;

use App\Contracts\FamilyNewsProvider;
use App\Models\DailyNote;
use App\Models\FamilyUpdate;
use App\Models\FamilyVisits\VisitRequest;
use App\Models\Resident;
use App\Policies\DailyNotePolicy;
use App\Policies\FamilyUpdatePolicy;
use App\Policies\FamilyVisitPolicy;
use App\Policies\ResidentPolicy;
use App\Services\FamilyVisits\DummyFamilyNewsProvider;
use App\Services\SupabaseSession;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();
        $this->app->bind(FamilyNewsProvider::class, DummyFamilyNewsProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Resident::class, ResidentPolicy::class);
        Gate::policy(VisitRequest::class, FamilyVisitPolicy::class);
        Gate::policy(DailyNote::class, DailyNotePolicy::class);
        Gate::policy(FamilyUpdate::class, FamilyUpdatePolicy::class);
        Auth::viaRequest('supabase', fn ($request) => app(SupabaseSession::class)->user($request));
        $this->configureAuthRateLimiters();
        $this->configureDefaults();
    }

    /**
     * PAWL12: a credential attempt is limited per normalized email + client IP.
     * Middleware emits HTTP 429 and Retry-After instead of a validation redirect.
     * Hash cache keys to avoid storing raw email addresses in cache metadata.
     */
    protected function configureAuthRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $input = $request->input('email');
            $email = Str::lower(trim(is_string($input) ? $input : ''));

            return Limit::perMinute(5)
                ->by('login:'.hash('sha256', $email.'|'.$request->ip()))
                ->response(fn (Request $request, array $headers) => $request->expectsJson()
                    ? response()->json(['message' => 'Terlalu banyak percobaan. Coba lagi dalam satu menit.'], 429, $headers)
                    : response()->view('errors.too-many-requests', [], 429, $headers));
        });

        RateLimiter::for('password-recovery', function (Request $request) {
            $input = $request->input('email');
            $email = Str::lower(trim(is_string($input) ? $input : ''));

            return Limit::perMinute(5)->by('password-recovery:'.hash('sha256', $email.'|'.$request->ip()));
        });
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
            : null,
        );
    }
}
