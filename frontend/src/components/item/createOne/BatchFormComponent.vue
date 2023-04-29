<template>
  <div class="col-12 batch-wrapper q-mb-md q-pt-md q-px-md">
    <div class="row q-col-gutter-md q-mb-xs">
      <q-input
        dense
        type="number"
        class="col-4"
        outlined
        v-model="
          sectionStore.newItem.availableIn[props.warehouseIndex].batches[
            props.index
          ].amount
        "
        label="Кількість"
        :rules="[
          (val) => (val !== null && val !== '') || 'Введіть кількість',
          (val) => val >= 0 || 'Не менше 0',
        ]"
      />
      <q-input
        dense
        type="number"
        class="col-3"
        outlined
        v-model="
          sectionStore.newItem.availableIn[props.warehouseIndex].batches[
            props.index
          ].price
        "
        label="Ціна"
        :rules="[
          (val) => (val !== null && val !== '') || 'Введіть ціну',
          (val) => val >= 0 || 'Не менше 0',
        ]"
      />
      <q-select
        class="col-3"
        dense
        hide-dropdown-icon
        outlined
        v-model="
          sectionStore.newItem.availableIn[props.warehouseIndex].batches[
            props.index
          ].currency
        "
        label="Валюта"
        :options="['UAH', 'USD', 'EUR']"
      />
      <div class="col-2">
        <q-btn
          flat
          icon="remove"
          style="height: 39px; width: 100%"
          @click="removeBatch"
          v-if="props.index != 0"
        >
          <q-tooltip
            class="bg-black text-body2"
            anchor="bottom middle"
            self="top middle"
            :offset="[0, 5]"
          >
            Видалити партію
          </q-tooltip>
        </q-btn>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useItemStore } from "src/stores/itemStore";
const sectionStore = useItemStore();
const props = defineProps(["warehouseIndex", "index"]);

function removeBatch() {
  if (props.index != 0) {
    sectionStore.newItem.availableIn[props.warehouseIndex].batches.splice(
      props.index,
      1
    );
  }
}
</script>

<style scoped>
.batch-wrapper {
  border: 1px solid rgba(96, 0, 92, 0.18);
  border-radius: 4px;
}
</style>
