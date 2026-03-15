<?php

namespace App\Services;

use App\Services\Report\AccountStateCalculator;
use App\Services\Report\ExpenseCalculator;
use App\Services\Report\ItemStatisticsCalculator;
use App\Services\Report\OrderStatisticsCalculator;
use App\Services\Report\ProfitCalculator;
use App\Services\Report\RevenueCalculator;
use App\Services\Report\ServiceStatisticsCalculator;
use App\Services\Report\TransactionCalculator;
use App\Services\Report\UserInvolvementCalculator;
use Carbon\Carbon;

class ReportService
{
    private RevenueCalculator $revenueCalculator;

    private ExpenseCalculator $expenseCalculator;

    private ProfitCalculator $profitCalculator;

    private OrderStatisticsCalculator $orderStatisticsCalculator;

    private UserInvolvementCalculator $userInvolvementCalculator;

    private ServiceStatisticsCalculator $serviceStatisticsCalculator;

    private ItemStatisticsCalculator $itemStatisticsCalculator;

    private TransactionCalculator $transactionCalculator;

    private AccountStateCalculator $accountStateCalculator;

    public function __construct()
    {
        $this->revenueCalculator = new RevenueCalculator();
        $this->expenseCalculator = new ExpenseCalculator();
        $this->profitCalculator = new ProfitCalculator();
        $this->orderStatisticsCalculator = new OrderStatisticsCalculator();
        $this->userInvolvementCalculator = new UserInvolvementCalculator();
        $this->serviceStatisticsCalculator = new ServiceStatisticsCalculator();
        $this->itemStatisticsCalculator = new ItemStatisticsCalculator();
        $this->transactionCalculator = new TransactionCalculator();
        $this->accountStateCalculator = new AccountStateCalculator();
    }

