<?php

namespace ProcessMaker\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use ProcessMaker\Rules\SortBy;

class CaseListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'userId' => 'sometimes|integer',
            'status' => 'sometimes|in:IN_PROGRESS,COMPLETED',
            'sortBy' => ['sometimes', 'string', new SortBy],
            'filterBy' => 'sometimes|json',
            'search' => 'sometimes|string',
            'pageSize' => 'sometimes|integer|min:1',
            'page' => 'sometimes|integer|min:1',
        ];
    }
}
