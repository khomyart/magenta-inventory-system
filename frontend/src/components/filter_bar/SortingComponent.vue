<template>
  <q-btn icon="filter_list" round flat style="margin: 0px 5px 0px 11px">
    <q-badge
      v-if="filterOrder.field === 'id'"
      color="red"
      style="margin-top: -4px; position: absolute; top: -8px; left: 0px"
      ><q-icon
        size="14px"
        :name="
          filterOrder.field === 'id'
            ? filterOrder.value === 'asc'
              ? 'expand_less'
              : 'expand_more'
            : ''
        "
      />
    </q-badge>
    <q-tooltip
      anchor="bottom left"
      :offset="[55, -37]"
      class="bg-black text-body2"
    >
      Сортування
    </q-tooltip>
    <q-menu self="top middle" :offset="[-24, 8]">
      <q-inner-loading :showing="sectionStore.data.isItemsLoading">
        <q-spinner-puff size="50px" color="primary" />
      </q-inner-loading>

      <q-list style="width: 270px">
        <q-item
          v-close-popup
          v-ripple
          clickable
          :active="filterOrder.field === 'id' && filterOrder.value === 'desc'"
          @click="setFilterOrder('id', 'desc')"
          active-class="text-purple"
        >
          <q-item-section>Від новішого до старішого</q-item-section>
          <q-item-section
            v-if="filterOrder.field === 'id' && filterOrder.value === 'desc'"
            avatar
          >
            <q-icon name="done" />
          </q-item-section>
        </q-item>
        <q-item
          v-close-popup
          v-ripple
          clickable
          :active="filterOrder.field === 'id' && filterOrder.value === 'asc'"
          @click="setFilterOrder('id', 'asc')"
          active-class="text-purple"
        >
          <q-item-section>Від старішого до новішого</q-item-section>
          <q-item-section
            v-if="filterOrder.field === 'id' && filterOrder.value === 'asc'"
            avatar
          >
            <q-icon name="done" />
          </q-item-section>
        </q-item>
        <q-separator></q-separator>
        <q-item v-ripple clickable v-close-popup @click="clearAllFilters">
          <q-item-section>Скинути усі фільтри</q-item-section>
        </q-item>
      </q-list>
    </q-menu>
  </q-btn>
</template>

<script setup>
import { computed } from "vue";
import { useAppConfigStore } from "src/stores/appConfigStore";

const appStore = useAppConfigStore();
const sectionStore = props.sectionStore;

const props = defineProps(["filterIn", "sectionStore"]);

const filterOrder = computed(() => {
  return {
    field: appStore.filters.data[props.filterIn].selectedParams.order.field,
    value: appStore.filters.data[props.filterIn].selectedParams.order.value,
  };
});

function setFilterOrder(field, fieldOrder) {
  let order = appStore.filters.data[props.filterIn].selectedParams.order;
  if (order.field === field && order.value === fieldOrder) {
    order.field = "";
    order.value = "";
    order.combined = "";
  } else {
    order.field = field;
    order.value = fieldOrder;
    order.combined = `${field}${fieldOrder}`;
  }
}

function clearAllFilters() {
  let filter = appStore.filters.data[props.filterIn];

  // Define numeric fields that should use "more" filter mode
  const numericFields = ['price', 'amount', 'id', 'total_price', 'advance_payment', 'final_payment', 'remaining_to_pay'];
  // Define select fields that should use "equal" filter mode
  const selectFields = ['status'];
  // Define date "from" fields that should use "more" filter mode
  const dateFromFields = ['completion_deadline_from', 'created_at_from', 'completed_at_from', 'fully_payed_at_from'];
  // Define date "to" fields that should use "less" filter mode
  const dateToFields = ['completion_deadline_to', 'created_at_to', 'completed_at_to', 'fully_payed_at_to'];
  // Define boolean fields (is_null) that should be reset to false
  const booleanFields = ['completion_deadline_is_null', 'created_at_is_null', 'completed_at_is_null', 'fully_payed_at_is_null'];

  for (const [key] of Object.entries(filter.selectedParams)) {
    if (key === "warehouse") continue;

    // Reset boolean fields to false, others to empty string
    if (booleanFields.includes(key)) {
      filter.selectedParams[key].value = false;
    } else {
      filter.selectedParams[key].value = "";
    }

    if (numericFields.includes(key) || dateFromFields.includes(key)) {
      // "more" mode for numeric fields and date "from" fields
      filter.selectedParams[key].filterMode =
        appStore.filters.availableParams.items[2];
    } else if (dateToFields.includes(key)) {
      // "less" mode for date "to" fields
      filter.selectedParams[key].filterMode =
        appStore.filters.availableParams.items[3];
    } else if (selectFields.includes(key)) {
      // "equal" mode for select filters
      filter.selectedParams[key].filterMode =
        appStore.filters.availableParams.items[6];
    } else if (!booleanFields.includes(key)) {
      // "include" mode for text/string filters (skip boolean fields)
      filter.selectedParams[key].filterMode =
        appStore.filters.availableParams.items[0];
    }
  }

  filter.selectedParams.order.field = "";
  filter.selectedParams.order.value = "";
  filter.selectedParams.order.combined = "";
}
</script>

<style scoped></style>
