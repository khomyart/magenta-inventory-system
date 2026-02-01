<template>
  <div class="reports-page q-pa-md">
    <div class="page-header q-mb-lg">
      <h4 class="q-ma-none">Звіти та аналітика</h4>
      <p class="text-grey-7 q-mt-sm q-mb-none">
        Перегляд фінансових показників та статистики замовлень
      </p>
    </div>

    <!-- Date Range Selector -->
    <q-card class="q-mb-lg">
      <q-card-section>
        <div class="row items-center q-gutter-md">
          <div class="col-auto" style="width: 250px">
            <DateTimeInputComponent
              v-model="startDate"
              dense
              label="Дата початку"
              :autoSetCurrentTime="false"
            />
          </div>
          <div class="col-auto">
            <q-icon name="arrow_forward" size="sm" color="grey-6" />
          </div>
          <div class="col-auto" style="width: 250px">
            <DateTimeInputComponent
              v-model="endDate"
              dense
              label="Дата закінчення"
              :autoSetCurrentTime="false"
            />
          </div>
          <div class="col-auto">
            <q-btn-dropdown
              flat
              color="primary"
              no-caps
              label="Швидкі періоди"
            >
              <q-list>
                <q-item clickable v-close-popup @click="setQuickPeriod('today')">
                  <q-item-section>
                    <q-item-label>Сьогодні</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item
                  clickable
                  v-close-popup
                  @click="setQuickPeriod('yesterday')"
                >
                  <q-item-section>
                    <q-item-label>Вчора</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item
                  clickable
                  v-close-popup
                  @click="setQuickPeriod('thisWeek')"
                >
                  <q-item-section>
                    <q-item-label>Цей тиждень</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item
                  clickable
                  v-close-popup
                  @click="setQuickPeriod('lastWeek')"
                >
                  <q-item-section>
                    <q-item-label>Минулий тиждень</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item
                  clickable
                  v-close-popup
                  @click="setQuickPeriod('thisMonth')"
                >
                  <q-item-section>
                    <q-item-label>Цей місяць</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item
                  clickable
                  v-close-popup
                  @click="setQuickPeriod('lastMonth')"
                >
                  <q-item-section>
                    <q-item-label>Минулий місяць</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item
                  clickable
                  v-close-popup
                  @click="setQuickPeriod('thisYear')"
                >
                  <q-item-section>
                    <q-item-label>Цей рік</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </div>

          <div class="col-auto">
            <q-btn
              color="primary"
              icon="analytics"
              @click="generateReport"
              :loading="reportStore.data.isReportLoading"
              :disable="!isDateRangeValid"
            >
              <q-tooltip class="bg-black text-body2">Сформувати звіт</q-tooltip>
            </q-btn>
          </div>
          <div class="col-auto">
            <q-btn
              flat
              color="grey-7"
              icon="refresh"
              @click="resetReport"
              :disable="!reportStore.hasReport"
            >
              <q-tooltip class="bg-black text-body2">Скинути</q-tooltip>
            </q-btn>
          </div>
          <q-space />
        </div>
      </q-card-section>
    </q-card>

    <!-- Report Content -->
    <div v-if="reportStore.hasReport">
      <!-- Summary Cards -->
      <div class="row q-col-gutter-md q-mb-lg">
        <!-- Revenue Card -->
        <div class="col-12 col-md-4">
          <q-card>
            <q-card-section>
              <div class="text-overline text-grey-7">Виручка</div>
              <div class="text-h4 text-positive q-mt-sm">
                {{ formatCurrency(reportStore.revenue?.total || 0) }}
              </div>
              <div class="q-mt-sm text-caption text-grey-8">
                <div v-if="reportStore.revenue?.breakdown">
                  <div>Картка: {{ formatCurrency(reportStore.revenue.breakdown.card || 0) }}</div>
                  <div>Термінал: {{ formatCurrency(reportStore.revenue.breakdown.terminal || 0) }}</div>
                  <div>Готівка: {{ formatCurrency(reportStore.revenue.breakdown.cash || 0) }}</div>
                </div>
              </div>
              <div class="text-caption text-grey-7 q-mt-xs">
                Замовлень: {{ reportStore.revenue?.orders_count || 0 }}
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Expenses Card -->
        <div class="col-12 col-md-4">
          <q-card>
            <q-card-section>
              <div class="text-overline text-grey-7">Витрати</div>
              <div class="text-h4 text-negative q-mt-sm">
                {{ formatCurrency(reportStore.expenses?.total || 0) }}
              </div>
              <div class="q-mt-sm text-caption text-grey-8">
                <div v-if="reportStore.expenses?.breakdown">
                  <div>Картка: {{ formatCurrency(reportStore.expenses.breakdown.card || 0) }}</div>
                  <div>Рахунок: {{ formatCurrency(reportStore.expenses.breakdown.terminal || 0) }}</div>
                  <div>Готівка: {{ formatCurrency(reportStore.expenses.breakdown.cash || 0) }}</div>
                </div>
              </div>
              <div class="text-caption text-grey-7 q-mt-xs">
                Записів: {{ reportStore.expenses?.spends_count || 0 }}
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Profit Card -->
        <div class="col-12 col-md-4">
          <q-card>
            <q-card-section>
              <div class="text-overline text-grey-7">Прибуток</div>
              <div
                class="text-h4 q-mt-sm"
                :class="
                  (reportStore.profit?.total || 0) >= 0
                    ? 'text-positive'
                    : 'text-negative'
                "
              >
                {{ formatCurrency(reportStore.profit?.total || 0) }}
              </div>
              <div class="q-mt-sm text-caption text-grey-8">
                <div v-if="reportStore.revenue?.breakdown && reportStore.expenses?.breakdown">
                  <div>
                    Картка:
                    <span :class="(reportStore.revenue.breakdown.card - reportStore.expenses.breakdown.card) >= 0 ? 'text-green' : 'text-red'">
                      {{ formatCurrency(reportStore.revenue.breakdown.card - reportStore.expenses.breakdown.card) }}
                    </span>
                  </div>
                  <div>
                    Рахунок/Термінал:
                    <span :class="(reportStore.revenue.breakdown.terminal - reportStore.expenses.breakdown.terminal) >= 0 ? 'text-green' : 'text-red'">
                      {{ formatCurrency(reportStore.revenue.breakdown.terminal - reportStore.expenses.breakdown.terminal) }}
                    </span>
                  </div>
                  <div>
                    Готівка:
                    <span :class="(reportStore.revenue.breakdown.cash - reportStore.expenses.breakdown.cash) >= 0 ? 'text-green' : 'text-red'">
                      {{ formatCurrency(reportStore.revenue.breakdown.cash - reportStore.expenses.breakdown.cash) }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="text-caption text-grey-7 q-mt-xs">
                Маржа:
                {{
                  reportStore.profit?.margin !== undefined
                    ? reportStore.profit.margin.toFixed(2) + "%"
                    : "0%"
                }}
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="row q-col-gutter-md q-mb-lg">
        <div class="col-12">
          <q-card>
            <q-card-section>
              <div class="text-h6">Динаміка показників</div>
              <div class="text-caption text-grey-7">
                Виручка, витрати та прибуток за обраний період
              </div>
            </q-card-section>
            <q-separator />
            <q-card-section>
              <RevenueExpenseChart
                v-if="reportStore.dailyData"
                :dailyData="reportStore.dailyData"
              />
              <div v-else class="text-center text-grey-7 q-pa-xl">
                Немає даних для відображення графіка
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Services Statistics -->
      <div
        v-if="reportStore.servicesStatistics"
        class="row q-col-gutter-md q-mb-lg"
      >
        <div class="col-12">
          <q-card>
            <q-card-section>
              <div class="row items-center justify-between">
                <div class="col-auto">
                  <div class="text-h6">Найбільш вживані послуги</div>
                  <div class="text-caption text-grey-7">
                    Топ-10 послуг у виконаних замовленнях за обраний період
                  </div>
                </div>
                <div class="col-auto">
                  <q-btn-toggle
                    no-caps
                    v-model="servicesSortBy"
                    @update:model-value="generateReport"
                    toggle-color="primary"
                    :options="[
                      {
                        label: 'По кількості замовлень',
                        value: 'orders_count',
                      },
                      {
                        label: 'По кількості послуг',
                        value: 'total_quantity',
                      },
                    ]"
                  />
                </div>
              </div>
            </q-card-section>
            <q-separator />
            <q-card-section>
              <div
                v-if="
                  reportStore.servicesStatistics.most_used &&
                  reportStore.servicesStatistics.most_used.length > 0
                "
              >
                <ServicesChart
                  :servicesData="reportStore.servicesStatistics.most_used"
                />
              </div>
              <div v-else class="text-center text-grey-7 q-pa-xl">
                Немає даних про використання послуг за обраний період
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Items Statistics -->
      <div v-if="reportStore.itemsStatistics" class="row q-col-gutter-md q-mb-lg">
        <div class="col-12">
          <q-card>
            <q-card-section>
              <div class="row items-center justify-between q-mb-md">
                <div class="col-auto">
                  <div class="text-h6">Найбільш вживані товари</div>
                  <div class="text-caption text-grey-7">
                    Топ-10 товарів у виконаних замовленнях за обраний період
                  </div>
                </div>
                <div class="col-auto">
                  <q-btn-toggle
                    no-caps
                    v-model="itemsSortBy"
                    @update:model-value="generateReport"
                    toggle-color="primary"
                    :options="[
                      {
                        label: 'По кількості замовлень',
                        value: 'orders_count',
                      },
                      {
                        label: 'По кількості товарів',
                        value: 'total_quantity',
                      },
                    ]"
                  />
                </div>
              </div>

              <!-- Filters -->
              <div class="row q-col-gutter-md">
                <div class="col-12 col-md-4">
                  <q-select
                    v-model="itemTypeId"
                    :options="typeStore.types"
                    option-value="id"
                    option-label="name"
                    emit-value
                    map-options
                    use-input
                    input-debounce="400"
                    @filter="typeFilter"
                    clearable
                    label="Вид (обов'язковий для фільтрації)"
                    dense
                    outlined
                    :loading="typeStore.data.isItemsLoading"
                    @update:model-value="generateReport"
                  />
                </div>
                <div class="col-12 col-md-4">
                  <q-select
                    v-model="itemColorId"
                    :options="colorStore.colors"
                    option-value="id"
                    option-label="description"
                    emit-value
                    map-options
                    use-input
                    input-debounce="400"
                    @filter="colorFilter"
                    clearable
                    label="Колір (опціонально)"
                    dense
                    outlined
                    :disable="!itemTypeId"
                    :loading="colorStore.data.isItemsLoading"
                    @update:model-value="generateReport"
                  >
                    <template v-slot:option="scope">
                      <q-item v-bind="scope.itemProps" class="flex items-center">
                        <div
                          class="color q-mr-sm"
                          :style="{ backgroundColor: scope.opt.value }"
                        ></div>
                        <span>{{ scope.opt.value }}</span>
                        <q-tooltip
                          class="bg-black text-body2"
                          anchor="center start"
                          self="center end"
                          :offset="[5, 0]"
                        >
                          {{ scope.opt.description }}
                        </q-tooltip>
                      </q-item>
                    </template>
                  </q-select>
                </div>
                <div class="col-12 col-md-4">
                  <q-select
                    v-model="itemSizeId"
                    :options="sizeStore.sizes"
                    option-value="id"
                    option-label="value"
                    emit-value
                    map-options
                    use-input
                    input-debounce="400"
                    @filter="sizeFilter"
                    clearable
                    label="Розмір (опціонально)"
                    dense
                    outlined
                    :disable="!itemTypeId"
                    :loading="sizeStore.data.isItemsLoading"
                    @update:model-value="generateReport"
                  />
                </div>
              </div>
            </q-card-section>
            <q-separator />
            <q-card-section>
              <div
                v-if="
                  reportStore.itemsStatistics.most_used &&
                  reportStore.itemsStatistics.most_used.length > 0
                "
              >
                <ItemsChart
                  :itemsData="reportStore.itemsStatistics.most_used"
                />
              </div>
              <div v-else class="text-center text-grey-7 q-pa-xl">
                Немає даних про використання товарів за обраними критеріями
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Order Statistics -->
      <div class="row q-col-gutter-md q-mb-lg">
        <div class="col-12 col-md-6">
          <q-card>
            <q-card-section>
              <div class="text-h6">Статистика замовлень</div>
              <div class="text-caption text-grey-7">
                Розподіл замовлень за статусами
              </div>
            </q-card-section>
            <q-separator />
            <q-card-section>
              <div
                v-if="reportStore.ordersStatistics"
                class="q-gutter-sm"
              >
                <div class="row items-center">
                  <div class="col-6 text-grey-8">Всього замовлень:</div>
                  <div class="col-6 text-right text-bold">
                    {{ reportStore.ordersStatistics.total }}
                  </div>
                </div>
                <q-separator />
                <div class="row items-center">
                  <div class="col-6 text-grey-8">Очікують:</div>
                  <div class="col-6 text-right">
                    {{ reportStore.ordersStatistics.by_status?.pending || 0 }}
                  </div>
                </div>
                <div class="row items-center">
                  <div class="col-6 text-grey-8">Підтверджені:</div>
                  <div class="col-6 text-right">
                    {{ reportStore.ordersStatistics.by_status?.confirmed || 0 }}
                  </div>
                </div>
                <div class="row items-center">
                  <div class="col-6 text-grey-8">В роботі:</div>
                  <div class="col-6 text-right">
                    {{ reportStore.ordersStatistics.by_status?.in_progress || 0 }}
                  </div>
                </div>
                <div class="row items-center">
                  <div class="col-6 text-grey-8">Завершені:</div>
                  <div class="col-6 text-right text-positive">
                    {{ reportStore.ordersStatistics.by_status?.completed || 0 }}
                  </div>
                </div>
                <div class="row items-center">
                  <div class="col-6 text-grey-8">Скасовані:</div>
                  <div class="col-6 text-right text-negative">
                    {{ reportStore.ordersStatistics.by_status?.cancelled || 0 }}
                  </div>
                </div>
                <q-separator />
                <div class="row items-center">
                  <div class="col-6 text-grey-8">Завершені та оплачені:</div>
                  <div class="col-6 text-right text-bold text-positive">
                    {{ reportStore.ordersStatistics.completed_and_paid || 0 }}
                  </div>
                </div>
                <div
                  v-if="reportStore.ordersStatistics.average_order_value"
                  class="row items-center"
                >
                  <div class="col-6 text-grey-8">Середній чек:</div>
                  <div class="col-6 text-right text-bold">
                    {{
                      formatCurrency(
                        reportStore.ordersStatistics.average_order_value
                      )
                    }}
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- User Involvement -->
        <div class="col-12 col-md-6">
          <q-card>
            <q-card-section>
              <div class="text-h6">Залученість співробітників</div>
              <div class="text-caption text-grey-7">
                Заробітки співробітників за замовленнями
              </div>
            </q-card-section>
            <q-separator />
            <q-card-section>
              <div v-if="groupedUserInvolvement.length > 0">
                <q-list separator>
                  <q-item
                    v-for="user in groupedUserInvolvement"
                    :key="user.user_id"
                  >
                    <q-item-section>
                      <q-item-label class="text-bold text-h6">{{
                        user.user_name
                      }}</q-item-label>
                      <q-item-label caption class="q-mt-xs">
                        Всього замовлень: {{ user.total_orders_count }} | Сумарна
                        виручка: {{ formatCurrency(user.total_earnings) }}
                      </q-item-label>

                      <!-- Levels breakdown -->
                      <q-list dense class="q-mt-sm q-ml-md">
                        <q-item
                          v-for="level in user.levels"
                          :key="`${user.user_id}_${level.involvement_level}`"
                          dense
                          class="q-px-none"
                        >
                          <q-item-section>
                            <q-item-label caption>
                              <span class="text-grey-8"
                                >Рівень {{ level.involvement_level }} ({{
                                  level.involvement_percentage
                                }}%):</span
                              >
                              {{ level.orders_count }}
                              {{ level.orders_count === 1 ? "замовлення" : "замовлень" }} •
                              {{ formatCurrency(level.earnings) }}
                              <span class="text-grey-6">
                                (на суму
                                {{ formatCurrency(level.total_orders_amount) }})
                              </span>
                            </q-item-label>
                          </q-item-section>
                        </q-item>
                      </q-list>
                    </q-item-section>
                    <q-item-section side top>
                      <q-item-label class="text-positive text-bold text-h6">
                        {{ formatCurrency(user.total_earnings) }}
                      </q-item-label>
                      <q-item-label caption class="text-right">
                        на суму {{ formatCurrency(user.total_orders_amount) }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>
              <div v-else class="text-center text-grey-7 q-pa-md">
                Немає даних про залученість співробітників
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center q-pa-xl">
      <q-icon name="assessment" size="120px" color="grey-4" />
      <div class="text-h6 text-grey-6 q-mt-md">
        Виберіть період та сформуйте звіт
      </div>
      <div class="text-body2 text-grey-5 q-mt-sm">
        Оберіть дати початку та закінчення періоду, або скористайтесь швидким
        вибором
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useReportStore } from "src/stores/reportStore";
import { useTypeStore } from "src/stores/typeStore";
import { useColorStore } from "src/stores/colorStore";
import { useSizeStore } from "src/stores/sizeStore";
import { date } from "quasar";
import RevenueExpenseChart from "src/components/reports/RevenueExpenseChart.vue";
import ServicesChart from "src/components/reports/ServicesChart.vue";
import ItemsChart from "src/components/reports/ItemsChart.vue";
import DateTimeInputComponent from "src/components/input/DateTimeInputComponent.vue";
import { getServerTime } from "../../helpers/GeneralPurposeFunctions.js";

