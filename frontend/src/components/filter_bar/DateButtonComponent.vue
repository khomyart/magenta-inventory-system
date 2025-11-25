<script setup>
/**
 * This component requires three params:
 *
 * Actual field in db label as a string (like "happened_at") passed via props.name,
 * and filtering field names, like: "happened_at_from", "happened_at_to" passed as an array via props.targetFields
 *
 * Also, they need to be set up in appConfigStore (all these three fields) to be able to manipulate with proper
 * filtering and ordering correctly.
 */


import { computed } from "vue";
import DateTimeInputComponent from "components/input/DateTimeInputComponent.vue";

const appStore = props.appStore;
const sectionStore = props.sectionStore;

const props = defineProps([
  "appStore",
  "sectionName",
  "sectionStore",
  "name",
  "label",
  "searchBarLabel",
  "orderButtonLabels",
  "width",
  "justOrder",
  //universal|text|number
  "mode",
  // In case if we need to pass two filtering fields for a date button component
  "targetFields"
]);

const emits = defineEmits([
  "clearFilter",
  "changeFilterMode",
  "setFilterOrder",
]);

const filterOrder = computed(() => {
  return {
    field: appStore.filters.data[props.sectionName].selectedParams.order.field,
    value: appStore.filters.data[props.sectionName].selectedParams.order.value,
  };
});

const filterBadgeLabel = computed(() => {
  let filterFrom = appStore.filters.data[props.sectionName].selectedParams[props.targetFields[0]];
  let filterTo = appStore.filters.data[props.sectionName].selectedParams[props.targetFields[1]];
  let isNullFilter = appStore.filters.data[props.sectionName].selectedParams[props.name + "_is_null"];

  // Return empty badges if filters don't exist
  if (!filterFrom || !filterTo) {
    return {
      value: "",
      mode: "",
      order: "",
    };
  }

  // Check if null filter is active
  const hasNullFilter = isNullFilter && isNullFilter.value === true;
  // Check if date range filters are active
  const hasDateFilter = filterFrom.value !== "" || filterTo.value !== "";

  return {
    value: hasNullFilter || hasDateFilter ? "V" : "",
    mode: hasNullFilter ? "NULL" : "DATE",
    order:
      appStore.filters.data[props.sectionName].selectedParams.order.field ===
      props.name
        ? appStore.filters.data[props.sectionName].selectedParams.order.value ===
          "asc"
          ? "asc"
          : "desc"
        : "",
  };
});

const filterModes = computed(() => {
  let modes = appStore.filters.availableParams.items;

  if (props.mode != "universal") {
    modes = appStore.filters.availableParams.items.filter(
      (item) => item.type === props.mode
    );
  }

  return modes;
});
</script>

