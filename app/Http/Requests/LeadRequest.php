<?php

namespace App\Http\Requests;

use App\Models\LeadField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorización en rutas y policy
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')->whereNull('deleted_at')],
            'source_id' => ['required', 'integer', Rule::exists('lead_sources', 'id')],
            'stage_id' => ['nullable', 'integer', Rule::exists('pipeline_stages', 'id')],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'priority' => ['nullable', Rule::in(array_keys(\App\Models\Lead::PRIORITIES))],
            'estimated_value' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
            'tags' => ['nullable', 'array', 'max:15'],
            'tags.*' => ['string', 'max:30'],
            'next_follow_up_at' => ['nullable', 'date'],
            'custom' => ['nullable', 'array'],
        ];

        foreach (LeadField::where('is_active', true)->get() as $field) {
            $key = "custom.{$field->key}";
            $base = match ($field->type) {
                'number' => ['numeric'],
                'email' => ['email:rfc', 'max:255'],
                'url' => ['url', 'max:2000'],
                'date' => ['date'],
                'checkbox' => ['boolean'],
                'select' => [Rule::in($field->options ?? [])],
                'textarea' => ['string', 'max:5000'],
                default => ['string', 'max:500'],
            };
            $rules[$key] = [$field->is_required && $field->type !== 'checkbox' ? 'required' : 'nullable', ...$base];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attrs = [];
        foreach (LeadField::all() as $field) {
            $attrs["custom.{$field->key}"] = mb_strtolower($field->label);
        }

        return $attrs;
    }
}