const reportStore = useReportStore();
const typeStore = useTypeStore();
const colorStore = useColorStore();
const sizeStore = useSizeStore();

const startDate = ref("");
const endDate = ref("");
const servicesSortBy = ref("orders_count");
const itemsSortBy = ref("orders_count");
const itemTypeId = ref(null);
const itemColorId = ref(null);
const itemSizeId = ref(null);

const isDateRangeValid = computed(() => {
  return startDate.value && endDate.value && startDate.value <= endDate.value;
});

const groupedUserInvolvement = computed(() => {
  if (!reportStore.userInvolvement || reportStore.userInvolvement.length === 0) {
    return [];
  }

  // Group by user_id
  const grouped = {};

  reportStore.userInvolvement.forEach((item) => {
    if (!grouped[item.user_id]) {
      grouped[item.user_id] = {
        user_id: item.user_id,
        user_name: item.user_name,
        levels: [],
        total_earnings: 0,
        total_orders_count: 0,
        total_orders_amount: 0,
      };
    }

    grouped[item.user_id].levels.push(item);
    grouped[item.user_id].total_earnings += item.earnings;
    grouped[item.user_id].total_orders_count += item.orders_count;
    grouped[item.user_id].total_orders_amount += item.total_orders_amount;
  });

  // Convert to array and sort levels within each user
  const result = Object.values(grouped).map((user) => {
    // Sort levels from 1 to 3
    user.levels.sort((a, b) => a.involvement_level - b.involvement_level);
    // Round totals
    user.total_earnings = Math.round(user.total_earnings * 100) / 100;
    user.total_orders_amount = Math.round(user.total_orders_amount * 100) / 100;
    return user;
  });

  // Sort users by total earnings descending
  result.sort((a, b) => b.total_earnings - a.total_earnings);

  return result;
});

