<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogClickRequest extends FormRequest
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
            'tmdb_movie_id' => ['required', 'integer', 'min:1'],
            'movie_title' => ['required', 'string', 'max:255'],
            'poster_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tmdb_movie_id.required' => 'Movie ID is required.',
            'tmdb_movie_id.integer' => 'Movie ID must be a valid integer.',
            'movie_title.required' => 'Movie title is required.',
        ];
    }
}
