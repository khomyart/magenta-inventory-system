<template>
  <div class="row q-col-gutter-md q-mt-sm">
    <q-input
      class="col-6 q-pt-sm q-mb-md"
      outlined
      v-model="
        sectionStore.newMultipleItems.sizes[props.sizeArrayIndex].detail.title
      "
      label="Назва"
    />
    <q-input
      class="col-6 q-pt-sm q-mb-md"
      outlined
      v-model="
        sectionStore.newMultipleItems.sizes[props.sizeArrayIndex].detail.model
      "
      label="Модель"
    />
    <q-input
      class="col-4 q-pt-sm q-mb-md"
      outlined
      label="Ціна"
      type="number"
      v-model="
        sectionStore.newMultipleItems.sizes[props.sizeArrayIndex].detail.price
      "
    />
    <q-select
      hide-dropdown-icon
      outlined
      label="Валюта"
      v-model="
        sectionStore.newMultipleItems.sizes[props.sizeArrayIndex].detail
          .currency
      "
      :options="['UAH', 'USD', 'EUR']"
      class="col-4 q-pt-sm q-mb-md"
    />
    <q-input
      class="col-4 q-pt-sm q-mb-md"
      outlined
      v-model="
        sectionStore.newMultipleItems.sizes[props.sizeArrayIndex].detail.lack
      "
      label="Нестача"
      type="number"
    />
  </div>
  <AddImagesComponent :index="props.sizeArrayIndex" type="size" />
  <AddAvailableInComponent
    type="sizes"
    :index="props.sizeArrayIndex"
    v-if="props.lastUsedCharacteristic === 'sizes'"
  />
</template>
<script setup>
import { useItemStore } from "src/stores/itemStore";
import AddImagesComponent from "./AddImagesComponent.vue";
import AddAvailableInComponent from "./AddAvailableInComponent.vue";
const sectionStore = useItemStore();
const props = defineProps([
  "sizeArrayIndex",
  "lastUsedCharacteristic",
  "rules",
]);
</script>
