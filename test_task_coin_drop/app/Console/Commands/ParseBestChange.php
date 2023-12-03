<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ParseBestChange extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:parse-best-change';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A command that parses courses from the BestChange website';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Шаг 1: Загружаем архив с BestChange
        $this->info('Downloading rates archive from BestChange...');
        $zipFile = storage_path('app/public/info.zip'); // Локальный путь для сохранения архива
        $zipResource = \fopen($zipFile, "w");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.bestchange.ru/info.zip');
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_FILE, $zipResource);
        $page = curl_exec($ch);

        if(!$page) {
            $this->error('Failed to download rates archive. Error: ' . curl_error($ch));
            curl_close($ch);
            fclose($zipResource);
            return;
        }

        curl_close($ch);
        fclose($zipResource);

        // Шаг 2: Разархивировать архив и прочитать содержимое файла bm_rates.dat
        $zip = new \ZipArchive();
        if($zip->open($zipFile) !== true) {
            $this->error('Could not open the zip archive.');
            return;
        }

        $zip->extractTo(storage_path('app/public'));
        $zip->close();
        $this->info('Archive has been downloaded and unpacked.');

        // Шаг 3: Прочесть и проанализировать файл bm_rates.dat для каждой пары валют
        $ratesFilePath = storage_path('app/public/bm_rates.dat');
        if (!file_exists($ratesFilePath)) {
            $this->error('bm_rates.dat file does not exist.');
            return;
        }

        $lines = file($ratesFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $bestRates = [];

        foreach ($lines as $line) {
            // Пример строки: "117;89;454;66.82258603;1;123638.43;0.2506;1"
            $parts = explode(';', $line);

            // Получаем идентификаторы валют и курсы
            list($sendCurrencyId, $receiveCurrencyId, , $exchangeRate, $receiveRate) = $parts;

            // Ключ для пары валют
            $currencyPairKey = $sendCurrencyId . '-' . $receiveCurrencyId;

            // Если для данной пары уже есть курс, проверяем, может ли текущий курс быть лучше
            if (!isset($bestRates[$currencyPairKey]) || $bestRates[$currencyPairKey]['exchangeRate'] < $exchangeRate) {
                $bestRates[$currencyPairKey] = [
                    'sendCurrencyId' => $sendCurrencyId,
                    'receiveCurrencyId' => $receiveCurrencyId,
                    'exchangeRate' => $exchangeRate,
                    'receiveRate' => $receiveRate
                ];
            }
        }

        // Шаг 4: Записываем результаты в базу данных
        $this->info('Updating database with best rates...');
        foreach ($bestRates as $bestRate) {
            // Используем модель для записи в базу данных
            \App\Models\CurrencyRate::updateOrCreate(
                [
                    'send_currency_id' => $bestRate['sendCurrencyId'],
                    'receive_currency_id' => $bestRate['receiveCurrencyId'],
                ],
                [
                    'send_rate' => $bestRate['sendRate'], // Предполагая что 'sendRate' это правильный ключ в $bestRate
                    'receive_rate' => $bestRate['receiveRate']
                ]
            );
        }


        $this->info('All best rates have been updated in the database.');
    }


}
