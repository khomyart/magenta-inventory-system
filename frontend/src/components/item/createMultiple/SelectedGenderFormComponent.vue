<template>
  <div class="row q-col-gutter-md q-mt-sm">
    <q-input
      class="col-5 q-pt-sm q-mb-md"
      outlined
      v-model="
        sectionStore.newMultipleItems.genders[props.genderArrayIndex].detail
          .article
      "
      label="Артикль"
    />
    <q-input
      class="col-7 q-pt-sm q-mb-md"
      outlined
      v-model="
        sectionStore.newMultipleItems.genders[props.genderArrayIndex].detail
          .model
      "
      label="Модель"
    />
    <q-input
      class="col-12 q-pt-sm q-mb-md"
      outlined
      v-model="
        sectionStore.newMultipleItems.genders[props.genderArrayIndex].detail
          .title
      "
      label="Назва"
    />

    <q-input
      class="col-4 q-pt-sm q-mb-md"
      outlined
      label="Ціна"
      type="number"
      v-model="
        sectionStore.newMultipleItems.genders[props.genderArrayIndex].detail
          .price
      "
    />
    <q-select
      hide-dropdown-icon
      outlined
      label="Валюта"
      v-model="
        sectionStore.newMultipleItems.genders[props.genderArrayIndex].detail
          .currency
      "
      :options="['UAH', 'USD', 'EUR']"
      class="col-4 q-pt-sm q-mb-md"
    />
    <q-input
      class="col-4 q-pt-sm q-mb-md"
      outlined
      v-model="
        sectionStore.newMultipleItems.genders[props.genderArrayIndex].detail
          .lack
      "
      label="Нестача"
      type="number"
    />
  </div>
  <AddImagesComponent :index="props.genderArrayIndex" type="gender" />
  <!-- <AddAvailableInComponent
            :type="
              usedCharacteristics.length === 0
                ? 'main'
                : usedCharacteristics.slice(-1)[0]
            "
            :index="
              usedCharacteristics.length === 0
                ? 0
                : selectedIndexes[usedCharacteristics.slice(-1)]
            "
            v-if="
              usedCharacteristics.length === 0 ||
              selectedIndexes[usedCharacteristics.slice(-1)] != -1
            "
          /> -->
</template>
<script setup>
import { useItemStore } from "src/stores/itemStore";
import AddImagesComponent from "./AddImagesComponent.vue";
const sectionStore = useItemStore();
const props = defineProps([
  "genderArrayIndex",
  "rules",
  "lastUsedCharacteristic",
]);
</script>
