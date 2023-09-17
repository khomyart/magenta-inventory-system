<template>
  <div class="text-h6 q-mb-md q-mb-sm-sm text-weight-medium text-left q-pl-md">
    {{ warehouseName }}
  </div>
  <div class="row q-gutter-md q-mb-md">
    <div
      :class="{
        'favorite-warehouse-button-active':
          sectionStore.itemMove[props.target].warehouse != null &&
          sectionStore.itemMove[props.target].warehouse.id ===
            warehouseInfo.warehouse.id,
      }"
      class="favorite-warehouse-button q-pa-sm"
      @click="fillWarehouseFromFavorite(index)"
      v-for="(warehouseInfo, index) in warehouseStore.favoriteWarehouses"
      :key="index"
    >
      {{ warehouseInfo.warehouse.name }} ({{ warehouseInfo.city.name }},
      {{ warehouseInfo.warehouse.address }})
    </div>
  </div>
  <div class="row q-col-gutter-md q-mb-sm">
    <q-select
      autocomplete="false"
      :hide-dropdown-icon="sectionStore.itemMove[props.target].country != null"
      outlined
      v-model="sectionStore.itemMove[props.target].country"
      use-input
      hide-selected
      fill-input
      label="Країна"
      input-debounce="400"
      :options="countryStore.items"
      option-value="id"
      option-label="name"
      @update:model-value="countryUpdate"
      @filter="countryFilter"
      :loading="loadingStates.country"
      class="col-12 col-sm-4 q-pt-sm q-pt-sm-md"
      :rules="[
        () =>
          (sectionStore.itemMove[props.target].country != null &&
            sectionStore.itemMove[props.target].country.id != undefined) ||
          'Оберіть країну',
      ]"
    >
      <template
        v-if="
          sectionStore.itemMove[props.target].country && !loadingStates.country
        "
        v-slot:append
      >
        <q-icon
          name="cancel"
          @click.stop.prevent="
            sectionStore.itemMove[props.target].country = null;
            countryUpdate();
          "
          class="cursor-pointer"
        />
      </template>
    </q-select>
    <q-select
      autocomplete="false"
      :hide-dropdown-icon="
        sectionStore.itemMove[props.target].country == null ||
        sectionStore.itemMove[props.target].country.id == undefined ||
        sectionStore.itemMove[props.target].city != null
      "
      outlined
      v-model="sectionStore.itemMove[props.target].city"
      label="Місто"
      use-input
      hide-selected
      fill-input
      input-debounce="400"
      :options="cityStore.items"
      option-value="id"
      option-label="name"
      @update:model-value="cityUpdate"
      @filter="cityFilter"
      :loading="loadingStates.city"
      class="col-12 col-sm-4 q-pt-sm q-pt-sm-md"
      :disable="
        sectionStore.itemMove[props.target].country == null ||
        sectionStore.itemMove[props.target].country.id == undefined
      "
      :rules="[
        () =>
          (sectionStore.itemMove[props.target].city != null &&
            sectionStore.itemMove[props.target].city.id != undefined) ||
          'Оберіть місто',
      ]"
    >
      <template
        v-if="sectionStore.itemMove[props.target].city && !loadingStates.city"
        v-slot:append
      >
        <q-icon
          name="cancel"
          @click.stop.prevent="
            sectionStore.itemMove[props.target].city = null;
            cityUpdate();
          "
          class="cursor-pointer"
        />
      </template>
    </q-select>
    <q-select
      autocomplete="false"
      :hide-dropdown-icon="
        sectionStore.itemMove[props.target].city == null ||
        sectionStore.itemMove[props.target].city.id == undefined ||
        sectionStore.itemMove[props.target].warehouse != null
      "
      outlined
      v-model="sectionStore.itemMove[props.target].warehouse"
      label="Склад"
      use-input
      hide-selected
      fill-input
      input-debounce="400"
      :options="warehouseStore.simpleItems"
      option-value="id"
      option-label="name"
      @filter="warehouseFilter"
      :loading="loadingStates.warehouse"
      class="col-12 col-sm-4 q-pt-sm q-pt-sm-md"
      :disable="
        sectionStore.itemMove[props.target].city == null ||
        sectionStore.itemMove[props.target].city.id == undefined
      "
      :rules="[
        () =>
          (sectionStore.itemMove[props.target].warehouse != null &&
            sectionStore.itemMove[props.target].warehouse.id != undefined) ||
          'Оберіть склад',
      ]"
    >
      <template v-slot:option="scope">
        <q-item v-bind="scope.itemProps" class="flex items-center">
          {{ scope.opt.name }} ({{ scope.opt.address }})
        </q-item>
      </template>
      <template
        v-if="
          sectionStore.itemMove[props.target].warehouse &&
          !loadingStates.warehouse
        "
        v-slot:append
      >
        <q-icon
          name="cancel"
          @click.stop.prevent="
            sectionStore.itemMove[props.target].warehouse = null
          "
          class="cursor-pointer"
        />
      </template>
    </q-select>
  </div>