<template>
  <div class="filter-button">
    <q-badge
      v-if="filterBadgeLabel.value != '' || filterBadgeLabel.order != ''"
      color="red"
      class="q-mr-sm"
      style="height: 19px; position: absolute; top: -10px; left: 0px"
      align="middle"
    ><span v-if="filterBadgeLabel.value != ''">
        {{ filterBadgeLabel.mode }}
      </span>
      <span
        style="margin-left: 4px"
        v-if="filterBadgeLabel.value != '' && filterBadgeLabel.order != ''"
      ></span>
      <q-icon
        v-if="filterBadgeLabel.order != ''"
        size="14px"
        :name="filterBadgeLabel.order == 'asc' ? 'expand_less' : 'expand_more'"
      /></q-badge>
    <div class="filter-button-text" :style="`width: ${props.width}px;`">
      {{ props.label }}
    </div>

    <q-menu self="bottom middle" :offset="[-props.width / 2 - 16, -55]">
      <q-inner-loading :showing="sectionStore.data.isItemsLoading">
        <q-spinner-puff size="50px" color="primary" />
      </q-inner-loading>
      <div style="min-width: 250px; min-height: fit-content">
        <div class="row justify-end q-mb-sm">
          <q-btn flat v-close-popup dense icon="close"></q-btn>
        </div>
        <div class="row">
          <div class="filter-body col-12 q-px-md" style="width: 290px;">

            <q-checkbox
              v-if="appStore.filters.data[props.sectionName].selectedParams[props.name + '_is_null']"
              v-model="appStore.filters.data[props.sectionName].selectedParams[props.name + '_is_null'].value"
              label="Тільки записи без дати"
              color="primary"
              class="q-mb-md"
            />

            <DateTimeInputComponent
              v-if="appStore.filters.data[props.sectionName].selectedParams[props.targetFields[0]]"
              :label="searchBarLabel[0] ?? ''"
              dense
              :auto-set-current-time="false"
              class="q-mb-md"
              :disable="appStore.filters.data[props.sectionName].selectedParams[props.name + '_is_null']?.value === true"
              v-model="
                appStore.filters.data[props.sectionName].selectedParams[
                  props.targetFields[0]
                ].value
              ">
            </DateTimeInputComponent>
            <DateTimeInputComponent
              v-if="appStore.filters.data[props.sectionName].selectedParams[props.targetFields[1]]"
              :label="searchBarLabel[1] ?? ''"
              dense
              :auto-set-current-time="false"
              class="q-mb-md"
              :disable="appStore.filters.data[props.sectionName].selectedParams[props.name + '_is_null']?.value === true"
              v-model="
                appStore.filters.data[props.sectionName].selectedParams[
                  props.targetFields[1]
                ].value
              ">
            </DateTimeInputComponent>


            <div class="row justify-between q-mb-md">
              <q-btn
                class="q-px-md"
                style="width: 46%"
                :color="
                  filterOrder.field === props.name &&
                  filterOrder.value === 'asc'
                    ? 'primary'
                    : 'white'
                "
                :text-color="
                  filterOrder.field === props.name &&
                  filterOrder.value === 'asc'
                    ? 'white'
                    : 'black'
                "
                @click="$emit('setFilterOrder', props.name, 'asc')"
              ><q-icon name="arrow_upward" />
                <q-tooltip
                  class="bg-black text-body2"
                  anchor="bottom middle"
                  self="bottom middle"
                  :offset="[0, 40]"
                >
                  {{ props.orderButtonLabels.up }}
                </q-tooltip></q-btn
              >
              <q-btn
                class="q-px-md"
                style="width: 46%"
                :color="
                  filterOrder.field === props.name &&
                  filterOrder.value === 'desc'
                    ? 'primary'
                    : 'white'
                "
                :text-color="
                  filterOrder.field === props.name &&
                  filterOrder.value === 'desc'
                    ? 'white'
                    : 'black'
                "
                @click="$emit('setFilterOrder', props.name, 'desc')"
              ><q-icon name="arrow_downward" />
                <q-tooltip
                  class="bg-black text-body2"
                  anchor="bottom middle"
                  self="bottom middle"
                  :offset="[0, 40]"
                >
                  {{ props.orderButtonLabels.down }}
                </q-tooltip>
              </q-btn>
            </div>
            <div class="row q-mb-md">
              <q-btn
                v-close-popup
                class="col-12"
                @click="$emit('clearFilter', [props.name, ...props.targetFields, props.name + '_is_null'], props.mode)"
              >Скинути</q-btn
              >
            </div>
          </div>
        </div>
      </div>
    </q-menu>
  </div>
  <div class="filter-separator" :name="props.name">
    <div class="vertical-line"></div>
  </div>
</template>

<style scoped>
.filter-button {
  position: relative;
  padding: 0px 16px;
  width: fit-content;
  height: 100%;
  display: flex;
  align-items: center;
  user-select: none;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  border-radius: 5px;
}

.filter-button:hover {
  background-color: rgb(237, 237, 237);
}

.filter-button-text {
  display: block;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
</style>
