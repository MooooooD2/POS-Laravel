<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('create_journal_entry');
    }

    public function rules(): array
    {
        return [
            'entry_date'             => 'required|date|before_or_equal:today',
            'description'            => 'nullable|string|max:500',
            'reference_type'         => 'nullable|string|max:50',
            'reference_id'           => 'nullable|integer|min:1',
            'lines'                  => 'required|array|min:2',
            'lines.*.account_id'     => 'required|integer|exists:accounts,id',
            'lines.*.debit'          => 'nullable|numeric|min:0',
            'lines.*.credit'         => 'nullable|numeric|min:0',
            'lines.*.description'    => 'nullable|string|max:255',
        ];
    }
}
