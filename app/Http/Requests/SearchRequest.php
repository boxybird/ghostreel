<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchRequest extends FormRequest
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
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Please enter a search term.',
            'q.min' => 'Search term must be at least 2 characters.',
            'q.max' => 'Search term must not exceed 100 characters.',
        ];
    }

    /**
     * Handle a failed validation attempt for HTMX requests.
     * Returns the empty state partial instead of redirecting.
     */
    protected function failedValidation(Validator $validator): void
    {
        // For HTMX requests, return empty state partial instead of redirect
        if ($this->header('HX-Request')) {
            throw new HttpResponseException(
                response()->view('heatmap.partials.search-empty')
            );
        }

        parent::failedValidation($validator);
    }
}
