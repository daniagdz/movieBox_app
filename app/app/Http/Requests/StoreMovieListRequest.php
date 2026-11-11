<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

//https://laravel.com/framework/docs/12.x/validation
class StoreMovieListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Como se crea una lista nueva llega con saber que el user está autenticado
     */
    public function authorize(): bool
    {
        /*
        en routes ya habiamos creado un middleware que actua filtrando user autenticados,
            por eso simplemente devuelve true
        */
        return true; 
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
        ];
    }
}
