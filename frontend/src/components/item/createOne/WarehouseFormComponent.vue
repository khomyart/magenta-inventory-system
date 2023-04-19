<template>
  <div class="col-12 warehouse-wrapper">
    <div class="row q-col-gutter-md q-mb-sm">
      <q-select
        :hide-dropdown-icon="newItem.warehouses[0].country != null"
        outlined
        v-model="newItem.warehouses[0].country"
        use-input
        hide-selected
        fill-input
        label="Країна"
        input-debounce="400"
        :options="countryStore.items"
        option-value="id"
        option-label="name"
        @update:model-value="$emit('update:item', $event.target.value)"
        @filter="countryFilter"
        :loading="countryStore.data.isItemsLoading"
        class="col-6"
        :rules="[
          () =>
            (newItem.warehouses[0].country != null &&
              newItem.warehouses[0].country.id != undefined) ||
            'Оберіть країну',
        ]"
      >
        <template
          v-if="
            newItem.warehouses[0].country && !countryStore.data.isItemsLoading
          "
          v-slot:append
        >
          <q-icon
            name="cancel"
            @click.stop.prevent="newItem.warehouses[0].country = null"
            class="cursor-pointer"
          />
        </template>
      </q-select>
      <q-select
        :hide-dropdown-icon="
          newItem.warehouses[0].country == null ||
          newItem.warehouses[0].country.id == undefined ||
          newItem.warehouses[0].city != null
        "
        outlined
        v-model="newItem.warehouses[0].city"
        label="Місто"
        use-input
        hide-selected
        fill-input
        input-debounce="400"
        :options="cityStore.items"
        option-value="id"
        option-label="name"
        @filter="cityFilter"
        :loading="cityStore.data.isItemsLoading"
        class="col-6"
        :disable="
          newItem.warehouses[0].country == null ||
          newItem.warehouses[0].country.id == undefined
        "
        :rules="[
          () =>
            (newItem.warehouses[0].city != null &&
              newItem.warehouses[0].city.id != undefined) ||
            'Оберіть місто',
        ]"
      >
        <template
          v-if="newItem.warehouses[0].city && !cityStore.data.isItemsLoading"
          v-slot:append
        >
          <q-icon
            name="cancel"
            @click.stop.prevent="newItem.warehouses[0].city = null"
            class="cursor-pointer"
          />
        </template>
      </q-select>
    </div>
    <div class="row q-col-gutter-lg">
      <q-select
        :hide-dropdown-icon="
          newItem.warehouses[0].city == null ||
          newItem.warehouses[0].city.id == undefined ||
          newItem.warehouses[0].warehouse != null
        "
        outlined
        v-model="newItem.warehouses[0].warehouse"
        label="Склад"
        use-input
        hide-selected
        fill-input
        input-debounce="400"
        :options="warehouseStore.simpleItems"
        option-value="id"
        option-label="name"
        @filter="warehouseFilter"
        :loading="warehouseStore.data.isItemsLoading"
        class="col-10"
        :disable="
          newItem.warehouses[0].city == null ||
          newItem.warehouses[0].city.id == undefined
        "
        :rules="[
          () =>
            (newItem.warehouses[0].warehouse != null &&
              newItem.warehouses[0].warehouse.id != undefined) ||
            'Оберіть склад',
        ]"
      >
        <template
          v-if="
            newItem.warehouses[0].warehouse &&
            !warehouseStore.data.isItemsLoading
          "
          v-slot:append
        >
          <q-icon
            name="cancel"
            @click.stop.prevent="newItem.warehouses[0].warehouse = null"
            class="cursor-pointer"
          />
        </template>
      </q-select>
      <div class="col-2 flex items-start">
        <q-btn class="q-mr-sm" icon="remove" style="height: 55px; width: 100%">
          <q-tooltip
            class="bg-black text-body2"
            anchor="bottom middle"
            self="top middle"
            :offset="[0, 5]"
          >
            Видалити склад
          </q-tooltip>
        </q-btn>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  item: Object,
  warehouseStore: Object,
});

defineEmits(["update:item"]);
</script>
<style scoped>
.warehouse-wrapper {
  border: 1px solid rgba(0, 0, 0, 0.18);
  border-radius: 4px;
  padding: 15px 15px 7px;
}
</style>
