<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePredictionResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gameID'            => ['required', 'integer'],
            'prediction_gameID' => ['required', 'integer'],
            'homeTeamScore'     => ['nullable', 'integer', 'min:0', 'max:7'],
            'awayTeamScore'     => ['nullable', 'integer', 'min:0', 'max:7'],
            'gameWinnerID'      => ['nullable', 'integer'],
        ];
    }
}
