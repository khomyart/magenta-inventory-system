<template>
  <q-btn icon="filter_list" round flat style="margin: 0px 5px 0px 11px">
    <q-badge
      v-if="filterOrder.field === 'id'"
      color="red"
      floating
      style="margin-top: -4px"
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
  for (const [key] of Object.entries(filter.selectedParams)) {
    filter.selectedParams[key].value = "";
    filter.selectedParams[key].filterMode =
      appStore.filters.availableParams.items[0];
  }

  filter.selectedParams.order.field = "";
  filter.selectedParams.order.value = "";
  filter.selectedParams.order.combined = "";
}
</script>

<style scoped></style>
