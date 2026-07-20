<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamWorkloadSnapshotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team' => [
                'value' => $this->team->value,
                'label' => $this->team->label(),
            ],
            'tickets' => [
                'total' => $this->total_tickets,
                'open' => $this->open_tickets,
                'resolved' => $this->resolved_tickets,
                'overdue' => $this->overdue_tickets,
            ],
            'performance' => [
                'average_response_time' => $this->average_response_time ? [
                    'hours' => (float) $this->average_response_time,
                    'formatted' => $this->formatHours($this->average_response_time),
                ]: null,
                'average_resolution_time' => $this->average_resolution_time ? [
                    'hours' => (float) $this->average_resolution_time,
                    'formatted' => $this->formatHours($this->average_resolution_time),
                ]: null,
                'sla_compliance' => $this->sla_compliance ? (float) $this->sla_compliance : null,
            ],
            'workload_percentage' => $this->workload_percentage !== null
                ? (float) $this->workload_percentage
                : null,
            'snapshot_date' => $this->snapshot_date?->format('Y-m-d'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    // Helper
    private function formatHours(float|string|null $hours): ?string
    {
        if (is_null($hours)) {
            return null;
        }

        $hours =(float) $hours;
        $h = (int) $hours;
        $minutes = (int) round(($hours - $h) * 60);

        if ($h > 0 && $minutes > 0) {
            return "{$h}h {$minutes}m";
        }

        if ($h > 0) {
            return "{$h}h";
        }

        return "{$minutes}m";
    }
}
