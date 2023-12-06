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
            $this->downloadFile($url, $filePath);
            $this->extractArchive($filePath, $extractPath);
            $this->processRates($extractPath);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return;
        } finally {
            $this->cleanUp($filePath, $extractPath);
            $this->info('The courses have been successfully updated.');
        }
    }

    protected function downloadFile($url, $filePath): void
    {
        $client = new Client();

        try {
            $client->request('GET', $url, ['sink' => Storage::path($filePath)]);
        } catch (GuzzleException $e) {
            throw new \Exception("Failed to download the archive. " . $e->getMessage());
        }
    }

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

    protected function processRates($extractPath): void
    {
        $ratesFilePath = Storage::path($extractPath . '\bm_rates.dat');
        $this->info("Check");
        DB::beginTransaction();
        try {
            $ratesFile = fopen($ratesFilePath, 'r');
            while (($line = fgetcsv($ratesFile, 0, ';')) !== false) {
                $this->info($line[0] . " " . $line[1]. " " . $line[3]. " " . $line[4]);
                $this->updateOrCreateRate($line);
            }
            fclose($ratesFile);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function updateOrCreateRate($line): void
    {
        $send_currency_id = $line[0];
        $receive_currency_id = $line[1];
        $send_rate = $line[3];
        $receive_rate = $line[4];

        CurrencyRate::updateOrCreate([
            'send_currency_id' => $send_currency_id,
            'receive_currency_id' => $receive_currency_id,
        ], [
            'send_rate' => $send_rate,
            'receive_rate' => $receive_rate,
        ]);
    }

    protected function cleanUp($filePath, $extractPath): void
    {
        Storage::delete($filePath);
        $files = Storage::allFiles($extractPath);
        Storage::delete($files);
        Storage::deleteDirectory($extractPath);
    }
}