const formatCurrency = (value) => {
  return new Intl.NumberFormat("uk-UA", {
    style: "currency",
    currency: "UAH",
    minimumFractionDigits: 2,
  }).format(value);
};

const generateReport = async () => {
  if (isDateRangeValid.value) {
    // Convert client time (DD/MM/YYYY HH:mm) to server format (YYYY-MM-DD HH:mm)
    const apiStartDate = getServerTime(startDate.value, "ua", true, false);
    const apiEndDate = getServerTime(endDate.value, "ua", true, false);
    console.log(apiEndDate, apiStartDate);
    await reportStore.fetchReport(
      apiStartDate,
      apiEndDate,
      servicesSortBy.value,
      itemsSortBy.value,
      itemTypeId.value,
      itemColorId.value,
      itemSizeId.value
    );
  }
};

const resetReport = () => {
  reportStore.reset();
  startDate.value = "";
  endDate.value = "";
};

const typeFilter = (val, update, abort) => {
  update(() => {
    typeStore.data.isItemsLoading = true;
    typeStore.simpleItems = [];
    typeStore.simpleReceive(val);
  });
};

const colorFilter = (val, update, abort) => {
  update(() => {
    colorStore.data.isItemsLoading = true;
    colorStore.simpleItems = [];
    colorStore.simpleReceive(val);
  });
};

