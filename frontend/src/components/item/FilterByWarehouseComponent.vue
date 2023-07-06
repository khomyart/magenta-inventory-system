<template>
  <q-btn flat round color="white" text-color="black" icon="warehouse">
    <q-badge
      v-if="appStore.filters.data.items.selectedParams.warehouse != null"
      color="red"
      style="top: 2px; left: 30px; position: absolute"
      rounded
    >
    </q-badge>
    <q-tooltip
      class="bg-black text-body2"
      anchor="center left"
      self="center right"
      :offset="[10, 10]"
    >
      Пошук за складами
    </q-tooltip>
    <q-menu self="top middle" :offset="[-20, 7]">
      <div style="min-width: 300px; min-height: fit-content">
        <div class="row justify-end q-mb-sm">
          <q-btn flat v-close-popup dense icon="close"></q-btn>
        </div>
        <div class="row q-px-md q-pb-md">
          <q-select
            autocomplete="false"
            outlined
            v-model="appStore.filters.data.items.selectedParams.warehouse"
            label="Склад"
            use-input
            hide-selected
            hide-dropdown-icon
            fill-input
            input-debounce="400"
            :options="warehouseStore.simpleItems"
            option-value="id"
            option-label="name"
            @filter="warehouseFilter"
            :loading="warehouseStore.data.isItemsLoading"
            class="col-12"
          >
            <template v-slot:option="scope">
              <q-item v-bind="scope.itemProps" class="flex items-center">
                - {{ scope.opt.country_name }}
                <br />
                - {{ scope.opt.city_name }}, ({{ scope.opt.address }})
                <br />
                - {{ scope.opt.name }}
              </q-item>
            </template>
            <template v-slot:append>
              <q-icon
                name="cancel"
                v-if="
                  appStore.filters.data.items.selectedParams.warehouse !=
                    null && warehouseStore.data.isItemsLoading == false
                "
                @click.stop.prevent="
                  appStore.filters.data.items.selectedParams.warehouse = null
                "
                class="cursor-pointer"
              />
              <q-icon
                v-if="
                  appStore.filters.data.items.selectedParams.warehouse ==
                    null && warehouseStore.data.isItemsLoading == false
                "
                name="search"
                @click.stop.prevent
              />
            </template>
          </q-select>
        </div>
      </div>
    </q-menu>
  </q-btn>
</template>

<script setup>
import { reactive, ref } from "vue";
import { useWarehouseStore } from "src/stores/warehouseStore";
import { useAppConfigStore } from "src/stores/appConfigStore";

const warehouseStore = useWarehouseStore();
const appStore = useAppConfigStore();

function warehouseFilter(val, update, abort) {
  warehouseStore.simpleItems = [];

  update(() => {
    warehouseStore.simpleReceive(val);
  });
}
</script>

<style scoped></style>
