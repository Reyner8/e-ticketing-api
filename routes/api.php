<?php

use App\Http\Controllers\Api\v1\ActivityLogController;
use App\Http\Controllers\Api\v1\ApprovalController;
use App\Http\Controllers\Api\v1\Assignment\TicketAssignmentController;
use App\Http\Controllers\Api\v1\Attachment\CommentAttachmentController;
use App\Http\Controllers\Api\v1\Attachment\ErrorReportAttachmentController;
use App\Http\Controllers\Api\v1\Attachment\FeatureRequestAttachmentController;
use App\Http\Controllers\Api\v1\Attachment\TicketAttachmentController;
use App\Http\Controllers\Api\v1\Comment\MentionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\ErrorController;
use App\Http\Controllers\Api\v1\TicketController;
use App\Http\Controllers\Api\v1\FeatureController;
use App\Http\Controllers\Api\v1\TIcketConversionController;
use App\Http\Controllers\Api\v1\Comment\TicketCommentController;
use App\Http\Controllers\Api\v1\Comment\FeatureRequestCommentController;
use App\Http\Controllers\Api\v1\Comment\ErrorReportCommentController;
use App\Http\Controllers\Api\v1\DowntimeRecordController;
use App\Http\Controllers\Api\v1\StatusHistory\ErrorReportStatusHistoryController;
use App\Http\Controllers\Api\v1\StatusHistory\FeatureRequestStatusHistoryController;
use App\Http\Controllers\Api\v1\StatusHistory\TicketStatusHistoryController;

Route::prefix('v1')->group(function () {
    require __DIR__ . '/auth.php';

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::middleware('role:admin,it_staff,reporter,team_lead')->group(function () {
            //ticket routes
            Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
            Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

            //error report routes
            Route::get('/error-reports', [ErrorController::class, 'index'])->name('error-reports.index');
            Route::get('/error-reports/{error}', [ErrorController::class, 'show'])->name('error-reports.show');

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
        });

        Route::middleware('role:it_staff')->group(function () {
            //ticket routes
            Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
            Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
            Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.delete');
            Route::post('/tickets/{ticket}/convert/error-report', [TicketConversionController::class, 'toErrorReport'])
                ->name('tickets.convert.error-report');
            Route::post('/tickets/{ticket}/convert/feature-request', [TicketConversionController::class, 'toFeatureRequest'])
                ->name('tickets.convert.feature-request');

            //error report routes
            Route::post('/error-reports', [ErrorController::class, 'store'])->name('error-reports.store');
            Route::put('/error-reports/{error}', [ErrorController::class, 'update'])->name('error-reports.update');
            Route::delete('/error-reports/{error}', [ErrorController::class, 'destroy'])->name('error-reports.delete');

            //feature request routes
            Route::post('/feature-requests', [FeatureController::class, 'store'])->name('feature-requests.store');
            Route::put('/feature-requests/{feature}', [FeatureController::class, 'update'])->name('feature-requests.update');
            Route::delete('/feature-requests/{feature}', [FeatureController::class, 'destroy'])->name('feature-requests.delete');

            //approval routes
            Route::post('/tickets/{ticket}/approve', [ApprovalController::class, 'approveTicket'])->name('tickets.approve');
            Route::post('/tickets/{ticket}/reject', [ApprovalController::class, 'rejectTicket'])->name('tickets.reject');
            Route::post('/features/{feature}/approve', [ApprovalController::class, 'approveFeatureRequest'])->name('feature-request.approve');
            Route::post('/features/{feature}/reject', [ApprovalController::class, 'rejectFeatureRequest'])->name('feature-requests.reject');
            Route::post('/errors/{error}/approve', [ApprovalController::class, 'approveErrorReport'])->name('error-reports.approve');
            Route::post('/errors/{error}/reject', [ApprovalController::class, 'rejectErrorReport'])->name('error-reports.reject');

            //status history routes
            Route::patch('/tickets/{ticket}/status', [TicketStatusHistoryController::class, 'update']);
            Route::patch('/features/{feature}/status', [FeatureRequestStatusHistoryController::class, 'update']);
            Route::patch('/errors/{error}/status', [ErrorReportStatusHistoryController::class, 'update']);

            //assignment routes
            Route::post('/tickets/{ticket}/assign/user', [TicketAssignmentController::class, 'assignUser']);
            Route::post('/tickets/{ticket}/assign/team', [TicketAssignmentController::class, 'assignTeam']);
            Route::post('/tickets/{ticket}/unassign/user', [TicketAssignmentController::class, 'unassignUser']);
            Route::post('/tickets/{ticket}/unassign/team', [TicketAssignmentController::class, 'unassignTeam']);

            //activity log routes
            Route::get('tickets/{ticket}/activity-logs', [ActivityLogController::class, 'ticket']);
            Route::get('errors/{error}/activity-logs', [ActivityLogController::class, 'errorReport']);
            Route::get('features/{feature}/activity-logs', [ActivityLogController::class, 'featureRequest']);

            //downtime record routes
            Route::post('downtime-records', [DowntimeRecordController::class, 'store']);
            Route::put('downtime-records/{downtimeRecord}', [DowntimeRecordController::class, 'update']);
            Route::patch('downtime-records/{downtimeRecord}/resolve', [DowntimeRecordController::class, 'resolve']);
            Route::delete('downtime-records', [DowntimeRecordController::class, 'destroy']);

        });
    });
});
