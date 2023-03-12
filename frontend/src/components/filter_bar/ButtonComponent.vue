<template>
  <q-btn flat stretch class="filter-button">
    <q-badge
      v-if="filterBadgeLabel.value != '' || filterBadgeLabel.order != ''"
      color="red"
      class="q-mr-sm"
      floating
      style="height: 19px"
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
    <div :style="`min-width: ${props.width}px; text-align: start`">
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
          <div class="filter-body col-12 q-px-md">
            <q-input
              class="col-12 q-mb-md"
              outlined
              v-model="
                appStore.filters.data[props.sectionName].selectedParams[
                  props.name
                ].value
              "
              :placeholder="props.searchBarLabel"
              dense
              debounce="700"
            />
            <q-select
              class="col-12 q-mb-md"
              dense
              outlined
              v-model="
                appStore.filters.data[props.sectionName].selectedParams[
                  props.name
                ].filterMode
              "
              :options="appStore.filters.availableParams.items"
              @update:model-value="$emit('changeFilterMode', props.name)"
            >
            </q-select>
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
                ><q-icon name="arrow_upward"
              /></q-btn>
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
                ><q-icon name="arrow_downward"
              /></q-btn>
            </div>
            <div class="row q-mb-md">
              <q-btn
                v-close-popup
                class="col-12"
                @click="$emit('clearFilter', props.name)"
                >Скинути</q-btn
              >
            </div>
          </div>
        </div>
      </div>
    </q-menu>
  </q-btn>
  <div class="filter-separator" :name="props.name">
    <div class="vertical-line"></div>
  </div>
</template>
<script setup>
import { computed } from "vue";

const appStore = props.appStore;
const sectionStore = props.sectionStore;

const props = defineProps([
  "appStore",
  "sectionName",
  "sectionStore",
  "name",
  "label",
  "searchBarLabel",
  "width",
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
  let filter =
    appStore.filters.data[props.sectionName].selectedParams[props.name];

  return {
    value: filter.value != "" ? "V" : "",
    mode: filter.filterMode.shortName,
    order:
      appStore.filters.data[props.sectionName].selectedParams.order.field ==
      props.name
        ? appStore.filters.data[props.sectionName].selectedParams.order.value ==
          "asc"
          ? "asc"
          : "desc"
        : "",
  };
});
</script>
<style></style>
