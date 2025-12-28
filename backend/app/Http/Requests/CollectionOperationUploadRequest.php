<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CollectionOperationUploadRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,txt|max:1002400',
            'operation_type' => 'required|in:add,remove',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a CSV file to upload',
            'file.mimes' => 'The file must be a CSV file',
            'file.max' => 'The file size must not exceed 100MB',
            'operation_type.required' => 'Please specify the operation type (add or remove)',
            'operation_type.in' => 'Operation type must be either "add" or "remove"',
        ];
    }
}
