<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers\Desk;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Enums\ProcessorKind;
use Vipertecpro\DataRightsDesk\Models\Processor;

/** s.8(2), Rule 6(1)(f), s.11(1)(b): who the app shares personal data with. */
class ProcessorController
{
    public function __construct(private DataRightsManager $dataRights) {}

    public function index(): View
    {
        return view('data-rights::desk.processors.index', [
            'processors' => Processor::query()->orderByDesc('active')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('data-rights::desk.processors.form', [
            'processor' => new Processor(['kind' => ProcessorKind::Processor, 'purposes' => [], 'data_shared' => []]),
            'purposes' => $this->dataRights->purposes()->all(),
        ]);
    }

    public function store(Request $http): RedirectResponse
    {
        Processor::query()->create($this->validated($http));

        return redirect()->route('data-rights-desk.processors.index')->with('data-rights.status', 'Processor added.');
    }

    public function edit(Processor $processor): View
    {
        return view('data-rights::desk.processors.form', [
            'processor' => $processor,
            'purposes' => $this->dataRights->purposes()->all(),
        ]);
    }

    public function update(Request $http, Processor $processor): RedirectResponse
    {
        $processor->update($this->validated($http));

        return redirect()->route('data-rights-desk.processors.index')->with('data-rights.status', 'Processor saved.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $http): array
    {
        $data = $http->validate([
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::enum(ProcessorKind::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'purposes' => ['nullable', 'array'],
            'purposes.*' => ['string', Rule::in($this->dataRights->purposes()->keys())],
            'data_shared' => ['nullable', 'string', 'max:5000'],
            'contract_reference' => ['nullable', 'string', 'max:255'],
            'contract_date' => ['nullable', 'date'],
            'security_clause' => ['nullable', 'boolean'],
            'country' => ['nullable', 'string', 'max:64'],
            'hidden_from_access_summary' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $data['name'],
            'kind' => $data['kind'],
            'description' => $data['description'] ?? null,
            'purposes' => array_values((array) ($data['purposes'] ?? [])),
            'data_shared' => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($data['data_shared'] ?? '')) ?: []))),
            'contract_reference' => $data['contract_reference'] ?? null,
            'contract_date' => $data['contract_date'] ?? null,
            'security_clause' => (bool) ($data['security_clause'] ?? false),
            'country' => $data['country'] ?? null,
            'hidden_from_access_summary' => (bool) ($data['hidden_from_access_summary'] ?? false),
            'active' => (bool) ($data['active'] ?? false),
        ];
    }
}
