<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMovieListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Además de saber que el user está autenticado (por middleware) necesitamos saber
     *  si ese user tiene acceso a esa lista. Con 'can' lo comprobamos
     * Se vincula internamente con el policy correspondiente -> MovieListPolicy
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('list'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:100',
            'description' => 'sometimes|nullable|string|max:1000',
        ];
    }
}
