<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMovieStateRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */

    //https://laravel.com/framework/docs/12.x/validation -> 'sometimes'

    /*
        'sometimes' -> indicamos que ese valor del array de datos sea ignorado / no validado 
                        si no se modifica y evitar errores al no pone un valor
                        Luego el 'syncWithoutDetaching' sincroniza las modificaciones para el 'id' dado, 
                        pero manteniendo los datos no modificados
        */
    public function rules(): array
    {
        return [
            'watched' => 'sometimes|boolean',
            'liked' => 'sometimes|boolean',
            'watchlisted' => 'sometimes|boolean',
            'rating' => 'sometimes|nullable|integer|between:1,10',
            'review' => 'sometimes|nullable|string|max:500',
        ];
    }
}
