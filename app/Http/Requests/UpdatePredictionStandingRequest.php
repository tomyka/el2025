<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePredictionStandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prediction_standingID' => ['required', 'integer'],
            'groupPosition'         => ['nullable', 'integer', 'min:1', 'max:4'],
            'last32'                => ['nullable', 'integer', 'min:0', 'max:1'],
            'last16'                => ['nullable', 'integer', 'min:0', 'max:1'],
            'quarterfinal'          => ['nullable', 'integer', 'min:0', 'max:1'],
            'semifinal'             => ['nullable', 'integer', 'min:0', 'max:1'],
            'final'                 => ['nullable', 'integer', 'min:1', 'max:4'],
        ];
    }
}
