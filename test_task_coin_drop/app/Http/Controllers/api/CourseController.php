<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CurrencyRateIndexRequest;
use App\Http\Requests\CurrencyRateShowRequest;
use App\Http\Service\CurrencyRateService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    /**
     * @param CurrencyRateIndexRequest $request
     * @param CurrencyRateService $service
     * @return JsonResponse
     */
    public function index(CurrencyRateIndexRequest $request, CurrencyRateService $service): JsonResponse
    {
        try {
            $send_currency = $request->input('send_currency_id');
            $receive_currency = $request->input('receive_currency_id');

            $courses = $service->getCurrencyRates($send_currency, $receive_currency);

            return response()->json($courses);
        } catch (ModelNotFoundException $exception) {
            return response()->json(['error' => 'Currency rate not found.'], 404);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * @param CurrencyRateShowRequest $request
     * @param $send_currency
     * @param $receive_currency
     * @param CurrencyRateService $service
     * @return JsonResponse
     */
    public function show($send_currency, $receive_currency, CurrencyRateService $service): JsonResponse
    {
        try {
            $course = $service->getCurrencyRate($send_currency, $receive_currency);
            return response()->json($course);
        } catch (ModelNotFoundException $exception) {
            return response()->json(['error' => 'Currency rate not found.'], 404);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }
}
