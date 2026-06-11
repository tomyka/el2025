<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gameID'        => ['required', 'integer'],
            'homeTeamScore' => ['nullable', 'integer', 'min:0', 'max:150'],
            'awayTeamScore' => ['nullable', 'integer', 'min:0', 'max:150'],
        ];
    }
}