const sizeFilter = (val, update, abort) => {
  update(() => {
    sizeStore.data.isItemsLoading = true;
    sizeStore.simpleItems = [];
    sizeStore.simpleReceive(val);
  });
};

const setQuickPeriod = (period) => {
  const today = new Date();
  let start, end;

  // Helper function to get Monday of the week (week starts on Monday)
  const getMondayOfWeek = (d) => {
    const day = d.getDay(); // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
    const diff = day === 0 ? -6 : 1 - day; // If Sunday, go back 6 days; otherwise go back to Monday
    return date.addToDate(d, { days: diff });
  };

  // Helper function to get Sunday of the week
  const getSundayOfWeek = (d) => {
    const monday = getMondayOfWeek(d);
    return date.addToDate(monday, { days: 6 });
  };

  switch (period) {
    case "today":
      start = date.formatDate(today, "DD/MM/YYYY") + " 00:00";
      end = date.formatDate(today, "DD/MM/YYYY") + " 23:59";
      break;
    case "yesterday":
      const yesterday = date.subtractFromDate(today, { days: 1 });
      start = date.formatDate(yesterday, "DD/MM/YYYY") + " 00:00";
      end = date.formatDate(yesterday, "DD/MM/YYYY") + " 23:59";
      break;
    case "thisWeek":
      const thisWeekMonday = getMondayOfWeek(today);
      const thisWeekSunday = getSundayOfWeek(today);
      start = date.formatDate(thisWeekMonday, "DD/MM/YYYY") + " 00:00";
      end = date.formatDate(thisWeekSunday, "DD/MM/YYYY") + " 23:59";
      break;
    case "lastWeek":
      // Get last week by going back 7 days from today, then finding Monday and Sunday
      const lastWeekDate = date.subtractFromDate(today, { days: 7 });
      const lastWeekMonday = getMondayOfWeek(lastWeekDate);
      const lastWeekSunday = getSundayOfWeek(lastWeekDate);
      start = date.formatDate(lastWeekMonday, "DD/MM/YYYY") + " 00:00";
      end = date.formatDate(lastWeekSunday, "DD/MM/YYYY") + " 23:59";
      break;
    case "thisMonth":
      const monthStart = date.startOfDate(today, "month");
      const monthEnd = date.endOfDate(today, "month");
      start = date.formatDate(monthStart, "DD/MM/YYYY") + " 00:00";
      end = date.formatDate(monthEnd, "DD/MM/YYYY") + " 23:59";
      break;
    case "lastMonth":
      // Get last month by subtracting 1 month from today first
      const lastMonthDate = date.subtractFromDate(today, { months: 1 });
      const lastMonthStart = date.startOfDate(lastMonthDate, "month");
      const lastMonthEnd = date.endOfDate(lastMonthDate, "month");
      start = date.formatDate(lastMonthStart, "DD/MM/YYYY") + " 00:00";
      end = date.formatDate(lastMonthEnd, "DD/MM/YYYY") + " 23:59";
      break;
    case "thisYear":
      const yearStart = date.startOfDate(today, "year");
      const yearEnd = date.endOfDate(today, "year");
      start = date.formatDate(yearStart, "DD/MM/YYYY") + " 00:00";
      end = date.formatDate(yearEnd, "DD/MM/YYYY") + " 23:59";
      break;
  }

  startDate.value = start;
  endDate.value = end;
};

onMounted(async () => {
  // Set a default period to this month
  resetReport()
  setQuickPeriod("thisMonth");
});
</script>

<style scoped>
.reports-page {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header h4 {
  font-size: 28px;
  font-weight: 600;
  color: #1d1d1d;
}

.color {
  width: 30px;
  height: 30px;
  border-radius: 5px;
  border: 1px solid rgba(0, 0, 0, 0.18);
}
</style>
