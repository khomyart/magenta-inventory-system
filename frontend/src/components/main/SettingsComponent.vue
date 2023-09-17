<template>
  <q-dialog v-model="sectionStore.dialogs.settings.isShown">
    <q-card style="width: 100vw; max-width: 600px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="settings" color="black" size="md" class="q-mr-sm" />
          Налаштування
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-card-section
        style="max-height: 700px; height: 70vh"
        class="scroll col-12"
      >
        <div class="text-h6 q-mb-md text-weight-medium text-left q-pl-md">
          Обрані склади
        </div>
        <div class="row q-col-gutter-md q-mb-sm">
          <q-select
            autocomplete="false"
            :hide-dropdown-icon="warehouseInfo.country != null"
            outlined
            v-model="warehouseInfo.country"
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
            :rules="[]"
          >
            <template
              v-if="warehouseInfo.country && !loadingStates.country"
              v-slot:append
            >
              <q-icon
                name="cancel"
                @click.stop.prevent="
                  warehouseInfo.country = null;
                  countryUpdate();
                "
                class="cursor-pointer"
              />
            </template>
          </q-select>
          <q-select
            autocomplete="false"
            :hide-dropdown-icon="
              warehouseInfo.country == null ||
              warehouseInfo.country.id == undefined ||
              warehouseInfo.city != null
            "
            outlined
            v-model="warehouseInfo.city"
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
              warehouseInfo.country == null ||
              warehouseInfo.country.id == undefined
            "
            :rules="[
              () =>
                (warehouseInfo.city != null &&
                  warehouseInfo.city.id != undefined) ||
                'Оберіть місто',
            ]"
          >
            <template
              v-if="warehouseInfo.city && !loadingStates.city"
              v-slot:append
            >
              <q-icon
                name="cancel"
                @click.stop.prevent="
                  warehouseInfo.city = null;
                  cityUpdate();
                "
                class="cursor-pointer"
              />
            </template>
          </q-select>
          <q-select
            autocomplete="false"
            :hide-dropdown-icon="
              warehouseInfo.city == null ||
              warehouseInfo.city.id == undefined ||
              warehouseInfo.warehouse != null
            "
            outlined
            v-model="warehouseInfo.warehouse"
            label="Склад"
            use-input
            hide-selected
            fill-input
            input-debounce="400"
            :options="warehouseStore.simpleItems"
            option-value="id"
            option-label="name"
            @filter="warehouseFilter"
            @update:model-value="warehouseUpdate"
            :loading="loadingStates.warehouse"
            class="col-12 col-sm-4 q-pt-sm q-pt-sm-md"
            :disable="
              warehouseInfo.city == null || warehouseInfo.city.id == undefined
            "
            :rules="[
              () =>
                (warehouseInfo.warehouse != null &&
                  warehouseInfo.warehouse.id != undefined) ||
                'Оберіть склад',
            ]"
          >
            <template v-slot:option="scope">
              <q-item v-bind="scope.itemProps" class="flex items-center">
                {{ scope.opt.name }} ({{ scope.opt.address }})
              </q-item>
            </template>
            <template
              v-if="warehouseInfo.warehouse && !loadingStates.warehouse"
              v-slot:append
            >
              <q-icon
                name="cancel"
                @click.stop.prevent="warehouseInfo.warehouse = null"
                class="cursor-pointer"
              />
            </template>
          </q-select>
        </div>
        <div class="row">
          <q-btn
            color="primary"
            @click="addSelectedWarehouseToFavoriteList"
            class="col-12"
            >Додати склад до обраних</q-btn
          >
        </div>
        <div class="row q-col-gutter-sm q-pt-md">
          <template
            v-for="(warehouseInfo, index) in warehouseStore.favoriteWarehouses"
            :key="index"
          >
            <div class="col-12">
              <div
                class="favorite-warehouse-container q-pa-sm flex justify-between items-center"
              >
                <div class="text">
                  {{ warehouseInfo.warehouse.name }} ({{
                    warehouseInfo.country.name
                  }}, {{ warehouseInfo.city.name }} -
                  {{ warehouseInfo.warehouse.address }})
                </div>
                <div>
                  <q-btn
                    flat
                    icon="delete"
                    @click="removeItemFromList(index)"
                    round
                  ></q-btn>
                </div>
              </div>
            </div>
          </template>
        </div>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat color="black" v-close-popup><b>Закрити</b></q-btn>
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { useAppConfigStore } from "src/stores/appConfigStore";
import { useCityStore } from "src/stores/helpers/cityStore";
import { useCountryStore } from "src/stores/helpers/countryStore";
import { useWarehouseStore } from "src/stores/warehouseStore";
import { watch, reactive } from "vue";

const sectionStore = useAppConfigStore();
const countryStore = useCountryStore();
const cityStore = useCityStore();
const warehouseStore = useWarehouseStore();

let warehouseInfo = reactive({
  country: null,
  city: null,
  warehouse: null,
});

//makes possible to do loading animation for every individual
//set of inputs (co., ci., wa.)
let loadingStates = reactive({
  country: false,
  city: false,
  warehouse: false,
  items: false,
});

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
    cityStore.receive(warehouseInfo.country.id, val, loadingStates);
  });
}

function warehouseFilter(val, update, abort) {
  update(() => {
    loadingStates.warehouse = true;
    warehouseStore.simpleItems = [];
    warehouseStore.simpleReceive(val, warehouseInfo.city.id, loadingStates);
  });
}

//if changing country - clear city and warehouse
function countryUpdate() {
  warehouseInfo.city = null;
  warehouseInfo.warehouse = null;
}

//if changing city - clear warehouse
function cityUpdate() {
  warehouseInfo.warehouse = null;
}

//if changing warehouse - clear items
function warehouseUpdate() {
  warehouseInfo.items = [];
}

function addSelectedWarehouseToFavoriteList() {
  if (
    warehouseInfo.country == null ||
    warehouseInfo.city == null ||
    warehouseInfo.warehouse == null
  ) {
    return;
  }

  let isWarehouseInList = false;
  warehouseStore.favoriteWarehouses.forEach((storeWarehouseInfo) => {
    if (storeWarehouseInfo.warehouse.id === warehouseInfo.warehouse.id)
      isWarehouseInList = true;
  });

  if (isWarehouseInList === false) {
    warehouseStore.favoriteWarehouses.push({ ...warehouseInfo });
    warehouseStore.setFavoriteWarehouses();
  }
}

function removeItemFromList(index) {
  warehouseStore.favoriteWarehouses.splice(index, 1);
  warehouseStore.setFavoriteWarehouses();
}

watch(
  () => sectionStore.dialogs.settings.isShown,
  (newValue) => {
    console.log("settings shown");
  }
);
</script>

<style scoped>
.favorite-warehouse-container {
  border-radius: 5px;
  border: 1px solid rgba(0, 0, 0, 0.18);
}

.favorite-warehouse-container > text {
}
</style>
