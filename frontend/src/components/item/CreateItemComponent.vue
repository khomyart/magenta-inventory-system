<template>
  <q-dialog v-model="sectionStore.dialogs.create.isShown">
    <q-card>
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="apps" color="black" size="md" class="q-mr-sm" />
          Предмет
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="sectionStore.create(newItem)">
        <q-card-section
          style="max-height: 60vh; width: 95vw; max-width: 500px"
          class="scroll col-12 q-pt-lg"
        >
          <div class="row q-col-gutter-md q-mb-sm">
            <q-input
              class="col-12"
              outlined
              v-model="newItem.title"
              label="Назва"
              autofocus
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть назву',
                (val) => val.length <= 10 || 'Не більше 255 символів',
              ]"
            />
          </div>

          <div class="row q-col-gutter-md q-mb-sm">
            <q-input
              class="col-5"
              outlined
              v-model="newItem.article"
              label="Артикль"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть артикль',
                (val) => val.length <= 10 || 'Не більше 10 символів',
              ]"
            />
            <q-input
              class="col-4"
              outlined
              v-model="newItem.price"
              label="Ціна"
              :rules="[
                (val) => (val !== null && val !== '') || 'Вкажіть ціну',
                (val) => val.length <= 13 || 'Не більше 13 символів',
              ]"
            />
            <q-select
              hide-dropdown-icon
              outlined
              v-model="newItem.currency"
              label="Валюта"
              :options="['UAH', 'USD', 'EUR']"
              class="col-3"
            />
          </div>

          <div class="row q-col-gutter-md q-mb-sm">
            <q-select
              :hide-dropdown-icon="
                newItem.type != null && newItem.type.id != undefined
              "
              outlined
              v-model="newItem.type"
              use-input
              hide-selected
              fill-input
              autocomplete="false"
              label="Тип"
              input-debounce="400"
              :options="typeStore.simpleItems"
              option-label="name"
              @filter="typeFilter"
              :loading="typeStore.data.isItemsLoading"
              class="col-6"
              :rules="[
                () =>
                  (newItem.type != null && newItem.type.id != undefined) ||
                  'Оберіть тип',
              ]"
            >
              <template
                v-if="newItem.type && !typeStore.data.isItemsLoading"
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="newItem.type = null"
                  class="cursor-pointer"
                />
              </template>
            </q-select>
            <q-select
              :hide-dropdown-icon="
                newItem.unit != null && newItem.unit.id != undefined
              "
              outlined
              v-model="newItem.unit"
              use-input
              hide-selected
              fill-input
              autocomplete="false"
              label="Одиниця виміру"
              input-debounce="400"
              :options="unitStore.simpleItems"
              option-label="name"
              @filter="unitFilter"
              :loading="unitStore.data.isItemsLoading"
              class="col-6"
              :rules="[
                () =>
                  (newItem.unit != null && newItem.unit.id != undefined) ||
                  'Оберіть одиницю виміру',
              ]"
            >
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps" class="flex items-center">
                  {{ scope.opt.name }} ({{ scope.opt.description }})
                </q-item>
              </template>

              <template
                v-if="newItem.unit && !unitStore.data.isItemsLoading"
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="newItem.unit = null"
                  class="cursor-pointer"
                />
              </template>
            </q-select>
          </div>

          <div class="row q-col-gutter-md q-mb-lg">
            <q-select
              :hide-dropdown-icon="newItem.color != null"
              outlined
              v-model="newItem.color"
              use-input
              hide-selected
              fill-input
              autocomplete="false"
              label="Колір"
              option-label="description"
              input-debounce="400"
              :options="colorStore.simpleItems"
              @filter="colorFilter"
              :loading="colorStore.data.isItemsLoading"
              class="col-4"
            >
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps" class="flex items-center">
                  <div
                    class="color q-mr-sm"
                    :style="{ backgroundColor: scope.opt.value }"
                  ></div>
                  <span>{{ scope.opt.value }}</span>
                  <q-tooltip
                    class="bg-black text-body2"
                    anchor="center right"
                    self="center left"
                    :offset="[0, 0]"
                  >
                    {{ scope.opt.description }}
                  </q-tooltip>
                </q-item>
              </template>

              <template
                v-if="newItem.color && !colorStore.data.isItemsLoading"
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="newItem.color = null"
                  class="cursor-pointer"
                />
              </template>
            </q-select>
            <q-select
              :hide-dropdown-icon="newItem.size != null"
              outlined
              v-model="newItem.size"
              use-input
              hide-selected
              fill-input
              autocomplete="false"
              label="Розмір"
              input-debounce="400"
              :options="sizeStore.simpleItems"
              option-label="value"
              @filter="sizeFilter"
              :loading="sizeStore.data.isItemsLoading"
              class="col-4"
            >
              <template
                v-if="newItem.size && !sizeStore.data.isItemsLoading"
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="newItem.size = null"
                  class="cursor-pointer"
                />
              </template>
            </q-select>
            <q-select
              :hide-dropdown-icon="newItem.gender != null"
              outlined
              v-model="newItem.gender"
              use-input
              hide-selected
              fill-input
              autocomplete="false"
              label="Гендер"
              input-debounce="400"
              :options="genderStore.simpleItems"
              option-label="name"
              @filter="genderFilter"
              :loading="genderStore.data.isItemsLoading"
              class="col-4"
            >
              <template
                v-if="newItem.gender && !genderStore.data.isItemsLoading"
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="newItem.gender = null"
                  class="cursor-pointer"
                />
              </template>
            </q-select>
          </div>
          <q-separator class="q-mb-sm" />
          <div class="row q-mb-sm text-h6">
            <div class="col-4"></div>
            <div class="col-4 flex justify-center items-center">Наявність:</div>
            <div class="col-4">
              <q-btn class="q-mr-sm" round flat icon="add">
                <q-tooltip
                  class="bg-black text-body2"
                  anchor="bottom middle"
                  self="top middle"
                  :offset="[0, 5]"
                >
                  Додати склад
                </q-tooltip>
              </q-btn>
              <q-btn class="q-mr-sm" round flat icon="delete">
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
            v-model:item="newItem.warehouses[0]"
            :warehouse-store="warehouseStore"
          ></WarehouseFormComponent>
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
import { useCountryStore } from "src/stores/helpers/countryStore";
import { useCityStore } from "src/stores/helpers/cityStore";
import { useItemStore } from "src/stores/itemStore";
import { useTypeStore } from "src/stores/typeStore";
import { useSizeStore } from "src/stores/sizeStore";
import { useGenderStore } from "src/stores/genderStore";
import { useColorStore } from "src/stores/colorStore";
import { useWarehouseStore } from "src/stores/warehouseStore";
import { useUnitStore } from "src/stores/unitStore";
import WarehouseFormComponent from "src/components/item/createOne/WarehouseFormComponent.vue";

