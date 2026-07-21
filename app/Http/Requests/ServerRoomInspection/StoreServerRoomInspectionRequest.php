<?php

namespace App\Http\Requests\ServerRoomInspection;

use App\Enums\InspectionConclusion;
use App\Enums\InspectionEscalation;
use App\Enums\InspectionType;
use App\Services\Ops\ServerRoomInspectionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServerRoomInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemRules = [];
        foreach (ServerRoomInspectionService::CHECKLIST_KEYS as $key) {
            $itemRules["checklist_items.{$key}"] = ['required', 'array'];
            $itemRules["checklist_items.{$key}.ok"] = ['required', 'boolean'];
            $itemRules["checklist_items.{$key}.notes"] = ['nullable', 'string'];
        }

        return [
            'inspection_date' => ['required', 'date'],
            'inspector_id' => ['nullable', 'integer', 'exists:users,id'],
            'inspection_type' => ['required', 'string', Rule::in(InspectionType::values())],
            'checklist_items' => ['required', 'array'],
            ...$itemRules,
            'conclusion' => ['required', 'string', Rule::in(InspectionConclusion::values())],
            'follow_up' => ['nullable', 'string'],
            'escalation' => ['nullable', 'string', Rule::in(InspectionEscalation::values())],
            'notes' => ['nullable', 'string'],
        ];
    }
}
