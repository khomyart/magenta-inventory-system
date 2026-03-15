<template>
  <div class="filter-button">
    <q-badge
      v-if="filterBadgeLabel.value != '' || filterBadgeLabel.order != ''"
      color="red"
      class="q-mr-sm"
      style="height: 19px; position: absolute; top: -10px; left: 0px"
      align="middle"
    >
      <span v-if="filterBadgeLabel.value != ''">
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
      />
    </q-badge>
    <div class="filter-button-text" :style="`width: ${props.width}px;`">
      {{ props.label }}
    </div>

    <q-menu self="bottom middle" :offset="[-props.width / 2 - 16, -55]">
      <q-inner-loading :showing="props.sectionStore.data.isItemsLoading">
        <q-spinner-puff size="50px" color="primary" />
      </q-inner-loading>
      <div style="min-width: 250px; min-height: fit-content">
        <div class="row justify-end q-mb-sm">
          <q-btn flat v-close-popup dense icon="close"></q-btn>
        </div>
        <div class="row">
          <div class="filter-body col-12 q-px-md">
            <q-select
              v-if="props.appStore.filters.data[props.sectionName].selectedParams[props.name]"
              autofocus
              class="col-12 q-mb-md"
              outlined
              v-model="internalValue"
              :options="props.options"
              :option-label="props.optionLabel || 'label'"
              :option-value="props.optionValue || 'value'"
              emit-value
              map-options
              :placeholder="props.searchBarLabel"
              dense
            />
            <q-select
              v-if="props.appStore.filters.data[props.sectionName].selectedParams[props.name]"
              class="col-12 q-mb-md"
              dense
              outlined
              v-model="internalFilterMode"
              :options="filterModes"
              @update:model-value="$emit('changeFilterMode', props.name)"
            />
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
              >
                <q-icon name="arrow_upward" />
                <q-tooltip
                  class="bg-black text-body2"
                  anchor="bottom middle"
                  self="bottom middle"
                  :offset="[0, 40]"
                >
                  {{ props.orderButtonLabels.up }}
                </q-tooltip>
              </q-btn>
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
              >
                <q-icon name="arrow_downward" />
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
                @click="$emit('clearFilter', props.name, 'select')"
              >
                Скинути
              </q-btn>
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

<script setup>
import { computed } from "vue";

const props = defineProps({
  appStore: Object,
  sectionName: String,
  sectionStore: Object,
  name: String,
  label: String,
  searchBarLabel: String,
  orderButtonLabels: Object,
  width: [Number, String],
  options: {
    type: Array,
    default: () => []
  },
  optionLabel: String,
  optionValue: String,
  // Add missing props to prevent "Extraneous non-props attributes" warning
  mode: String,
  targetFields: Array
});

const emits = defineEmits([
  "clearFilter",
  "changeFilterMode",
  "setFilterOrder",
]);

// Helper to access the filter object safely
const currentFilter = computed(() => {
  return props.appStore.filters.data[props.sectionName].selectedParams[props.name];
});

// Use computed with getter/setter for v-model to avoid prop mutation error
const internalValue = computed({
  get: () => currentFilter.value?.value,
  set: (val) => {
    if (currentFilter.value) {
      currentFilter.value.value = val;
    }
  }
});

const internalFilterMode = computed({
  get: () => currentFilter.value?.filterMode,
  set: (val) => {
    if (currentFilter.value) {
      currentFilter.value.filterMode = val;
    }
  }
});

const filterOrder = computed(() => {
  return {
    field: props.appStore.filters.data[props.sectionName].selectedParams.order.field,
    value: props.appStore.filters.data[props.sectionName].selectedParams.order.value,
  };
});

const filterBadgeLabel = computed(() => {
  let filter = currentFilter.value;

  if (!filter) {
    return {
      value: "",
      mode: "",
      order: "",
    };
  }

  return {
    value: filter.value != "" && filter.value != null ? "V" : "",
    mode: filter.filterMode?.shortName || "",
    order:
      props.appStore.filters.data[props.sectionName].selectedParams.order.field ==
      props.name
        ? props.appStore.filters.data[props.sectionName].selectedParams.order.value ==
          "asc"
          ? "asc"
          : "desc"
        : "",
  };
});

const filterModes = computed(() => {
  return props.appStore.filters.availableParams.items.filter(
    (item) => item.type === "select"
  );
});
</script>

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
