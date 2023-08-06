<template>
  <div class="row q-mb-sm text-h6">
    <div class="col-4"></div>
    <div class="col-4 flex items-center justify-center">Наявність</div>
    <div class="col-4">
      <q-btn class="q-mr-sm" round flat icon="add" @click="addAvailableIn">
        <q-tooltip
          class="bg-black text-body2"
          anchor="bottom middle"
          self="top middle"
          :offset="[0, 5]"
        >
          Додати склад
        </q-tooltip>
      </q-btn>
      <q-btn
        class="q-mr-sm"
        round
        flat
        icon="delete"
        @click="removeAllWarehouses"
      >
        <q-tooltip
          class="bg-black text-body2"
          anchor="bottom middle"
          self="top middle"
          :offset="[0, 5]"
        >
          Видалити всі склади
        </q-tooltip>
      </q-btn>
    </div>
  </div>
  <WarehouseFormComponent
    v-for="(item, index) in target.availableIn"
    :targetType="props.type"
    :targetIndex="props.index"
    :key="index"
    :warehouseIndex="index"
  ></WarehouseFormComponent>
</template>
<script setup>
import { watch, onMounted } from "vue";
import { useItemStore } from "src/stores/itemStore";
import WarehouseFormComponent from "./WarehouseFormComponent.vue";

const props = defineProps(["type", "index"]);
const sectionStore = useItemStore();
const warehouseTemplate = {
  country: null,
  city: null,
  warehouse: null,
  batches: [
    {
      amount: "",
      price: null,
      currency: "UAH",
    },
  ],
};

let target =
  props.type === "main"
    ? sectionStore.newMultipleItems.main.detail
    : sectionStore.newMultipleItems[props.type][props.index].detail;

//keep "target" reactive without mutation issues
watch([() => props.type, () => props.index], () => {
  target =
    props.type === "main"
      ? sectionStore.newMultipleItems.main.detail
      : sectionStore.newMultipleItems[props.type][props.index].detail;
});

function addAvailableIn() {
  let warehouse = JSON.parse(JSON.stringify(warehouseTemplate));
  target.availableIn.push(warehouse);
}

function removeAllWarehouses() {
  target.availableIn = [];
}
</script>
<style></style>
