<?php

namespace ProcessMaker\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetAllCasesRequest extends FormRequest
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
            'status' => 'sometimes|in:in_progress,completed',
            'sortBy' => 'sometimes|string',
            'filterBy' => 'sometimes|array',
            'search' => 'sometimes|string',
            'pageSize' => 'sometimes|integer|min:1',
            'page' => 'sometimes|integer|min:1',
        ];
    }
}
