<?php

namespace App\Http\Requests\ServerRoomInspection;

use App\Enums\InspectionConclusion;
use App\Enums\InspectionEscalation;
use App\Enums\InspectionType;
use App\Services\Ops\ServerRoomInspectionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServerRoomInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemRules = [];
        foreach (ServerRoomInspectionService::CHECKLIST_KEYS as $key) {
            $itemRules["checklist_items.{$key}"] = ['sometimes', 'array'];
            $itemRules["checklist_items.{$key}.ok"] = ['required_with:checklist_items.'.$key, 'boolean'];
            $itemRules["checklist_items.{$key}.notes"] = ['nullable', 'string'];
        }

        return [
            'inspection_date' => ['sometimes', 'date'],
            'inspector_id' => ['sometimes', 'integer', 'exists:users,id'],
            'inspection_type' => ['sometimes', 'string', Rule::in(InspectionType::values())],
            'checklist_items' => ['sometimes', 'array'],
            ...$itemRules,
            'conclusion' => ['sometimes', 'string', Rule::in(InspectionConclusion::values())],
            'follow_up' => ['nullable', 'string'],
            'escalation' => ['nullable', 'string', Rule::in(InspectionEscalation::values())],
            'notes' => ['nullable', 'string'],
        ];
    }
}
