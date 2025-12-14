<?php

namespace App\Http\Controllers;

use App\Helpers\AuthAPI;
use App\Helpers\ErrorHandler;
use App\Http\Resources\ReportResource;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public string $section = 'reports';

    public AuthAPI|false $authAPI;

    public function __construct(Request $request, private ReportService $reportService)
    {
        $this->authAPI = AuthAPI::isAuthenticated($request->bearerToken(), $request->ip());
    }

    /**
     * Get full report with daily breakdown
     *
     * @param Request $request
     * @return Response
     */
    public function read(Request $request): Response
    {
        $rules = [
            'start_date' => ['required', 'date_format:Y-m-d H:i'],
            'end_date' => ['required', 'date_format:Y-m-d H:i', 'after_or_equal:start_date'],
            'services_sort_by' => ['nullable', 'in:orders_count,total_quantity'],
            'items_sort_by' => ['nullable', 'in:orders_count,total_quantity'],
            'item_type_id' => ['nullable', 'integer', 'exists:types,id'],
            'item_color_id' => ['nullable', 'integer', 'exists:colors,id'],
            'item_size_id' => ['nullable', 'integer', 'exists:sizes,id'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ErrorHandler::responseWith(json_encode($validator->errors()));
        }

        try {
            $data = $validator->validated();
        } catch (ValidationException $e) {
            return ErrorHandler::responseWith($e->getMessage());
        }

        try {
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);
            $servicesSortBy = $data['services_sort_by'] ?? 'orders_count';
            $itemsSortBy = $data['items_sort_by'] ?? 'orders_count';
            $itemTypeId = $data['item_type_id'] ?? null;
            $itemColorId = $data['item_color_id'] ?? null;
            $itemSizeId = $data['item_size_id'] ?? null;

            $report = $this->reportService->generateReport(
                $startDate,
                $endDate,
                $servicesSortBy,
                $itemsSortBy,
                $itemTypeId,
                $itemColorId,
                $itemSizeId
            );

            return response()->json(['data' => $report]);
        } catch (\Exception $e) {
            return ErrorHandler::responseWith('Помилка при генерації звіту: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get summary report (without daily breakdown)
     *
     * @param Request $request
     * @return Response
     */
    public function summary(Request $request): Response
    {
        $rules = [
            'start_date' => ['required', 'date_format:Y-m-d H:i'],
            'end_date' => ['required', 'date_format:Y-m-d H:i', 'after_or_equal:start_date'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ErrorHandler::responseWith(json_encode($validator->errors()));
        }

        try {
            $data = $validator->validated();
        } catch (ValidationException $e) {
            return ErrorHandler::responseWith($e->getMessage());
        }

        try {
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            $summary = $this->reportService->getReportSummary($startDate, $endDate);

            return response()->json(['data' => $summary]);
        } catch (\Exception $e) {
            return ErrorHandler::responseWith('Помилка при генерації звіту: '.$e->getMessage(), 500);
        }
    }
}
