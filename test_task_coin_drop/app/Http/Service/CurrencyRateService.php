<?php

namespace App\Http\Service;

use App\Models\CurrencyRate;

class CurrencyRateService
{
    public function getCurrencyRates(?string $send_currency, ?string $receive_currency)
    {

        $query = CurrencyRate::query();


        if ($send_currency !== null) {
            $query->where('send_currency_id', $send_currency);
        }
        if ($receive_currency !== null) {
            $query->where('receive_currency_id', $receive_currency);
        }

        return $query->get();
    }

    public function getCurrencyRate($send_currency, $receive_currency)
    {
        return CurrencyRate::where('send_currency_id', $send_currency)
            ->where('receive_currency_id', $receive_currency)
            ->firstOrFail();
    }
}
