<?php

namespace ProcessMaker\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use ProcessMaker\Models\Process;

class ProcessUpdateRequest extends FormRequest
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
        $process = $this->route('process');
        $rules = Process::rules($process);

        if (!$this->has('name')) {
            unset($rules['name']);
        }

        if ($this->has('default_for_anon_webentry')) {
            $rules = ['language_code' => 'required_if:default_for_anon_webentry,true'];
        }

        if ($this->has('user_id')) {
            $rules['user_id'] = ['required', 'integer', 'exists:users,id'];
        }

        return $rules;
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => __('Process owner is required.'),
            'user_id.exists' => __('Selected process owner is invalid.'),
        ];
    }
}
