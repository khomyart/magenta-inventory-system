<template>
  <div class="col-12 warehouse-wrapper q-mb-md">
    <div class="row q-col-gutter-md q-mb-sm">
      <q-select
        autocomplete="false"
        :hide-dropdown-icon="sectionStore.income[props.index].country != null"
        outlined
        v-model="sectionStore.income[props.index].country"
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
        class="col-6"
        :rules="[
          () =>
            (sectionStore.income[props.index].country != null &&
              sectionStore.income[props.index].country.id != undefined) ||
            'Оберіть країну',
        ]"
      >
        <template
          v-if="
            sectionStore.income[props.index].country && !loadingStates.country
          "
          v-slot:append
        >
          <q-icon
            name="cancel"
            @click.stop.prevent="
              sectionStore.income[props.index].country = null;
              countryUpdate();
            "
            class="cursor-pointer"
          />
        </template>
      </q-select>
      <q-select
        autocomplete="false"
        :hide-dropdown-icon="
          sectionStore.income[props.index].country == null ||
          sectionStore.income[props.index].country.id == undefined ||
          sectionStore.income[props.index].city != null
        "
        outlined
        v-model="sectionStore.income[props.index].city"
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
        class="col-6"
        :disable="
          sectionStore.income[props.index].country == null ||
          sectionStore.income[props.index].country.id == undefined
        "
        :rules="[
          () =>
            (sectionStore.income[props.index].city != null &&
              sectionStore.income[props.index].city.id != undefined) ||
            'Оберіть місто',
        ]"
      >
        <template
          v-if="sectionStore.income[props.index].city && !loadingStates.city"
          v-slot:append
        >
          <q-icon
            name="cancel"
            @click.stop.prevent="
              sectionStore.income[props.index].city = null;
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
          sectionStore.income[props.index].city == null ||
          sectionStore.income[props.index].city.id == undefined ||
          sectionStore.income[props.index].warehouse != null
        "
        outlined
        v-model="sectionStore.income[props.index].warehouse"
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
        class="col-10"
        :disable="
          sectionStore.income[props.index].city == null ||
          sectionStore.income[props.index].city.id == undefined
        "
        :rules="[
          () =>
            (sectionStore.income[props.index].warehouse != null &&
              sectionStore.income[props.index].warehouse.id != undefined) ||
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
            sectionStore.income[props.index].warehouse &&
            !loadingStates.warehouse
          "
          v-slot:append
        >
          <q-icon
            name="cancel"
            @click.stop.prevent="
              sectionStore.income[props.index].warehouse = null
            "
            class="cursor-pointer"
          />
        </template>
      </q-select>
      <div class="col-2 d-flex items-start">
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
      <div class="col-4 flex items-center justify-center text-bold text-h6">
        Предмети:
      </div>
      <div class="col-4">
        <q-btn class="q-mr-sm" round flat icon="add" @click="createBatch">
          <q-tooltip
            class="bg-black text-body2"
            anchor="bottom middle"
            self="top middle"
            :offset="[0, 5]"
          >
            Додати партію
          </q-tooltip>
        </q-btn>
        <q-btn
          class="q-mr-sm"
          round
          flat
          icon="delete"
          @click="removeAllBatches"
        >
          <q-tooltip
            class="bg-black text-body2"
            anchor="bottom middle"
            self="top middle"
            :offset="[0, 5]"
          >
            Видалити всі партії
          </q-tooltip>
        </q-btn>
      </div>
    </div>
    <template
      v-for="(item, batchIndex) in sectionStore.income[props.index].batches"
      :key="batchIndex"
    >
      <IncomeCreatorBatchComponent
        :warehouseIndex="props.index"
        :index="batchIndex"
        :imagesStoreUrl="appConfigStore.imagesStoreUrl"
      />
    </template>
  </div>
</template>

<script setup>
import { watch, onMounted, onBeforeUnmount, reactive } from "vue";
import { useCityStore } from "src/stores/helpers/cityStore";
import { useCountryStore } from "src/stores/helpers/countryStore";
import { useItemStore } from "src/stores/itemStore";
import { useWarehouseStore } from "src/stores/warehouseStore";
import { useAppConfigStore } from "src/stores/appConfigStore";
import IncomeCreatorBatchComponent from "./IncomeCreatorBatchComponent.vue";
const sectionStore = useItemStore();
const countryStore = useCountryStore();
const cityStore = useCityStore();
const warehouseStore = useWarehouseStore();
const appConfigStore = useAppConfigStore();

const props = defineProps(["index"]);
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
  items: [],
};

function removeWarehouse() {
  sectionStore.income.splice(props.index, 1);
}

function createBatch() {
  let batch = JSON.parse(JSON.stringify(batchTemplate));
  sectionStore.income[props.index].batches.push(batch);
}

function removeAllBatches() {
  sectionStore.income[props.index].batches = [];
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
      sectionStore.income[props.index].country.id,
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
      sectionStore.income[props.index].city.id,
      loadingStates
    );
  });
}

//if changing country - clear city and warehouse
function countryUpdate() {
  sectionStore.income[props.index].city = null;
  sectionStore.income[props.index].warehouse = null;
}

//if changing city - clear warehouse
function cityUpdate() {
  sectionStore.income[props.index].warehouse = null;
}
</script>
<style scoped>
.warehouse-wrapper {
  border: 1px solid rgba(0, 0, 0, 0.185);
  border-radius: 4px;
  padding: 15px 15px 0px;
}
</style>
