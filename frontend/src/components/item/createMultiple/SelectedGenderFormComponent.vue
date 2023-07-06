<template>
  <div class="row q-col-gutter-md q-mt-sm q-mb-sm">
    <q-input
      class="col-5 q-pt-sm"
      outlined
      v-model="
        sectionStore.newMultipleItems.genders[props.genderArrayIndex].detail
          .article
      "
      label="Артикль"
      :rules="[
        // (val) => (val !== null && val !== '') || 'Введіть артикль',
        // (val) => val.length <= 10 || 'Не більше 10 символів',
      ]"
    />
    <q-input
      class="col-7 q-pt-sm"
      outlined
      v-model="
        sectionStore.newMultipleItems.genders[props.genderArrayIndex].detail
          .model
      "
      label="Модель"
      :rules="[
        // (val) => (val !== null && val !== '') || 'Введіть модель',
        // (val) => val.length <= 255 || 'Не більше 255 символів',
      ]"
    />
    <q-input
      class="col-12 q-pt-sm"
      outlined
      v-model="
        sectionStore.newMultipleItems.genders[props.genderArrayIndex].detail
          .title
      "
      label="Назва"
      :rules="[
        // (val) => (val !== null && val !== '') || 'Введіть назву',
        // (val) => val.length <= 255 || 'Не більше 255 символів',
      ]"
    />

    <q-input
      class="col-4 q-pt-sm"
      outlined
      label="Ціна"
      type="number"
      v-model="
        sectionStore.newMultipleItems.genders[props.genderArrayIndex].detail
          .price
      "
      :rules="[
        // (val) => (val !== null && val !== '') || 'Вкажіть ціну',
        // (val) => val.length <= 13 || 'Не більше 13 символів',
        // (val) => val >= 1 || 'Не менше 1',
      ]"
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
      class="col-4 q-pt-sm"
    />
    <q-input
      class="col-4 q-pt-sm"
      outlined
      v-model="
        sectionStore.newMultipleItems.genders[props.genderArrayIndex].detail
          .lack
      "
      label="Нестача"
      type="number"
      :rules="[
        // (val) => (val !== null && val !== '') || 'Вкажіть нестачу',
        // (val) => val >= 1 || 'Не менше одиниці',
      ]"
    />
  </div>
</template>
<script setup>
import { useItemStore } from "src/stores/itemStore";
const sectionStore = useItemStore();
const props = defineProps(["genderArrayIndex"]);
</script>
