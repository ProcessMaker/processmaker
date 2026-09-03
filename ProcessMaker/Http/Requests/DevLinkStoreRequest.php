<?php

namespace ProcessMaker\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use ProcessMaker\Rules\HttpOrigin;

class DevLinkStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'url' => trim((string) $this->input('url')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', Rule::unique('dev_links', 'name')],
            'url' => ['bail', 'required', 'url', new HttpOrigin()],
        ];
    }
}
