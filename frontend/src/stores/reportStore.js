import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { useAppConfigStore } from "./appConfigStore";

const appConfigStore = useAppConfigStore();
const sectionName = "reports";

export const useReportStore = defineStore("report", {
  state: () => ({
    report: null,
    summary: null,
    dateRange: {
      startDate: null,
      endDate: null,
    },
    data: {
      isReportLoading: false,
      isSummaryLoading: false,
    },
  }),
  actions: {
    /**
     * Fetch full report with daily breakdown
     */
    async fetchReport(
      startDate,
      endDate,
      servicesSortBy = "orders_count",
      itemsSortBy = "orders_count",
      itemTypeId = null,
      itemColorId = null,
      itemSizeId = null
    ) {
      this.data.isReportLoading = true;
      this.dateRange.startDate = startDate;
      this.dateRange.endDate = endDate;

      try {
        const params = {
          start_date: startDate,
          end_date: endDate,
          services_sort_by: servicesSortBy,
          items_sort_by: itemsSortBy,
        };

        // Add item filters only if they are set
        if (itemTypeId !== null) {
          params.item_type_id = itemTypeId;
        }
        if (itemColorId !== null) {
          params.item_color_id = itemColorId;
        }
        if (itemSizeId !== null) {
          params.item_size_id = itemSizeId;
        }

        const res = await api.get(`/${sectionName}`, { params });
        this.report = res.data.data;
      } catch (err) {
        appConfigStore.catchRequestError(err);
      } finally {
        this.data.isReportLoading = false;
      }
    },

    /**
     * Fetch summary report without daily breakdown
     */
    async fetchSummary(startDate, endDate) {
      this.data.isSummaryLoading = true;
      this.dateRange.startDate = startDate;
      this.dateRange.endDate = endDate;

      try {
        const res = await api.get(`/${sectionName}/summary`, {
          params: {
            start_date: startDate,
            end_date: endDate,
          },
        });
        this.summary = res.data.data;
      } catch (err) {
        appConfigStore.catchRequestError(err);
      } finally {
        this.data.isSummaryLoading = false;
      }
    },

    /**
     * Reset report data
     */
    reset() {
      this.report = null;
      this.summary = null;
      this.dateRange.startDate = null;
      this.dateRange.endDate = null;
    },
  },
  getters: {
    /**
     * Get revenue data
     */
    revenue: (state) => {
      if (!state.report?.summary) return null;
      return {
        total: state.report.summary.total_revenue,
        orders_count: state.report.summary.completed_orders_count,
      };
    },

    /**
     * Get expenses data
     */
    expenses: (state) => {
      if (!state.report?.summary) return null;
      return {
        total: state.report.summary.total_expenses,
        spends_count: state.report.summary.spends_count,
      };
    },

    /**
     * Get profit data
     */
    profit: (state) => {
      if (!state.report?.summary) return null;
      return {
        total: state.report.summary.total_profit,
        margin: state.report.summary.profit_margin_percentage,
      };
    },

    /**
     * Get orders statistics
     */
    ordersStatistics: (state) => {
      if (!state.report) return null;
      return {
        total: state.report.summary?.total_orders || 0,
        by_status: state.report.orders_by_status || {},
        completed_and_paid: state.report.summary?.completed_orders_count || 0,
        average_order_value: state.report.summary?.average_order_value || 0,
      };
    },

    /**
     * Get user involvement data
     */
    userInvolvement: (state) => state.report?.user_involvement || null,

    /**
     * Get services statistics
     */
    servicesStatistics: (state) => {
      if (!state.report?.services_statistics) return null;
      return {
        most_used: state.report.services_statistics.most_used || [],
        total_services_used: state.report.services_statistics.total_services_used || 0,
      };
    },

    /**
     * Get items statistics
     */
    itemsStatistics: (state) => {
      if (!state.report?.items_statistics) return null;
      return {
        most_used: state.report.items_statistics.most_used || [],
        total_items_used: state.report.items_statistics.total_items_used || 0,
      };
    },

    /**
     * Get daily data for charts
     * Transforms backend format to chart format
     */
    dailyData: (state) => {
      if (!state.report?.daily_data || !Array.isArray(state.report.daily_data)) {
        return null;
      }

      const dates = [];
      const revenue = [];
      const expenses = [];
      const profit = [];

      state.report.daily_data.forEach((day) => {
        dates.push(day.date);
        revenue.push(day.revenue || 0);
        expenses.push(day.expenses || 0);
        profit.push(day.profit || 0);
      });

      return {
        dates,
        revenue,
        expenses,
        profit,
      };
    },

    /**
     * Check if report is loaded
     */
    hasReport: (state) => state.report !== null,
  },
});
