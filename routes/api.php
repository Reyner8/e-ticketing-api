<?php

use App\Http\Controllers\Api\v1\ActivityLogController;
use App\Http\Controllers\Api\v1\ApprovalController;
use App\Http\Controllers\Api\v1\Assignment\ErrorReportAssignmentController;
use App\Http\Controllers\Api\v1\Assignment\FeatureRequestAssignmentController;
use App\Http\Controllers\Api\v1\Attachment\CommentAttachmentController;
use App\Http\Controllers\Api\v1\Attachment\ErrorReportAttachmentController;
use App\Http\Controllers\Api\v1\Attachment\FeatureRequestAttachmentController;
use App\Http\Controllers\Api\v1\Attachment\TicketAttachmentController;
use App\Http\Controllers\Api\v1\CalendarEventController;
use App\Http\Controllers\Api\v1\Comment\MentionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\TicketController;
use App\Http\Controllers\Api\v1\FeatureController;
use App\Http\Controllers\Api\v1\TIcketConversionController;
use App\Http\Controllers\Api\v1\Comment\TicketCommentController;
use App\Http\Controllers\Api\v1\Comment\FeatureRequestCommentController;
use App\Http\Controllers\Api\v1\Comment\ErrorReportCommentController;
use App\Http\Controllers\Api\v1\ConversionHistoryController;
use App\Http\Controllers\Api\v1\DowntimeAffectedSystemController;
use App\Http\Controllers\Api\v1\DowntimeRecordController;
use App\Http\Controllers\Api\v1\ErrorReportController;
use App\Http\Controllers\Api\v1\MilestoneController;
use App\Http\Controllers\Api\v1\NotificationController;
use App\Http\Controllers\Api\v1\PublicSubmissionController;
use App\Http\Controllers\Api\v1\StatusHistory\ErrorReportStatusHistoryController;
use App\Http\Controllers\Api\v1\StatusHistory\FeatureRequestStatusHistoryController;
use App\Http\Controllers\Api\v1\StatusHistory\TicketStatusHistoryController;
use App\Http\Controllers\Api\v1\SystemConfigurationController;
use App\Http\Controllers\Api\v1\TagController;
use App\Http\Controllers\Api\v1\TeamWorkloadSnapshotController;
use App\Http\Controllers\Api\v1\Ticket\MergedTicketController;
use App\Http\Controllers\Api\v1\TicketWatcherController;
use App\Http\Controllers\Api\v1\TimelineEntryController;
use App\Http\Controllers\Api\v1\UserController;

