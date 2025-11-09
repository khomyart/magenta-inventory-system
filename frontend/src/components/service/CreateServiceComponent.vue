<script setup>
import {reactive} from "vue";
import {useServiceStore} from "stores/serviceStore";

const sectionStore = useServiceStore();

function showCreateDialog() {
  newItem.title = "";
  newItem.price = "";
  newItem.currency = "UAH";
  sectionStore.dialogs.create.isShown = true;
}

let newItem = reactive({
  title: "",
  price: "",
  currency: "UAH",
});
</script>

<template>
  <q-btn flat round color="black" icon="add" @click="showCreateDialog">
    <q-tooltip anchor="bottom left" :offset="[-20, 7]" class="bg-black text-body2">
      Створити
    </q-tooltip>
  </q-btn>

  <q-dialog v-model="sectionStore.dialogs.create.isShown">
    <q-card>
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="miscellaneous_services" color="black" size="md" class="q-mr-sm"/>
          Послуга
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="sectionStore.create(newItem)">
        <q-card-section
          style="max-height: 50vh; width: 400px"
          class="scroll q-pt-md row q-col-gutter-sm"
        >
          <q-input
            class="col-12"
            outlined
            v-model="newItem.title"
            autofocus
            label="Назва послуги"
            :rules="[
                (val) => (val !== null && val !== '') || 'Введіть назву',
                (val) => val.length <= 255 || 'Не більше 255 символів',
              ]"
          />
          <q-input
            outlined
            class="col-6"
            v-model="newItem.price"
            label="Ціна"
            type="number"
            step="0.01"
            :rules="[
                (val) => (val !== null && val !== '') || 'Вкажіть ціну',
                (val) => val >= 0.01 || 'Не менше 0.01',
              ]"
          />
          <q-select
            hide-dropdown-icon
            outlined
            v-model="newItem.currency"
            label="Валюта"
            :options="['UAH', 'USD', 'EUR']"
            class="col-6"
          />
        </q-card-section>

        <q-separator/>

        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn
            flat
            color="primary"
            type="submit"
            :loading="sectionStore.dialogs.create.isLoading"
          ><b>Створити</b></q-btn
          >
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
