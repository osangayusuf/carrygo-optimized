<?php

namespace App\Http\Requests;

use App\Models\Bid;
use App\Rules\MaxEventBidPointsInFinalWindow;
use Illuminate\Foundation\Http\FormRequest;

class PlaceBidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Bid $bid */
        $bid = $this->route('bid');

        return [
            'points' => [
                'required',
                'integer',
                'min:1',
                new MaxEventBidPointsInFinalWindow($bid, $this->user()->msisdn),
            ],
        ];
    }
}
