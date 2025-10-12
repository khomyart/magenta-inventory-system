<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class NbuCurrencyExchangeCoursesService
{
    /**
     * Returns nbu currency exchange currency rate for today
     *
     * @param string $date - yyyymm
     * @param string $currencyCode - USD|EUR|etc...
     * @param
     *
     * @return float currency exchange index
     */
    public function getCourses(string $date, string $currencyCode): float
    {
        $cachedCurrencies = Cache::get("nbuCurrencyExchangeCourses");
        if ($cachedCurrencies != null) {
            return (float)$cachedCurrencies[$currencyCode] ?? 1;
        }
        $response = file_get_contents("https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?date={$date}&json");
        if ($response) {
            $currencies = json_decode($response, true);
            $currenciesAssoc = [];
            foreach ($currencies as $currency) {
                if ($currency['cc'] && $currency['rate']) {
                    $currenciesAssoc[$currency['cc']] = $currency['rate'];
                }
            }

            Cache::put("nbuCurrencyExchangeCourses", $currenciesAssoc, now()->setTime(23,59,59));
            return (float)$currenciesAssoc[$currencyCode] ?? 1;
        }

        return 1;
    }
}