</template>
<script setup>
import { watch, reactive, computed } from "vue";
import { useCityStore } from "src/stores/helpers/cityStore";
import { useCountryStore } from "src/stores/helpers/countryStore";
import { useWarehouseStore } from "src/stores/warehouseStore";
import { useItemStore } from "src/stores/itemStore";
const sectionStore = useItemStore();
const countryStore = useCountryStore();
const cityStore = useCityStore();
const warehouseStore = useWarehouseStore();

//where target is "from" or "to"
const props = defineProps(["target"]);
let warehouseName = computed(() => {
  return props.target == "from" ? "Звідки" : props.target == "to" ? "Куди" : "";
});

//makes possible to do loading animation for every individual
//set of inputs (co., ci., wa.)
let loadingStates = reactive({
  country: false,
  city: false,
  warehouse: false,
});

function fillWarehouseFromFavorite(index) {
  sectionStore.itemMove[props.target].country =
    warehouseStore.favoriteWarehouses[index].country;
  sectionStore.itemMove[props.target].city =
    warehouseStore.favoriteWarehouses[index].city;
  sectionStore.itemMove[props.target].warehouse =
    warehouseStore.favoriteWarehouses[index].warehouse;
}

function countryFilter(val, update, abort) {
  update(() => {
    loadingStates.country = true;
    countryStore.items = [];
    countryStore.receive(val, loadingStates);
  });
}

function cityFilter(val, update, abort) {
  update(() => {
    loadingStates.city = true;
    cityStore.items = [];
    cityStore.receive(
      sectionStore.itemMove[props.target].country.id,
      val,
      loadingStates
    );
  });
}

function warehouseFilter(val, update, abort) {
  update(() => {
    loadingStates.warehouse = true;
    warehouseStore.simpleItems = [];
    warehouseStore.simpleReceive(
      val,
      sectionStore.itemMove[props.target].city.id,
      loadingStates
    );
  });
}

//if changing country - clear city and warehouse
function countryUpdate() {
  sectionStore.itemMove[props.target].city = null;
  sectionStore.itemMove[props.target].warehouse = null;
}

//if changing city - clear warehouse
function cityUpdate() {
  sectionStore.itemMove[props.target].warehouse = null;
}

//if changing warehouse - clear items
function warehouseUpdate() {
  sectionStore.itemMove.items = [];
}

if (props.target === "from") {
  watch(
    () => sectionStore.itemMove.from.warehouse,
    () => {
      warehouseUpdate();
    }
  );
}
</script>
<style scoped>
.favorite-warehouse-button {
  border-radius: 5px;
  border: 1px solid rgb(0, 0, 0, 0.18);
  cursor: pointer;
  transition: all 0.2s ease-in-out;
}
.favorite-warehouse-button:hover {
  background-color: #b53cda;
  color: white;
}

.favorite-warehouse-button-active {
  background-color: #b53cda;
  color: white;
}
</style>