Route::prefix('v1')->group(function () {
    require __DIR__ . '/auth.php';

    Route::middleware(['public.api_key', 'throttle:public-submissions'])
        ->post('/public/submit', [PublicSubmissionController::class, 'store'])
        ->name('public.submit');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::middleware('role:admin,it_staff,reporter,team_lead')->group(function () {
            //user routes
            Route::get('me', [UserController::class, 'me']);
            Route::patch('me/preferences', [UserController::class, 'updateMyPreferences']);

            //ticket routes
            Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
            Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
            Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

            //error report routes
            Route::get('/error-reports', [ErrorReportController::class, 'index'])->name('error-reports.index');
            Route::get('/error-reports/{error}', [ErrorReportController::class, 'show'])->name('error-reports.show');

            //feature request routes
            Route::get('/feature-requests', [FeatureController::class, 'index'])->name('feature-requests.index');
            Route::get('/feature-requests/{feature}', [FeatureController::class, 'show'])->name('feature-requests.show');

            //comment routes
            Route::apiResource('tickets.comments', TicketCommentController::class)->only(['index', 'store', 'destroy']);
            Route::apiResource('errors.comments', ErrorReportCommentController::class)->only(['index', 'store', 'destroy']);
            Route::apiResource('features.comments', FeatureRequestCommentController::class)->only(['index', 'store', 'destroy']);

            //mentions routes
            Route::get('/comments/{comment}/mentions', [MentionController::class, 'index'])->name('comment-mentions.index');
            Route::get('/mentions/me', [MentionController::class, 'mine'])->name('comment-mentions.me');

            //attachment routes
            Route::apiResource('tickets.attachments', TicketAttachmentController::class)->only(['index', 'store', 'destroy']);
            Route::apiResource('errors.attachments', ErrorReportAttachmentController::class)->only(['index', 'store', 'destroy']);
            Route::apiResource('features.attachments', FeatureRequestAttachmentController::class)->only(['index', 'store', 'destroy']);
            Route::apiResource('comments.attachments', CommentAttachmentController::class)->only(['index', 'store', 'destroy']);

            //status history routes
            Route::get('tickets/{ticket}/status', [TicketStatusHistoryController::class, 'index'])->name('ticket.status');
            Route::get('features/{feature}/status', [FeatureRequestStatusHistoryController::class, 'index'])->name('features.status');
            Route::get('errors/{error}/status', [ErrorReportStatusHistoryController::class, 'index'])->name('errors.status');

            //downtime record routes
            Route::get('downtime-records', [DowntimeRecordController::class, 'index']);
            Route::get('downtime-records/{downtimeRecord}', [DowntimeRecordController::class, 'show']);

            //milestone routes
            Route::get('feature-requests/{feature}/milestones', [MilestoneController::class, 'index']);
            Route::get('feature-requests/{feature}/milestones/{milestone}', [MilestoneController::class, 'show']);

            //timeline routes
            Route::get('feature-requests/{feature}/timelines', [TimelineEntryController::class, 'index']);
            Route::get('feature-requests/{feature}/timelines/{entry}', [TimelineEntryController::class, 'show']);

            //tag routes
            Route::get('tags', [TagController::class, 'index']);
            Route::get('tags/{tag}', [TagController::class, 'show']);

            //notification routes
            Route::get('/notifications', [NotificationController::class, 'index']);
            Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
            Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
            Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::patch('/notifications/read-many', [NotificationController::class, 'markManyAsRead']);
            Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
            Route::delete('/notifications/read', [NotificationController::class, 'deleteAllRead']);
            Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

            //ticket watcher routes
            Route::get('/tickets/{ticket}/watchers', [TicketWatcherController::class, 'index']);
            Route::get('/tickets/{ticket}/watch/status', [TicketWatcherController::class, 'status']);
            Route::get('/me/watched-tickets', [TicketWatcherController::class, 'watchedTickets']);
            Route::post('/tickets/{ticket}/watch', [TicketWatcherController::class, 'toggleWatch']);

            //conversion history routes
            Route::get('/conversion-history', [ConversionHistoryController::class, 'index']);
            Route::get('/conversion-history/{history}', [ConversionHistoryController::class, 'show']);
            Route::get('/tickets/{ticket}/conversion-history', [ConversionHistoryController::class, 'byTicket']);

            //merge ticket routes
            Route::get('/tickets/{ticket}/merge', [MergedTicketController::class, 'index']);

            //calendar event routes
            Route::get('/calendar-events', [CalendarEventController::class, 'index']);
            Route::get('/calendar-events/calendar', [CalendarEventController::class, 'calendar']);
            Route::get('/calendar-events/upcoming', [CalendarEventController::class, 'upcoming']);
            Route::get('/calendar-events/{event}', [CalendarEventController::class, 'show']);
        });

        Route::middleware('role:admin')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{user}', [UserController::class, 'show']);
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
            Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
            Route::patch('/users/{user}/preferences', [UserController::class, 'updatePreferences']);
        });

        Route::middleware('role:team_lead')->group(function () {
            //approval routes
            Route::post('/tickets/{ticket}/approve', [ApprovalController::class, 'approveTicket'])->name('tickets.approve');
            Route::post('/tickets/{ticket}/reject', [ApprovalController::class, 'rejectTicket'])->name('tickets.reject');
            Route::post('/features/{feature}/approve', [ApprovalController::class, 'approveFeatureRequest'])->name('feature-request.approve');
            Route::post('/features/{feature}/reject', [ApprovalController::class, 'rejectFeatureRequest'])->name('feature-requests.reject');
            Route::post('/errors/{error}/approve', [ApprovalController::class, 'approveErrorReport'])->name('error-reports.approve');
            Route::post('/errors/{error}/reject', [ApprovalController::class, 'rejectErrorReport'])->name('error-reports.reject');
            
            //feature request assignment routes
            Route::post('/features/{feature}/assign/user', [FeatureRequestAssignmentController::class, 'assignUser']);
            Route::post('/features/{feature}/assign/team', [FeatureRequestAssignmentController::class, 'assignTeam']);
            Route::post('/features/{feature}/unassign/user', [FeatureRequestAssignmentController::class, 'unassignUser']);
            Route::post('/features/{feature}/unassign/team', [FeatureRequestAssignmentController::class, 'unassignTeam']);

            //error report assignment routes
            Route::post('/errors/{error}/assign/user', [ErrorReportAssignmentController::class, 'assignUser']);
            Route::post('/errors/{error}/assign/team', [ErrorReportAssignmentController::class, 'assignTeam']);
            Route::post('/errors/{error}/unassign/user', [ErrorReportAssignmentController::class, 'unassignUser']);
            Route::post('/errors/{error}/unassign/team', [ErrorReportAssignmentController::class, 'unassignTeam']);
        });

        Route::middleware('role:it_staff')->group(function () {
            //ticket routes
            Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
            Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.delete');
            Route::post('/tickets/{ticket}/convert/error-report', [TicketConversionController::class, 'toErrorReport'])
                ->name('tickets.convert.error-report');
            Route::post('/tickets/{ticket}/convert/feature-request', [TicketConversionController::class, 'toFeatureRequest'])
                ->name('tickets.convert.feature-request');

            //error report routes
            Route::post('/error-reports', [ErrorReportController::class, 'store'])->name('error-reports.store');
            Route::put('/error-reports/{error}', [ErrorReportController::class, 'update'])->name('error-reports.update');
            Route::delete('/error-reports/{error}', [ErrorReportController::class, 'destroy'])->name('error-reports.delete');

            //feature request routes
            Route::post('/feature-requests', [FeatureController::class, 'store'])->name('feature-requests.store');
            Route::put('/feature-requests/{feature}', [FeatureController::class, 'update'])->name('feature-requests.update');
            Route::delete('/feature-requests/{feature}', [FeatureController::class, 'destroy'])->name('feature-requests.delete');

            //status history routes
            Route::patch('/tickets/{ticket}/status', [TicketStatusHistoryController::class, 'update']);
            Route::patch('/features/{feature}/status', [FeatureRequestStatusHistoryController::class, 'update']);
            Route::patch('/errors/{error}/status', [ErrorReportStatusHistoryController::class, 'update']);

            //activity log routes
            Route::get('tickets/{ticket}/activity-logs', [ActivityLogController::class, 'ticket']);
            Route::get('errors/{error}/activity-logs', [ActivityLogController::class, 'errorReport']);
            Route::get('features/{feature}/activity-logs', [ActivityLogController::class, 'featureRequest']);

            //downtime record routes
            Route::post('downtime-records', [DowntimeRecordController::class, 'store']);
            Route::put('downtime-records/{downtimeRecord}', [DowntimeRecordController::class, 'update']);
            Route::patch('downtime-records/{downtimeRecord}/resolve', [DowntimeRecordController::class, 'resolve']);
            Route::delete('downtime-records/{downtimeRecord}', [DowntimeRecordController::class, 'destroy']);

            //milestone routes
            Route::post('feature-requests/{feature}/milestones', [MilestoneController::class, 'store']);
            Route::put('feature-requests/{feature}/milestones/{milestone}', [MilestoneController::class, 'update']);
            Route::patch('feature-requests/{feature}/milestones/{milestone}/progress', [MilestoneController::class, 'updateProgress']);
            Route::patch('feature-requests/{feature}/milestones/{milestone}/complete', [MilestoneController::class, 'complete']);
            Route::delete('feature-requests/{feature}/milestones/{milestone}', [MilestoneController::class, 'destroy']);

            //timeline routes
            Route::post('feature-requests/{feature}/timelines', [TimelineEntryController::class, 'store']);
            Route::put('feature-requests/{feature}/timelines/{entry}', [TimelineEntryController::class, 'update']);
            Route::patch('feature-requests/{feature}/timelines/{entry}/progress', [TimelineEntryController::class, 'updateProgress']);
            Route::patch('feature-requests/{feature}/timelines/{entry}/complete', [TimelineEntryController::class, 'complete']);
            Route::delete('feature-requests/{feature}/timelines/{entry}', [TimelineEntryController::class, 'destroy']);

            //tag routes
            Route::post('tags', [TagController::class, 'store']);
            Route::put('tags/{tag}', [TagController::class, 'update']);
            Route::delete('tags/{tag}', [TagController::class, 'destroy']);
            Route::post('{resourceType}/{resourceId}/tags/attach', [TagController::class, 'attach'])->where('resourceType', 'tickets|features|errors');
            Route::post('{resourceType}/{resourceId}/tags/detach', [TagController::class, 'detach'])->where('resourceType', 'tickets|features|errors');
            Route::put('{resourceType}/{resourceId}/tags/sync', [TagController::class, 'sync'])->where('resourceType', 'tickets|features|errors');

            //ticket watcher routes
            Route::post('tickets/{ticket}/watchers', [TicketWatcherController::class, 'addWatcher']);
            Route::delete('tickets/{ticket}/watchers/{userId}', [TicketWatcherController::class, 'removeWatcher']);

            //merge ticket routes
            Route::post('/tickets/{ticket}/merge', [MergedTicketController::class, 'mergeTicket']);
            Route::delete('tickets/{ticket}/merge/{mergedTicketId}', [MergedTicketController::class, 'unmergeTicket']);

            //downtime system affected routes
            Route::get('/downtime-records/{downtime}/affected-systems', [DowntimeAffectedSystemController::class, 'index']);
            Route::post('/downtime-records/{downtime}/affected-systems', [DowntimeAffectedSystemController::class, 'store']);
            Route::put('/downtime-records/{downtime}/affected-systems', [DowntimeAffectedSystemController::class, 'sync']);
            Route::delete('/downtime-records/{downtime}/affected-systems', [DowntimeAffectedSystemController::class, 'destroy']);

            //calendar event routes
            Route::post('/calendar-events/', [CalendarEventController::class, 'store']);
            Route::post('/calendar-events/{event}', [CalendarEventController::class, 'update']);
            Route::delete('/calendar-events/{event}', [CalendarEventController::class, 'destroy']);

            //system configuration routes
            Route::get('/system-configuration', [SystemConfigurationController::class, 'index']);
            Route::get('/system-configuration/key/{key}', [SystemConfigurationController::class, 'showByKey']);
            Route::get('/system-configuration/{config}', [SystemConfigurationController::class, 'show']);
            Route::post('/system-configuration', [SystemConfigurationController::class, 'store']);
            Route::put('/system-configuration/{config}', [SystemConfigurationController::class, 'update']);
            Route::put('/system-configuration/key/{key}', [SystemConfigurationController::class, 'upsert']);
            Route::delete('/system-configuration/{config}', [SystemConfigurationController::class, 'destroy']);
            Route::post('/system-configuration/cache/clear', [SystemConfigurationController::class, 'clearCache']);

            //team workload snapshot routes
            Route::get('/team-workload', [TeamWorkloadSnapshotController::class, 'index']);
            Route::get('/team-workload/latest', [TeamWorkloadSnapshotController::class, 'latest']);
            Route::get('/team-workload/compare', [TeamWorkloadSnapshotController::class, 'compare']);
            Route::get('/team-workload/{team}/history', [TeamWorkloadSnapshotController::class, 'history']);
            Route::get('/team-workload/{snapshot}', [TeamWorkloadSnapshotController::class, 'show']);
            Route::post('/team-workload/generate', [TeamWorkloadSnapshotController::class, 'generate']);
        });
    });
});
