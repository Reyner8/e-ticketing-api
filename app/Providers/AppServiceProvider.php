<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\ErrorReport;
use App\Models\FeatureRequest;
use App\Models\Ticket;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

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
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        RateLimiter::for('api', function () {
            return Limit::perMinute(60);
        });

        RateLimiter::for('public-submissions', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perHour(30)->by($request->ip()),
            ];
        });

        Relation::morphMap([
            'ticket' => Ticket::class,
            'error_report' => ErrorReport::class,
            'feature_request' => FeatureRequest::class,
            'comment' => Comment::class,
            'backup_restore_test' => \App\Models\BackupRestoreTest::class,
            'server_room_visitor' => \App\Models\ServerRoomVisitor::class,
            'server_room_inspection' => \App\Models\ServerRoomInspection::class,
        ]);
    }
}