const sectionStore = useItemStore();
const countryStore = useCountryStore();
const cityStore = useCityStore();
const typeStore = useTypeStore();
const sizeStore = useSizeStore();
const genderStore = useGenderStore();
const colorStore = useColorStore();
const warehouseStore = useWarehouseStore();
const unitStore = useUnitStore();

let newItem = reactive({
  article: "",
  title: "",
  price: "",
  currency: "UAH",
  type: null,
  gender: null,
  size: null,
  color: null,
  unit: null,
  warehouses: [
    {
      country: null,
      city: null,
      warehouse: null,
    },
  ],
});

function showCreateDialog() {
  newItem.article = "";
  newItem.title = "";
  newItem.price = "";
  newItem.currency = "UAH";
  newItem.type = null;
  newItem.gender = null;
  newItem.size = null;
  newItem.color = null;
  newItem.unit = null;
  warehouses = [];
}

function typeFilter(val, update, abort) {
  update(() => {
    typeStore.data.isItemsLoading = true;
    typeStore.simpleItems = [];
    typeStore.simpleReceive(val);
  });
}

function unitFilter(val, update, abort) {
  update(() => {
    unitStore.data.isItemsLoading = true;
    unitStore.simpleItems = [];
    unitStore.simpleReceive(val);
  });
}

function colorFilter(val, update, abort) {
  update(() => {
    colorStore.data.isItemsLoading = true;
    colorStore.simpleItems = [];
    colorStore.simpleReceive(val);
  });
}

function sizeFilter(val, update, abort) {
  update(() => {
    sizeStore.data.isItemsLoading = true;
    sizeStore.simpleItems = [];
    sizeStore.simpleReceive(val);
  });
}

function genderFilter(val, update, abort) {
  update(() => {
    genderStore.data.isItemsLoading = true;
    genderStore.simpleItems = [];
    genderStore.simpleReceive(val);
  });
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
    cityStore.receive(newItem.warehouses[0].country.id, val);
  });
}

function warehouseFilter(val, update, abort) {
  update(() => {
    warehouseStore.data.isItemsLoading = true;
    warehouseStore.items = [];
    warehouseStore.simpleReceive(newItem.warehouses[0].city.id, val);
  });
}
</script>
<style scoped>
.color {
  width: 30px;
  height: 30px;
  border-radius: 5px;
  border: 1 px solid black;
}
</style>
