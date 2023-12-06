<?php

namespace Http\Service;

use App\Http\Service\CurrencyRateService;
use App\Models\CurrencyRate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyRateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testGetCurrencyRates()
    {
        // Создайте несколько записей валютных курсов в фейковой базе данных
        $currencyRate1 = CurrencyRate::factory()->create([
            'send_currency_id' => '1',
            'receive_currency_id' => '2',
        ]);
        $currencyRate2 = CurrencyRate::factory()->create([
            'send_currency_id' => '1',
            'receive_currency_id' => '3',
        ]);
        $currencyRate3 = CurrencyRate::factory()->create([
            'send_currency_id' => '2',
            'receive_currency_id' => '3',
        ]);

        $service = new CurrencyRateService();

        // Тестирование без фильтров
        $currencyRates = $service->getCurrencyRates(null, null);
        $this->assertCount(3, $currencyRates);

        // Тестирование с фильтром отправляемой валюты
        $currencyRates = $service->getCurrencyRates('1', null);
        $this->assertCount(2, $currencyRates);

        // Тестирование с фильтром получаемой валюты
        $currencyRates = $service->getCurrencyRates(null, '3');
        $this->assertCount(2, $currencyRates);

        // Тестирование с обоими фильтрами
        $currencyRates = $service->getCurrencyRates('1', '2');
        $this->assertCount(1, $currencyRates);
        $this->assertTrue($currencyRates->first()->is($currencyRate1));
    }

    public function testGetCurrencyRate()
    {
        // Создание тестовой записи валютного курса
        $currencyRate = CurrencyRate::factory()->create([
            'send_currency_id' => '1',
            'receive_currency_id' => '2',
        ]);

        $service = new CurrencyRateService();

        // Получение курса валюты
        $foundCurrencyRate = $service->getCurrencyRate('1', '2');
        $this->assertEquals($foundCurrencyRate->id, $currencyRate->id);

        // Проверка исключения, если курс не найден
        $this->expectException(ModelNotFoundException::class);
        $service->getCurrencyRate('ABC', 'XYZ');
    }
}
