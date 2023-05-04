<template>
  <q-btn flat round color="black" icon="add" @click="showCreateDialog">
    <q-tooltip
      anchor="bottom left"
      :offset="[-20, 7]"
      class="bg-black text-body2"
    >
      Створити
    </q-tooltip>
  </q-btn>

  <q-dialog v-model="sectionStore.dialogs.create.isShown">
    <q-card>
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="warehouse" color="black" size="md" class="q-mr-sm" />
          Склад
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="sectionStore.create(newItem)">
        <q-card-section
          style="max-height: 50vh; width: 95vw; max-width: 450px"
          class="scroll col-12 q-pt-lg"
        >
          <div class="row q-col-gutter-lg q-mb-sm">
            <q-select
              hide-dropdown-icon
              outlined
              v-model="newItem.country"
              use-input
              hide-selected
              fill-input
              autofocus
              label="Країна"
              input-debounce="400"
              :options="countryStore.items"
              option-value="id"
              option-label="name"
              @filter="countryFilter"
              :loading="countryStore.data.isItemsLoading"
              class="col-6"
              :rules="[
                () =>
                  (newItem.country != null &&
                    newItem.country.id != undefined) ||
                  'Оберіть країну',
              ]"
            >
            </q-select>
            <q-select
              hide-dropdown-icon
              outlined
              v-model="newItem.city"
              label="Місто"
              use-input
              hide-selected
              fill-input
              input-debounce="400"
              :options="cityStore.items"
              option-value="id"
              option-label="name"
              @filter="cityFilter"
              :loading="cityStore.data.isItemsLoading"
              class="col-6"
              :disable="
                newItem.country == null || newItem.country.id == undefined
              "
              :rules="[
                () =>
                  (newItem.city != null && newItem.city.id != undefined) ||
                  'Оберіть місто',
              ]"
            >
            </q-select>
          </div>
          <div class="row q-col-gutter-lg q-mb-sm">
            <q-input
              class="col-6"
              outlined
              v-model="newItem.address"
              label="Адреса"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть адресу',
                (val) => val.length <= 250 || 'Не більше 250 символів',
              ]"
            />
            <q-input
              class="col-6"
              outlined
              v-model="newItem.name"
              label="Назва"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть назву',
                (val) => val.length <= 100 || 'Не більше 100 символів',
              ]"
            />
          </div>

          <q-input
            class="col-12"
            outlined
            v-model="newItem.description"
            label="Опис"
            :rules="[
              (val) => (val !== null && val !== '') || 'Введіть опис',
              (val) => val.length <= 1000 || 'Не більше 1000 символів',
            ]"
          />
        </q-card-section>

        <q-separator />

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
<script setup>
import { reactive, ref, watch } from "vue";
import { useWarehouseStore } from "src/stores/warehouseStore";
import { useCountryStore } from "src/stores/helpers/countryStore";
import { useCityStore } from "src/stores/helpers/cityStore";

const sectionStore = useWarehouseStore();
const countryStore = useCountryStore();
const cityStore = useCityStore();

let newItem = reactive({
  country: "",
  city: "",
  address: "",
  name: "",
  description: "",
});

watch(
  () => newItem.country,
  () => {
    newItem.city = "";
  }
);

function showCreateDialog() {
  sectionStore.dialogs.create.isShown = true;
  newItem.country = "";
  newItem.city = "";
  newItem.address = "";
  newItem.name = "";
  newItem.description = "";
}

function countryFilter(val, update, abort) {
  update(() => {
    countryStore.data.isItemsLoading = true;
    countryStore.items = [];
    countryStore.receive(val);
  });
}

function cityFilter(val, update, abort) {
  update(() => {
    cityStore.data.isItemsLoading = true;
    cityStore.items = [];
    cityStore.receive(newItem.country.id, val);
  });
}
</script>
<style scoped></style>
