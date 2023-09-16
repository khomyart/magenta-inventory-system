<template>
  <div class="col-12 warehouse-wrapper q-mb-md">
    <div class="row q-gutter-md q-mb-md">
      <div
        class="favorite-warehouse-button q-pa-sm"
        @click="fillWarehouseFromFavorite(index)"
        v-for="(warehouseInfo, index) in warehouseStore.favoriteWarehouses"
        :key="index"
      >
        {{ warehouseInfo.warehouse.name }} ({{ warehouseInfo.city.name }},
        {{ warehouseInfo.warehouse.address }} )
      </div>
    </div>
    <div class="row q-col-gutter-md q-mb-sm">
      <q-select
        autocomplete="false"
        :hide-dropdown-icon="target[props.warehouseIndex].country != null"
        outlined
        v-model="target[props.warehouseIndex].country"
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
        class="col-6 q-mb-md"
      >
        <template
          v-if="target[props.warehouseIndex].country && !loadingStates.country"
          v-slot:append
        >
          <q-icon
            name="cancel"
            @click.stop.prevent="
              target[props.warehouseIndex].country = null;
              countryUpdate();
            "
            class="cursor-pointer"
          />
        </template>
      </q-select>
      <q-select
        autocomplete="false"
        :hide-dropdown-icon="
          target[props.warehouseIndex].country == null ||
          target[props.warehouseIndex].country.id == undefined ||
          target[props.warehouseIndex].city != null
        "
        outlined
        v-model="target[props.warehouseIndex].city"
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
        class="col-6 q-mb-md"
        :disable="
          target[props.warehouseIndex].country == null ||
          target[props.warehouseIndex].country.id == undefined
        "
      >
        <template
          v-if="target[props.warehouseIndex].city && !loadingStates.city"
          v-slot:append
        >
          <q-icon
            name="cancel"
            @click.stop.prevent="
              target[props.warehouseIndex].city = null;
              cityUpdate();
            "
            class="cursor-pointer"
          />
        </template>
      </q-select>
    </div>
    <div class="row q-col-gutter-lg">
      <q-select
        autocomplete="false"
        :hide-dropdown-icon="
          target[props.warehouseIndex].city == null ||
          target[props.warehouseIndex].city.id == undefined ||
          target[props.warehouseIndex].warehouse != null
        "
        outlined
        v-model="target[props.warehouseIndex].warehouse"
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
        class="col-10 q-mb-md"
        :disable="
          target[props.warehouseIndex].city == null ||
          target[props.warehouseIndex].city.id == undefined
        "
      >
        <template v-slot:option="scope">
          <q-item v-bind="scope.itemProps" class="flex items-center">
            {{ scope.opt.name }} ({{ scope.opt.address }})
          </q-item>
        </template>
        <template
          v-if="
            target[props.warehouseIndex].warehouse && !loadingStates.warehouse
          "
          v-slot:append
        >
          <q-icon
            name="cancel"
            @click.stop.prevent="target[props.warehouseIndex].warehouse = null"
            class="cursor-pointer"
          />
        </template>
      </q-select>
      <div class="col-2 flex items-start">
        <q-btn
          class="q-mr-sm"
          flat
          icon="remove"
          style="height: 56px; width: 100%"
          @click="removeWarehouse"
        >
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
    <q-separator class="q-mt-sm" />
    <div class="row q-my-sm">
      <div class="col-4"></div>
      <div
        class="col-4 flex items-center justify-center text-bold"
        style="font-size: 1.1rem"
      >
        Партії:
      </div>
      <div class="col-4">
        <q-btn class="q-mr-sm" round flat icon="add" @click="addBatch">
          <q-tooltip
            class="bg-black text-body2"
            anchor="bottom middle"
            self="top middle"
            :offset="[0, 5]"
          >
            Додати партію
          </q-tooltip>
        </q-btn>
      </div>
    </div>
    <template
      v-for="(item, batchIndex) in target[props.warehouseIndex].batches"
      :key="batchIndex"
    >
      <BatchFormComponent
        :targetType="props.targetType"
        :targetIndex="props.targetIndex"
        :warehouseIndex="props.warehouseIndex"
        :index="batchIndex"
      />
    </template>
  </div>
</template>

<script setup>
import { watch, reactive } from "vue";
import { useCityStore } from "src/stores/helpers/cityStore";
import { useCountryStore } from "src/stores/helpers/countryStore";
import { useItemStore } from "src/stores/itemStore";
import { useWarehouseStore } from "src/stores/warehouseStore";
import BatchFormComponent from "./BatchFormComponent.vue";
const sectionStore = useItemStore();
const countryStore = useCountryStore();
const cityStore = useCityStore();
const warehouseStore = useWarehouseStore();

const props = defineProps(["targetType", "targetIndex", "warehouseIndex"]);

let target =
  props.targetType === "main"
    ? sectionStore.newMultipleItems.main.detail.availableIn
    : sectionStore.newMultipleItems[props.targetType][props.targetIndex].detail
        .availableIn;

//keep "target" reactive without mutation issues
watch([() => props.targetType, () => props.targetIndex], () => {
  target =
    props.targetType === "main"
      ? sectionStore.newMultipleItems.main.detail.availableIn
      : sectionStore.newMultipleItems[props.targetType][props.targetIndex]
          .detail.availableIn;
});

//makes possible to do loading animation for every individual
//set of inputs (co., ci., wa.)
let loadingStates = reactive({
  country: false,
  city: false,
  warehouse: false,
});

let batchTemplate = {
  amount: "",
  price: null,
  currency: "UAH",
};

function fillWarehouseFromFavorite(index) {
  target[props.warehouseIndex].country =
    warehouseStore.favoriteWarehouses[index].country;
  target[props.warehouseIndex].city =
    warehouseStore.favoriteWarehouses[index].city;
  target[props.warehouseIndex].warehouse =
    warehouseStore.favoriteWarehouses[index].warehouse;
}

function removeWarehouse() {
  target.splice(props.warehouseIndex, 1);
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
      target[props.warehouseIndex].country.id,
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
      target[props.warehouseIndex].city.id,
      loadingStates
    );
  });
}

//if changing country - clear city and warehouse
function countryUpdate() {
  target[props.warehouseIndex].city = null;
  target[props.warehouseIndex].warehouse = null;
}

//if changing city - clear warehouse
function cityUpdate() {
  target[props.warehouseIndex].warehouse = null;
}

function addBatch() {
  let batch = JSON.parse(JSON.stringify(batchTemplate));
  target[props.warehouseIndex].batches.push(batch);
}
</script>
<style scoped>
.warehouse-wrapper {
  border: 1px solid rgba(0, 0, 0, 0.185);
  border-radius: 4px;
  padding: 15px 15px 0px;
}

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
</style>
