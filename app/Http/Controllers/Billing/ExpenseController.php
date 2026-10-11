<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\ClientService;
use App\Models\ServiceExpense;
use App\Services\Billing\ContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Costos y gastos de un servicio contratado: información interna (permiso contracts.costs). */
class ExpenseController extends Controller
{
    public function store(Request $request, ClientService $clientService, ContractService $contracts): RedirectResponse
    {
        $data = $request->validate([
            'concept' => ['required', 'string', 'max:255'],
            'currency' => ['required', Rule::in(['CLP', 'UF'])],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'incurred_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $contracts->addExpense($clientService, $data, $request->user());
        $this->toast('Gasto registrado.');

        return back();
    }

    public function destroy(ServiceExpense $expense): RedirectResponse
    {
        $expense->delete();
        $this->toast('Gasto eliminado.');

        return back();
    }
}
