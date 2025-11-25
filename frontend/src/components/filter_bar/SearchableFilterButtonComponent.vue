<template>
  <div class="filter-button">
    <q-badge
      v-if="filterBadgeLabel.value != '' || filterBadgeLabel.order != ''"
      color="red"
      class="q-mr-sm"
      style="height: 19px; position: absolute; top: -10px; left: 0px"
      align="middle"
    >
      <span v-if="filterBadgeLabel.value != ''">V</span>
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
      <q-inner-loading :showing="sectionStore.data.isItemsLoading">
        <q-spinner-puff size="50px" color="primary" />
      </q-inner-loading>
      <div style="width: 270px; max-width: 270px; min-height: fit-content">
        <div class="row justify-end q-mb-sm">
          <q-btn flat v-close-popup dense icon="close"></q-btn>
        </div>
        <div class="row">
          <div class="filter-body col-12 q-px-md">
            <!-- Selected Entity Display -->
            <div v-if="selectedEntity" class="selected-entity q-mb-md">
              <div class="selected-entity-content">
                <div class="selected-entity-text">
                  {{ formatEntityDisplay(selectedEntity) }}
                </div>
<!--                <q-btn-->
<!--                  flat-->
<!--                  dense-->
<!--                  round-->
<!--                  color="negative"-->
<!--                  icon="close"-->
<!--                  size="sm"-->
<!--                  @click="clearSelection"-->
<!--                />-->
              </div>
            </div>

            <!-- Search Input -->
            <q-select
              v-if="!selectedEntity"
              autofocus
              class="col-12 q-mb-md"
              outlined
              use-input
              hide-selected
              v-model="tempEntityHolder"
              :placeholder="props.searchBarLabel"
              input-debounce="400"
              :options="searchResults"
              :option-label="props.optionLabel || 'name'"
              :option-value="props.optionValue || 'id'"
              @filter="handleFilter"
              @update:model-value="handleEntitySelected"
              :loading="isLoading"
              hide-dropdown-icon
              dense
            >
              <template v-slot:append v-if="!isLoading">
                <q-icon name="search" />
              </template>
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ formatEntityDisplay(scope.opt) }}</q-item-label>
                    <q-item-label caption v-if="formatEntitySubtext(scope.opt)">
                      {{ formatEntitySubtext(scope.opt) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </template>
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
                @click="$emit('clearFilter', props.name, 'entity')"
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
import { computed, ref, watch } from "vue";
import { api } from "src/boot/axios";
import { useAppConfigStore } from "src/stores/appConfigStore";

const appConfigStore = useAppConfigStore();
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
  "apiEndpoint", // API endpoint for search (e.g., '/contacts/simple')
  "searchParam", // Parameter name for search (e.g., 'search_filter_value')
  "optionLabel", // Property name for option label (e.g., 'name')
  "optionValue", // Property name for option value (e.g., 'id')
  "displayTemplate", // Function to format display text
  "subtextTemplate", // Function to format subtext
]);

const emits = defineEmits([
  "clearFilter",
  "setFilterOrder",
]);

const searchResults = ref([]);
const isLoading = ref(false);
const tempEntityHolder = ref(null);
const selectedEntity = ref(null);

// Watch for changes in the filter value to update selectedEntity
watch(
  () => appStore.filters.data[props.sectionName].selectedParams[props.name]?.value,
  (newValue) => {
    if (!newValue || newValue === "") {
      selectedEntity.value = null;
    }
  }
);

const filterOrder = computed(() => {
  return {
    field: appStore.filters.data[props.sectionName].selectedParams.order.field,
    value: appStore.filters.data[props.sectionName].selectedParams.order.value,
  };
});

const filterBadgeLabel = computed(() => {
  let filter =
    appStore.filters.data[props.sectionName].selectedParams[props.name];

  // Return empty badges if filter doesn't exist
  if (!filter) {
    return {
      value: "",
      order: "",
    };
  }

  return {
    value: filter.value != "" && filter.value != null ? "V" : "",
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

function formatEntityDisplay(entity) {
  if (!entity) return "";
  if (props.displayTemplate) {
    return props.displayTemplate(entity);
  }
  return entity[props.optionLabel || 'name'] || "";
}

function formatEntitySubtext(entity) {
  if (!entity) return "";
  if (props.subtextTemplate) {
    return props.subtextTemplate(entity);
  }
  return "";
}

function handleFilter(val, update, abort) {
  if (!val || val.length === 0) {
    update(() => {
      searchResults.value = [];
    });
    return;
  }

  update(() => {
    isLoading.value = true;
    searchResults.value = [];

    const params = {};
    params[props.searchParam || 'search'] = val;

    api.get(props.apiEndpoint, { params })
      .then((res) => {
        searchResults.value = res.data.data || res.data || [];
      })
      .catch((err) => {
        appConfigStore.catchRequestError(err);
      })
      .finally(() => {
        isLoading.value = false;
      });
  });
}

function handleEntitySelected(entity) {
  if (entity && entity[props.optionValue || 'id']) {
    selectedEntity.value = entity;
    const filterParam = appStore.filters.data[props.sectionName].selectedParams[props.name];
    if (filterParam) {
      filterParam.value = entity[props.optionValue || 'id'];
    }
  }
  tempEntityHolder.value = null;
}

function clearSelection() {
  selectedEntity.value = null;
  const filterParam = appStore.filters.data[props.sectionName].selectedParams[props.name];
  if (filterParam) {
    filterParam.value = "";
  }
  tempEntityHolder.value = null;
}
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

.selected-entity {
  background-color: #f5f5f5;
  border: 1px solid #d0d0d0;
  border-radius: 4px;
  padding: 8px 12px;
}

.selected-entity-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.selected-entity-text {
  flex: 1;
  font-size: 0.9em;
  color: #333;
}
</style>