    /**
     * Generate complete report for the specified period
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @param string $servicesSortBy Sort services by 'orders_count' or 'total_quantity' (default: 'orders_count')
     * @param string $itemsSortBy Sort items by 'orders_count' or 'total_quantity' (default: 'orders_count')
     * @param int|null $itemTypeId Filter items by type_id
     * @param int|null $itemColorId Filter items by color_id
     * @param int|null $itemSizeId Filter items by size_id
     * @return array Complete report data
     */
    public function generateReport(
        Carbon $startDate,
        Carbon $endDate,
        string $servicesSortBy = 'orders_count',
        string $itemsSortBy = 'orders_count',
        ?int $itemTypeId = null,
        ?int $itemColorId = null,
        ?int $itemSizeId = null
    ): array {
        // Calculate revenue
        $revenueData = $this->revenueCalculator->calculateRevenue($startDate, $endDate);

        // Calculate expenses
        $expensesData = $this->expenseCalculator->calculateExpenses($startDate, $endDate);

        // Calculate profit
        $profitData = $this->profitCalculator->calculateProfit($revenueData, $expensesData);

        // Calculate transactions
        $transactionData = $this->transactionCalculator->calculateTransactions($startDate, $endDate);

        // Calculate current account state (balance)
        $accountState = $this->accountStateCalculator->calculateCurrentState();

        // Get daily data with all dates filled
        $dailyData = $this->prepareDailyData($startDate, $endDate, $revenueData['by_day'], $expensesData['by_day'], $profitData['by_day']);

        // Calculate statistics
        $orderStatistics = $this->orderStatisticsCalculator->getOrdersDistribution($startDate, $endDate);
        $averageOrderValue = $this->orderStatisticsCalculator->calculateAverageOrderValue($startDate, $endDate);

        // Calculate profit margin
        $profitMargin = $this->profitCalculator->calculateProfitMargin($profitData['total'], $revenueData['total']);

        // Calculate user involvement
        $userInvolvement = $this->userInvolvementCalculator->calculateUserInvolvement($startDate, $endDate);

        // Get most used services statistics
        $mostUsedServices = $this->serviceStatisticsCalculator->getMostUsedServices($startDate, $endDate, 10, $servicesSortBy);
        $totalServicesUsed = $this->serviceStatisticsCalculator->getTotalServicesUsed($startDate, $endDate);

        // Get most used items statistics
        $mostUsedItems = $this->itemStatisticsCalculator->getMostUsedItems(
            $startDate,
            $endDate,
            10,
            $itemsSortBy,
            $itemTypeId,
            $itemColorId,
            $itemSizeId
        );
        $totalItemsUsed = $this->itemStatisticsCalculator->getTotalItemsUsed($startDate, $endDate, $itemTypeId, $itemColorId, $itemSizeId);

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d H:i'),
                'end_date' => $endDate->format('Y-m-d H:i'),
            ],
            'summary' => [
                'total_revenue' => round($revenueData['total'], 2),
                'revenue_breakdown' => $revenueData['breakdown'] ?? [],
                'total_expenses' => round($expensesData['total'], 2),
                'expenses_breakdown' => $expensesData['breakdown'] ?? [],
                'total_profit' => round($profitData['total'], 2),
                'profit_margin_percentage' => $profitMargin,
                'total_orders' => $orderStatistics['total'],
                'completed_orders_count' => $orderStatistics['completed_and_paid'],
                'average_order_value' => $averageOrderValue,
                'spends_count' => $expensesData['spends_count'],
                'transactions' => $transactionData,
            ],
            'account_state' => $accountState,
            'daily_data' => $dailyData,
            'orders_by_status' => $orderStatistics['by_status'],
            'user_involvement' => $userInvolvement,
            'services_statistics' => [
                'most_used' => $mostUsedServices,
                'total_services_used' => $totalServicesUsed,
            ],
            'items_statistics' => [
                'most_used' => $mostUsedItems,
                'total_items_used' => $totalItemsUsed,
            ],
        ];
    }

    /**
     * Get report summary (without daily breakdown)
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array Summary report data
     */
    public function getReportSummary(Carbon $startDate, Carbon $endDate): array
    {
        // Calculate totals
        $revenueData = $this->revenueCalculator->calculateRevenue($startDate, $endDate);
        $expensesData = $this->expenseCalculator->calculateExpenses($startDate, $endDate);
        $totalProfit = $revenueData['total'] - $expensesData['total'];

        // Get order statistics
        $orderStatistics = $this->orderStatisticsCalculator->getOrdersDistribution($startDate, $endDate);
        $averageOrderValue = $this->orderStatisticsCalculator->calculateAverageOrderValue($startDate, $endDate);

        // Calculate profit margin
        $profitMargin = $this->profitCalculator->calculateProfitMargin($totalProfit, $revenueData['total']);

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d H:i'),
                'end_date' => $endDate->format('Y-m-d H:i'),
            ],
            'summary' => [
                'total_revenue' => round($revenueData['total'], 2),
                'revenue_breakdown' => $revenueData['breakdown'] ?? [],
                'total_expenses' => round($expensesData['total'], 2),
                'expenses_breakdown' => $expensesData['breakdown'] ?? [],
                'total_profit' => round($totalProfit, 2),
                'profit_margin_percentage' => $profitMargin,
                'total_orders' => $orderStatistics['total'],
                'completed_orders_count' => $orderStatistics['completed_and_paid'],
                'average_order_value' => $averageOrderValue,
                'spends_count' => $expensesData['spends_count'],
            ],
            'orders_by_status' => $orderStatistics['by_status'],
        ];
    }

    /**
     * Prepare daily data array combining revenue, expenses, and profit
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @param array $revenueByDay Revenue by day data
     * @param array $expensesByDay Expenses by day data
     * @param array $profitByDay Profit by day data
     *
     * @return array Array of daily data
     */
    private function prepareDailyData(
        Carbon $startDate, Carbon $endDate, array $revenueByDay, array $expensesByDay, array $profitByDay
    ): array
    {
        $dailyData = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateKey = $currentDate->format('Y-m-d');

            $dailyData[] = [
                'date' => $dateKey,
                'revenue' => round($revenueByDay[$dateKey] ?? 0, 2),
                'expenses' => round($expensesByDay[$dateKey] ?? 0, 2),
                'profit' => round($profitByDay[$dateKey] ?? 0, 2),
            ];

            $currentDate->addDay();
        }

        return $dailyData;
    }
}
