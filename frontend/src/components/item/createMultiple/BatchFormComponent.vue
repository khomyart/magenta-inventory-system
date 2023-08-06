<template>
  <div class="col-12 batch-wrapper q-mb-md q-pt-md q-px-md">
    <div class="row q-col-gutter-md q-mb-xs">
      <q-input
        type="number"
        class="col-4"
        outlined
        v-model="target.batches[props.index].amount"
        label="Кількість"
        :rules="[
          (val) => (val !== null && val !== '') || 'Введіть кількість',
          (val) => val >= 1 || 'Не менше 1',
        ]"
      />
      <q-input
        type="number"
        class="col-3"
        outlined
        v-model="target.batches[props.index].price"
        label="Ціна"
        :rules="[
          (val) => (val !== null && val !== '') || 'Введіть ціну',
          (val) => val >= 1 || 'Не менше 1',
        ]"
      />
      <q-select
        class="col-3"
        hide-dropdown-icon
        outlined
        v-model="target.batches[props.index].currency"
        label="Валюта"
        :options="['UAH', 'USD', 'EUR']"
      />
      <div class="col-2">
        <q-btn
          flat
          icon="remove"
          style="height: 56px; width: 100%"
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
import { watch } from "vue";
import { useItemStore } from "src/stores/itemStore";
const sectionStore = useItemStore();
const props = defineProps([
  "targetType",
  "targetIndex",
  "warehouseIndex",
  "index",
]);

let target =
  props.targetType === "main"
    ? sectionStore.newMultipleItems.main.detail.availableIn[
        props.warehouseIndex
      ]
    : sectionStore.newMultipleItems[props.targetType][props.targetIndex].detail
        .availableIn[props.warehouseIndex];

//keep "target" reactive without mutation issues
watch([() => props.targetType, () => props.targetIndex], () => {
  target =
    props.targetType === "main"
      ? sectionStore.newMultipleItems.main.detail.availableIn[
          props.warehouseIndex
        ]
      : sectionStore.newMultipleItems[props.targetType][props.targetIndex]
          .detail.availableIn[props.warehouseIndex];
});

function removeBatch() {
  if (props.index != 0) {
    target.batches.splice(props.index, 1);
  }
}
</script>

<style scoped>
.batch-wrapper {
  border: 1px solid rgba(96, 0, 92, 0.18);
  border-radius: 4px;
}
</style>
