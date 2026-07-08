<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\ApprovalStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSubmission\StorePublicSubmissionRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Attachment\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicSubmissionController extends Controller
{
    public function __construct(
        private readonly AttachmentService $attachmentService
    ) {}

    public function store(StorePublicSubmissionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $files = $request->file('files', []);
        $reporter = $this->getSystemReporter();

        Auth::onceUsingId($reporter->id);

        $ticket = DB::transaction(function () use ($data, $reporter, $files) {
            $ticket = Ticket::create([
                'id' => $this->generateTicketId(),
                'title' => $data['title'],
                'description' => $this->composeDescription($data),
                'category' => $data['category'],
                'priority' => $data['priority'],
                'status' => TicketStatus::PendingApproval->value,
                'approval_status' => ApprovalStatus::Pending->value,
                'reporter_id' => $reporter->id,
                'date_reported' => now(),
                'is_public_submission' => true,
                'submitter_name' => $data['submitter_name'],
                'submitter_email' => $data['submitter_email'],
                'submitter_phone' => $data['submitter_phone'] ?? null,
                'submitter_unit' => $data['submitter_unit'] ?? null,
            ]);

            foreach ($files as $file) {
                $this->attachmentService->upload($file, $ticket, $reporter->id);
            }

            return $ticket;
        });

        return ApiResponse::success(
            [
                'reference_number' => $ticket->id,
                'submission_type' => $data['submission_type'],
                'status' => $ticket->status->value,
                'submitted_at' => $ticket->date_reported->toIso8601String(),
            ],
            'Submission received. Our IT team will follow up shortly.',
            201
        );
    }

    private function composeDescription(array $data): string
    {
        $header = sprintf(
            "[Public Submission - %s]\nName: %s\nEmail: %s\nPhone: %s\nUnit: %s\n\n",
            strtoupper(str_replace('_', ' ', $data['submission_type'])),
            $data['submitter_name'],
            $data['submitter_email'],
            $data['submitter_phone'] ?? '-',
            $data['submitter_unit'] ?? '-'
        );

        return $header . $data['description'];
    }

    private function getSystemReporter(): User
    {
        $email = config('services.public_submission.system_reporter_email', 'public@system.local');

        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Public Submissions',
                'username' => 'public_submissions',
                'password' => bcrypt(Str::random(40)),
                'role' => UserRole::Reporter->value,
                'is_active' => false,
            ]
        );
    }

    private function generateTicketId(): string
    {
        return DB::transaction(function () {
            $prefix = 'PUB';
            $year = now()->format('Y');

            $lastTicket = Ticket::where('id', 'like', "{$prefix}-{$year}-%")
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $lastNumber = $lastTicket ? (int) substr($lastTicket->id, -4) : 0;
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

            return "{$prefix}-{$year}-{$newNumber}";
        });
    }
}
