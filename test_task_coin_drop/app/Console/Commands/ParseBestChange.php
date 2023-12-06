<?php

namespace App\Console\Commands;

use App\Models\CurrencyRate;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use GuzzleHttp\Client;

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
        $this->info("Parser is up and running...");

        $url = 'http://api.bestchange.ru/info.zip';
        $filePath = 'bestchange.zip';
        $extractPath = 'extracted';

        try {
            $this->downloadFile($url, $filePath); // Загрузка архива с данными об обменном курсе
            $this->extractArchive($filePath, $extractPath); // Извлечение содержимого архива
            $this->processRates($extractPath); // Обработка извлеченных данных и обновление курса в БД
        } catch (\Exception $e) {
            $this->error($e->getMessage()); // Вывод сообщения об ошибке, если что-то пошло не так
            return;
        } finally {
            $this->cleanUp($filePath, $extractPath); // Очистка временных файлов
            $this->info('The courses have been successfully updated.'); // Сообщение об успешном завершении
        }
    }

    /**
     * Загружает файл по указанному URL и сохраняет его на диске.
     */
    protected function downloadFile($url, $filePath): void
    {
        $client = new Client();

        try {
            $client->request('GET', $url, ['sink' => Storage::path($filePath)]);
        } catch (GuzzleException $e) {
            throw new \Exception("Failed to download the archive. " . $e->getMessage());
        }
    }

    /**
     * Извлекает файлы из архива в указанную директорию.
     */
    protected function extractArchive($filePath, $extractPath): void
    {
        $zip = new ZipArchive();

        if ($zip->open(Storage::path($filePath)) === true) {
            $zip->extractTo(Storage::path($extractPath));
            $zip->close();
        } else {
            throw new \Exception("Failed to unpack the archive.");
        }
    }

    /**
     * Обрабатывает данные о курсах валют и обновляет информацию в базе данных.
     */
    protected function processRates($extractPath): void
    {
        $ratesFilePath = Storage::path($extractPath . '/bm_rates.dat'); // Использование правильного разделителя для пути

        DB::beginTransaction();
        try {
            $batchSize = 500;
            $rateDataBatch = [];
            $ratesFile = fopen($ratesFilePath, 'r');
            $fileSize = filesize($ratesFilePath); // Размер файла для отслеживания прогресса
            $processedSize = 0; // Количество обработанных байт
            while (($line = fgetcsv($ratesFile, 0, ';')) !== false) {
                $rateDataBatch[] = [
                    'send_currency_id' => $line[0],
                    'receive_currency_id' => $line[1],
                    'send_rate' => $line[3],
                    'receive_rate' => $line[4],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $processedSize += strlen(implode(';', $line)) + 1; // +1 для учета перевода строки
                $this->showProgress($processedSize, $fileSize);
                if (count($rateDataBatch) === $batchSize) {
                    $this->upsertBatch($rateDataBatch);
                    $rateDataBatch = [];
                }
            }

            if (count($rateDataBatch) > 0) {
                $this->upsertBatch($rateDataBatch);
            }

            fclose($ratesFile);
            DB::commit(); // Подтвердить транзакцию
        } catch (\Exception $e) {
            DB::rollBack(); // Отменить транзакцию в случае ошибки
            throw $e;
        }
    }

    /**
     * Создает или обновляет курс валюты в базе данных.
     */
    protected function upsertBatch(array $rateDataBatch): void
    {
        foreach ($rateDataBatch as $data) {

            $existingRate = CurrencyRate::where('send_currency_id', $data['send_currency_id'])
                ->where('receive_currency_id', $data['receive_currency_id'])
                ->first();

            if ($existingRate) {
                // Если запись существует, обновите ее
                $existingRate->update([
                    'send_rate' => $data['send_rate'],
                    'receive_rate' => $data['receive_rate'],
                    'updated_at' => $data['updated_at'],
                ]);
            } else {
                // Если нет, вставьте новую запись
                CurrencyRate::create($data);
            }
        }
    }


    /**
     * Удаляет временные файлы и директории, созданные в процессе работы скрипта.
     */
    protected function cleanUp($filePath, $extractPath): void
    {
        Storage::delete($filePath);
        $files = Storage::allFiles($extractPath);
        Storage::delete($files);
        Storage::deleteDirectory($extractPath);
    }
    protected function showProgress($processedSize, $fileSize): void
    {
        $progressPercentage = ($processedSize / $fileSize) * 100;
        echo sprintf("\rProgress: %.2f%%", $progressPercentage);
    }
}
