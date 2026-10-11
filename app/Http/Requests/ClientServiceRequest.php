<?php

namespace App\Http\Requests;

use App\Models\ClientService;
use App\Models\Proposal;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // los permisos van en el middleware de las rutas
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->whereNull('deleted_at')],
            'proposal_id' => ['nullable', 'integer', Rule::exists('proposals', 'id')->whereNull('deleted_at')],
            'catalog_service_id' => ['nullable', 'integer', 'exists:services,id'],
            'parent_id' => ['nullable', 'integer', Rule::exists('client_services', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'billing_cycle' => ['required', Rule::in(array_keys(ClientService::CYCLES))],
            'currency' => ['required', Rule::in(['CLP', 'UF'])],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'auto_renew' => ['boolean'],
            'status' => ['required', Rule::in(array_keys(ClientService::STATUSES))],
            'billing_day' => ['nullable', 'integer', 'between:1,31'],
            'reminder_offsets' => ['nullable', 'array', 'max:8'],
            'reminder_offsets.*' => ['integer', 'between:-60,90'],
            'payment_link' => ['nullable', 'url', 'max:500'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $d = $this->validated();

            if (! empty($d['parent_id'])) {
                $parent = ClientService::find($d['parent_id']);
                $self = $this->route('clientService');
                if ($parent && $parent->client_id !== (int) $d['client_id']) {
                    $v->errors()->add('parent_id', 'El servicio principal debe ser de la misma empresa.');
                }
                if ($parent && $parent->parent_id) {
                    $v->errors()->add('parent_id', 'Elige un servicio principal (no uno que ya sea asociado de otro).');
                }
                if ($self && $self instanceof ClientService && ($self->id === $parent?->id || $self->children()->exists())) {
                    $v->errors()->add('parent_id', 'Un servicio con servicios asociados no puede depender de otro.');
                }
            }

            if (! empty($d['proposal_id']) && ($p = Proposal::find($d['proposal_id'])) && $p->client_id && $p->client_id !== (int) $d['client_id']) {
                $v->errors()->add('proposal_id', 'La propuesta pertenece a otra empresa.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        foreach (['proposal_id', 'catalog_service_id', 'parent_id', 'end_date', 'billing_day', 'payment_link', 'description', 'internal_notes'] as $k) {
            if ($this->input($k) === '') {
                $this->merge([$k => null]);
            }
        }
    }
}
