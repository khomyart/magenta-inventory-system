<template>
  <q-dialog v-model="sectionStore.dialogs.createMultiple.isShown">
    <q-card style="width: 95vw; max-width: 600px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="apps" color="black" size="md" class="q-mr-sm" />
          Група предметів, г: {{ selectedIndexes.genders }}, к:
          {{ selectedIndexes.colors }}, р: {{ selectedIndexes.sizes }}
          <q-btn class="q-ml-md" @click="fillNewMultipleItemObjectWithItems"
            >Заповнити</q-btn
          >
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit.prevent="submit">
        <q-card-section
          style="max-height: 75vh; height: 1000px"
          class="scroll col-12 q-pt-md"
        >
          <div class="row q-gutter-sm q-mb-md justify-start">
            <q-checkbox v-model="isUsed.genders" label="Гендер" color="teal" />
            <q-checkbox v-model="isUsed.colors" label="Колір" color="red" />
            <q-checkbox v-model="isUsed.sizes" label="Розмір" color="orange" />
          </div>
          <q-separator class="q-mb-lg"></q-separator>
          <div class="row q-col-gutter-md q-mb-sm q-pt-sm">
            <q-input
              class="col-12 q-pt-sm"
              outlined
              v-model="sectionStore.newMultipleItems.main.groupID"
              :debounce="700"
              label="ID групи"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть ID групи',
                (val) => val.length <= 36 || 'Не більше 36 символів',
              ]"
            >
              <template v-slot:append>
                <q-icon
                  name="cached"
                  class="cursor-pointer q-ml-xs"
                  @click.stop.prevent="generateGroupID"
                >
                  <q-tooltip
                    class="bg-black text-body2"
                    :offset="[0, 7]"
                    anchor="bottom middle"
                    self="top middle"
                    >Згенерувати ID групи</q-tooltip
                  >
                </q-icon>
              </template>
            </q-input>
            <q-input
              v-if="isUsed.genders === false"
              class="col-5 q-pt-sm"
              outlined
              v-model="sectionStore.newMultipleItems.main.detail.article"
              label="Артикль"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть артикль',
                (val) => val.length <= 10 || 'Не більше 10 символів',
              ]"
            />
            <q-input
              class="col-7 q-pt-sm"
              :class="{
                'col-12': isUsed.genders === true,
              }"
              outlined
              v-model="sectionStore.newMultipleItems.main.detail.title"
              label="Назва"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть назву',
                (val) => val.length <= 255 || 'Не більше 255 символів',
              ]"
            />
            <q-select
              :hide-dropdown-icon="
                sectionStore.newMultipleItems.main.type != null &&
                sectionStore.newMultipleItems.main.type.id != undefined
              "
              outlined
              v-model="sectionStore.newMultipleItems.main.type"
              use-input
              hide-selected
              fill-input
              autocomplete="false"
              label="Вид"
              input-debounce="400"
              :options="typeStore.simpleItems"
              option-label="name"
              @filter="typeFilter"
              :loading="typeStore.data.isItemsLoading"
              class="col-6 q-pt-sm"
              :rules="[
                () =>
                  (sectionStore.newMultipleItems.main.type != null &&
                    sectionStore.newMultipleItems.main.type.id != undefined) ||
                  'Оберіть вид',
              ]"
            >
              <template
                v-if="
                  sectionStore.newMultipleItems.main.type &&
                  !typeStore.data.isItemsLoading
                "
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="
                    sectionStore.newMultipleItems.main.type = null
                  "
                  class="cursor-pointer"
                />
              </template>
            </q-select>
            <q-input
              class="col-6 q-pt-sm"
              outlined
              v-model="sectionStore.newMultipleItems.main.detail.model"
              label="Модель"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть модель',
                (val) => val.length <= 255 || 'Не більше 255 символів',
              ]"
            />
            <q-input
              class="col-4 q-pt-sm"
              outlined
              label="Ціна"
              type="number"
              v-model="sectionStore.newMultipleItems.main.detail.price"
              :rules="[
                (val) => (val !== null && val !== '') || 'Вкажіть ціну',
                (val) => val.length <= 13 || 'Не більше 13 символів',
                (val) => val >= 1 || 'Не менше 1',
              ]"
            />
            <q-select
              hide-dropdown-icon
              outlined
              label="Валюта"
              v-model="sectionStore.newMultipleItems.main.detail.currency"
              :options="['UAH', 'USD', 'EUR']"
              class="col-4 q-pt-sm"
            />
            <q-input
              class="col-4 q-pt-sm"
              outlined
              v-model="sectionStore.newMultipleItems.main.detail.lack"
              label="Нестача"
              type="number"
              :rules="[
                (val) => (val !== null && val !== '') || 'Вкажіть нестачу',
                (val) => val >= 1 || 'Не менше одиниці',
              ]"
            />
          </div>
          <div class="q-mt-sm" v-if="isUsed.genders === true">
            <q-separator class="q-mb-md" />
            <div
              class="text-h6 q-mb-sm q-mb-sm-sm text-weight-medium text-left q-pl-md"
            >
              Гендери
            </div>
            <div class="row q-col-gutter-md">
              <q-select
                autocomplete="false"
                outlined
                use-input
                hide-selected
                v-model="templHolders.gender"
                label="Введіть назву гендеру"
                input-debounce="400"
                :options="genderStore.simpleItems"
                @filter="genderFilter"
                @update:model-value="addSelectedGenderToStore"
                :loading="genderStore.data.isItemsLoading"
                hide-dropdown-icon
                class="col-12 q-pb-sm"
                :rules="[
                  () =>
                    sectionStore.newMultipleItems.genders.length >= 1 ||
                    'Оберіть хоча б один гендер',
                ]"
              >
                <template
                  v-slot:append
                  v-if="genderStore.data.isItemsLoading === false"
                >
                  <q-avatar>
                    <q-icon size="23px" name="search"></q-icon>
                  </q-avatar>
                </template>

                <template v-slot:option="scope">
                  <q-item v-bind="scope.itemProps">
                    <div
                      class="list-item-body"
                      :class="{
                        'active-item-component': isGenderExistInList(
                          scope.opt.id
                        ),
                      }"
                    >
                      {{ scope.opt.name }}
                    </div>
                  </q-item>
                </template>
              </q-select>
            </div>
          </div>
          <div
            class="row col-12"
            v-if="sectionStore.newMultipleItems.genders.length > 0"
          >
            <div
              id="genders_container"
              class="col-12 items-wrapper q-px-md q-pt-md q-pb-md q-mb-sm q-mt-sm q-mt-sm-sm"
            >
              <div class="q-gutter-md row">
                <template
                  v-for="(item, itemIndex) in sectionStore.newMultipleItems
                    .genders"
                  :key="item.id"
                >
                  <div
                    :class="{
                      'selected-item': selectedIndexes.genders === itemIndex,
                    }"
                    @click="selectItem(itemIndex, 'gender')"
                    class="item-chip q-py-xs q-pl-md q-pr-sm row d-flex items-center"
                  >
                    <div class="item-chip-article q-mr-sm">
                      {{ item.name }}
                    </div>
                    <q-btn
                      round
                      color="grey"
                      flat
                      size="8px"
                      icon="close"
                      @click.stop="removeItem(itemIndex, 'gender')"
                    />
                  </div>
                </template>
              </div>
              <q-separator class="q-mt-md" />
              <SelectedGenderFormComponent
                :genderArrayIndex="selectedIndexes.genders"
              />
            </div>
          </div>
          <div id="bottom_of_genders_container"></div>
          <CreateColorComponent
            v-if="
              (isUsed.colors === true && selectedIndexes.genders != -1) ||
              (isUsed.colors === true && isUsed.genders === false)
            "
            :genderArrayIndex="selectedIndexes.genders"
            :selectedColorIndex="selectedIndexes.colors"
            @selectColor="selectItem"
            @removeColor="removeItem"
          />
          <CreateSizeComponent
            v-if="
              isUsed.sizes === true &&
              ((isUsed.genders === false && isUsed.colors === false) ||
                (isUsed.genders === true &&
                  isUsed.colors === false &&
                  selectedIndexes.genders != -1) ||
                (isUsed.genders === false &&
                  isUsed.colors === true &&
                  selectedIndexes.colors != -1) ||
                (isUsed.genders === true &&
                  isUsed.colors === true &&
                  selectedIndexes.colors != -1))
            "
            :colorArrayIndex="selectedIndexes.colors"
            :genderArrayIndex="selectedIndexes.genders"
            :selectedSizeIndex="selectedIndexes.sizes"
            @selectSize="selectItem"
            @removeSize="removeItem"
          />
        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn
            flat
            color="primary"
            type="submit"
            :loading="sectionStore.dialogs.createMultiple.isLoading"
            ><b>Створити</b></q-btn
          >
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
<script setup>
import { v4 as uuidv4 } from "uuid";
import { reactive } from "vue";
import { useCountryStore } from "src/stores/helpers/countryStore";
import { useCityStore } from "src/stores/helpers/cityStore";
import { useItemStore } from "src/stores/itemStore";
import { useTypeStore } from "src/stores/typeStore";
import { useSizeStore } from "src/stores/sizeStore";
import { useGenderStore } from "src/stores/genderStore";
import { useColorStore } from "src/stores/colorStore";
import { useWarehouseStore } from "src/stores/warehouseStore";
import { useUnitStore } from "src/stores/unitStore";
import SelectedGenderFormComponent from "src/components/item/createMultiple/SelectedGenderFormComponent.vue";
import CreateColorComponent from "./CreateColorComponent.vue";
import CreateSizeComponent from "./CreateSizeComponent.vue";

const sectionStore = useItemStore();
const countryStore = useCountryStore();
const cityStore = useCityStore();
const typeStore = useTypeStore();
const sizeStore = useSizeStore();
const genderStore = useGenderStore();
const colorStore = useColorStore();
const warehouseStore = useWarehouseStore();
const unitStore = useUnitStore();

const isUsed = reactive({
  genders: true,
  colors: true,
  sizes: true,
});

let templHolders = reactive({
  gender: {},
  color: {},
  sizes: {},
});

let selectedIndexes = reactive({
  genders: -1,
  colors: -1,
  sizes: -1,
});

function submit() {
  console.log("submited");
}

function generateGroupID() {
  sectionStore.newMultipleItems.main.groupID = uuidv4();
}

function typeFilter(val, update, abort) {
  update(() => {
    typeStore.data.isItemsLoading = true;
    typeStore.simpleItems = [];
    typeStore.simpleReceive(val);
  });
}

/**
 *  Genders functions
 */
function genderFilter(val, update, abort) {
  update(() => {
    genderStore.data.isItemsLoading = true;
    genderStore.simpleItems = [];
    genderStore.simpleReceive(val);
  });
}

function addSelectedGenderToStore(val) {
  let isValueExist = isGenderExistInList(val.id);
  let bottomOfGendersContainer = document.getElementById(
    "bottom_of_genders_container"
  );

  if (!isValueExist) {
    let newGenderIndex = sectionStore.newMultipleItems.genders.length;

    let newGenderTemplate = { ...val };
    newGenderTemplate.detail = { ...sectionStore.newMultipleItems.main.detail };
    sectionStore.newMultipleItems.genders.push(newGenderTemplate);
    templHolders.gender = {};

    if (selectedIndexes.genders === -1) {
      selectItem(newGenderIndex, "gender");
      setTimeout(() => {
        bottomOfGendersContainer.scrollIntoView({
          behavior: "smooth",
          block: "end",
        });
      }, 100);
    }
  }
}

function isGenderExistInList(itemId) {
  let items = sectionStore.newMultipleItems.genders;

  return items.filter((item) => item.id === itemId).length > 0 ? true : false;
}

/**
 * General function
 */

function fillNewMultipleItemObjectWithItems() {
  let items = {
    main: {
      groupID: "",
      type: null,
      units: "",
      detail: {
        title: "",
        model: "",
        article: "",
        price: "",
        currency: "UAH",
        lack: 10,
      },
    },
    genders: [
      {
        id: 10,
        name: "щось4",
        number_in_row: 1,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
      },
      {
        id: 2,
        name: "4",
        number_in_row: 3,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
      },
      {
        id: 4,
        name: "діти",
        number_in_row: 5,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
      },
      {
        id: 5,
        name: "чоловіч",
        number_in_row: 6,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
      },
      {
        id: 3,
        name: "5",
        number_in_row: 4,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
      },
    ],
    colors: [
      {
        id: 6,
        value: "#14cc61",
        article: "GR",
        description: "Гріно",
        text_color_value: "#ffffff",
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 4 },
        indexInArray: 0,
      },
      {
        id: 7,
        value: "#f5e798",
        article: "CR",
        description: "Кремі",
        text_color_value: "#000000",
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 3 },
        indexInArray: 1,
      },
      {
        id: 3,
        value: "#2833fc",
        article: "Article2",
        description: "Каралоуий",
        text_color_value: "#000000",
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 2 },
        indexInArray: 2,
      },
      {
        id: 3,
        value: "#2833fc",
        article: "Article2",
        description: "Каралоуий",
        text_color_value: "#000000",
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 1 },
        indexInArray: 3,
      },
      {
        id: 7,
        value: "#f5e798",
        article: "CR",
        description: "Кремі",
        text_color_value: "#000000",
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 1 },
        indexInArray: 4,
      },
      {
        id: 1,
        value: "#e61c1c",
        article: "WHI",
        description: "WRYYY",
        text_color_value: "#ffffff",
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 0 },
        indexInArray: 5,
      },
      {
        id: 5,
        value: "#ee00ff",
        article: "MA",
        description: "Маджентовий",
        text_color_value: "#ffffff",
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 4 },
        indexInArray: 6,
      },
      {
        id: 5,
        value: "#ee00ff",
        article: "MA",
        description: "Маджентовий",
        text_color_value: "#ffffff",
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 3 },
        indexInArray: 7,
      },
      {
        id: 1,
        value: "#e61c1c",
        article: "WHI",
        description: "WRYYY",
        text_color_value: "#ffffff",
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 2 },
        indexInArray: 8,
      },
      {
        id: 5,
        value: "#ee00ff",
        article: "MA",
        description: "Маджентовий",
        text_color_value: "#ffffff",
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 1 },
        indexInArray: 9,
      },
      {
        id: 6,
        value: "#14cc61",
        article: "GR",
        description: "Гріно",
        text_color_value: "#ffffff",
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 0 },
        indexInArray: 10,
      },
    ],
    sizes: [
      {
        id: 3,
        value: "S",
        description: "маленька",
        number_in_row: 3,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 4, colorArrayIndex: 6 },
        indexInArray: 0,
      },
      {
        id: 4,
        value: "M",
        description: "medium",
        number_in_row: 4,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 4, colorArrayIndex: 0 },
        indexInArray: 1,
      },
      {
        id: 4,
        value: "M",
        description: "medium",
        number_in_row: 4,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 3, colorArrayIndex: 7 },
        indexInArray: 2,
      },
      {
        id: 1,
        value: "3XXL",
        description: "опопис",
        number_in_row: 1,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 3, colorArrayIndex: 1 },
        indexInArray: 3,
      },
      {
        id: 3,
        value: "S",
        description: "маленька",
        number_in_row: 3,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 2, colorArrayIndex: 8 },
        indexInArray: 4,
      },
      {
        id: 4,
        value: "M",
        description: "medium",
        number_in_row: 4,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 2, colorArrayIndex: 2 },
        indexInArray: 5,
      },
      {
        id: 4,
        value: "M",
        description: "medium",
        number_in_row: 4,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 1, colorArrayIndex: 9 },
        indexInArray: 6,
      },
      {
        id: 1,
        value: "3XXL",
        description: "опопис",
        number_in_row: 1,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 1, colorArrayIndex: 4 },
        indexInArray: 7,
      },
      {
        id: 3,
        value: "S",
        description: "маленька",
        number_in_row: 3,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 1, colorArrayIndex: 3 },
        indexInArray: 8,
      },
      {
        id: 3,
        value: "S",
        description: "маленька",
        number_in_row: 3,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 0, colorArrayIndex: 10 },
        indexInArray: 9,
      },
      {
        id: 1,
        value: "3XXL",
        description: "опопис",
        number_in_row: 1,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 0, colorArrayIndex: 5 },
        indexInArray: 10,
      },
      {
        id: 2,
        value: "X",
        description: "мяв1",
        number_in_row: 2,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 4, colorArrayIndex: 6 },
        indexInArray: 11,
      },
      {
        id: 2,
        value: "X",
        description: "мяв1",
        number_in_row: 2,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 4, colorArrayIndex: 0 },
        indexInArray: 12,
      },
      {
        id: 1,
        value: "3XXL",
        description: "опопис",
        number_in_row: 1,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 3, colorArrayIndex: 7 },
        indexInArray: 13,
      },
      {
        id: 5,
        value: "L",
        description: "large",
        number_in_row: 5,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 3, colorArrayIndex: 1 },
        indexInArray: 14,
      },
      {
        id: 5,
        value: "L",
        description: "large",
        number_in_row: 5,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 2, colorArrayIndex: 8 },
        indexInArray: 15,
      },
      {
        id: 3,
        value: "S",
        description: "маленька",
        number_in_row: 3,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 2, colorArrayIndex: 2 },
        indexInArray: 16,
      },
      {
        id: 2,
        value: "X",
        description: "мяв1",
        number_in_row: 2,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 1, colorArrayIndex: 9 },
        indexInArray: 17,
      },
      {
        id: 3,
        value: "S",
        description: "маленька",
        number_in_row: 3,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 1, colorArrayIndex: 4 },
        indexInArray: 18,
      },
      {
        id: 5,
        value: "L",
        description: "large",
        number_in_row: 5,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 1, colorArrayIndex: 3 },
        indexInArray: 19,
      },
      {
        id: 4,
        value: "M",
        description: "medium",
        number_in_row: 4,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 0, colorArrayIndex: 10 },
        indexInArray: 20,
      },
      {
        id: 2,
        value: "X",
        description: "мяв1",
        number_in_row: 2,
        detail: {
          title: "",
          model: "",
          article: "",
          price: "",
          currency: "UAH",
          lack: 10,
        },
        connections: { genderArrayIndex: 0, colorArrayIndex: 5 },
        indexInArray: 21,
      },
    ],
  };
  sectionStore.newMultipleItems = {};
  sectionStore.newMultipleItems = items;
  selectItem(0, "gender");
}

function selectItem(itemIndex, itemType) {
  selectedIndexes[`${itemType}s`] = itemIndex;
  console.log("selected:", itemType, itemIndex);
  //when selecting gender, chose first color of it
  if (itemType === "gender") {
    let genderColors = sectionStore.newMultipleItems.colors.filter(
      (color) => color.connections.genderArrayIndex === selectedIndexes.genders
    );
    let firstColorItemIndexInGender =
      genderColors.length > 0 ? genderColors[0].indexInArray : -1;

    selectedIndexes.colors = firstColorItemIndexInGender;

    let genderSizes = sectionStore.newMultipleItems.sizes.filter(
      (size) =>
        size.connections.genderArrayIndex === selectedIndexes.genders &&
        size.connections.colorArrayIndex === selectedIndexes.colors
    );
    let firstSizeItemIndexInGender =
      genderSizes.length > 0 ? genderSizes[0].indexInArray : -1;

    selectedIndexes.sizes = firstSizeItemIndexInGender;
  }

  //when selecting color, chose first size of it
  if (itemType === "color") {
    let colorSizes = sectionStore.newMultipleItems.sizes.filter(
      (size) =>
        size.connections.genderArrayIndex === selectedIndexes.genders &&
        size.connections.colorArrayIndex === selectedIndexes.colors
    );
    let firstSizeItemIndexInColor =
      colorSizes.length > 0 ? colorSizes[0].indexInArray : -1;

    selectedIndexes.sizes = firstSizeItemIndexInColor;
  }
}

function recalculateColorsArrayIndexes() {
  sectionStore.newMultipleItems.colors =
    sectionStore.newMultipleItems.colors.map((color, index) => {
      color.indexInArray = index;
      return color;
    });
}

function recalculateSizesArrayIndexes() {
  sectionStore.newMultipleItems.sizes = sectionStore.newMultipleItems.sizes.map(
    (size, index) => {
      size.indexInArray = index;
      return size;
    }
  );
}

/**
 * Colors are connected to genders
 * So when we deleting gender, should recalculate this connection too
 */
function recalculateColorsConnectionIndexes(deletedGenderIndex) {
  sectionStore.newMultipleItems.colors =
    sectionStore.newMultipleItems.colors.map((color, index) => {
      if (color.connections.genderArrayIndex > deletedGenderIndex) {
        color.connections.genderArrayIndex -= 1;
      }

      return color;
    });
}

/**
 * Sizes are connected to genders and colors
 * So when we deleting colors or gender, should recalculate this connection too
 */
function recalculateSizesConnectionIndexes(
  deletedGenderIndex = null,
  deletingColorIndex = null
) {
  sectionStore.newMultipleItems.sizes = sectionStore.newMultipleItems.sizes.map(
    (size, index) => {
      if (
        deletedGenderIndex != null &&
        size.connections.genderArrayIndex > deletedGenderIndex
      ) {
        size.connections.genderArrayIndex -= 1;
      }
      if (
        deletingColorIndex != null &&
        size.connections.colorArrayIndex > deletingColorIndex
      ) {
        size.connections.colorArrayIndex -= 1;
      }

      return size;
    }
  );
}

function removeItem(itemIndex, type) {
  let itemInfo = removingItemInfo(itemIndex, type);

  //if removing gender, remove all colors and sizes which are belong to it
  if (type === "gender") {
    // itemsAmount = sectionStore.newMultipleItems.genders.length;

    //Dependent colors removing
    let genderColors = sectionStore.newMultipleItems.colors.filter(
      (color) => color.connections.genderArrayIndex === itemIndex
    );
    let colorsIndexes = genderColors.map((color) => {
      return color.indexInArray;
    });

    colorsIndexes.reverse().forEach((colorIndex) => {
      sectionStore.newMultipleItems.colors.splice(colorIndex, 1);
    });
    recalculateColorsArrayIndexes();
    recalculateColorsConnectionIndexes(itemIndex);

    //Dependent sizes removing
    let genderSizes = sectionStore.newMultipleItems.sizes.filter(
      (size) => size.connections.genderArrayIndex === itemIndex
    );
    let sizesIndexes = genderSizes.map((size) => {
      return size.indexInArray;
    });

    sizesIndexes.reverse().forEach((sizeIndex) => {
      sectionStore.newMultipleItems.sizes.splice(sizeIndex, 1);
    });

    recalculateSizesArrayIndexes();
    recalculateSizesConnectionIndexes(itemIndex, null);
    if (colorsIndexes.length > 0) {
      colorsIndexes.forEach((colorIndex) => {
        recalculateSizesConnectionIndexes(null, colorIndex);
      });
    }
  }

  if (type === "color") {
    let colorSizes = sectionStore.newMultipleItems.sizes.filter(
      (size) => size.connections.colorArrayIndex === itemIndex
    );
    let sizesIndexes = colorSizes.map((size) => {
      return size.indexInArray;
    });

    sizesIndexes.reverse().forEach((sizeIndex) => {
      sectionStore.newMultipleItems.sizes.splice(sizeIndex, 1);
    });

    recalculateSizesArrayIndexes();
    recalculateSizesConnectionIndexes(null, itemIndex);
  }

  sectionStore.newMultipleItems[`${type}s`].splice(itemIndex, 1);

  recalculateColorsArrayIndexes();
  recalculateSizesArrayIndexes();

  //deleted item index is more then selected
  if (itemIndex > selectedIndexes[`${type}s`]) {
    selectItem(selectedIndexes[`${type}s`], type);
    return;
  }
  //deleted item index is less then selected
  if (itemIndex < selectedIndexes[`${type}s`]) {
    selectItem(selectedIndexes[`${type}s`] - 1, type);
    return;
  }
  //same item
  if (itemIndex == selectedIndexes[`${type}s`]) {
    //last item in array
    if (itemInfo.amountOfContextedItems > 1) {
      if (itemInfo.isSelectedLast && itemInfo.isRemovingLast) {
        selectItem(itemInfo.previousIndex, type);
        return;
      }
      if (itemInfo.isSelectedFirst && itemInfo.isRemovingFirst) {
        selectItem(itemInfo.nextIndex - 1, type);
        return;
      }
    } else {
      if (itemInfo.isSelectedFirst && itemInfo.isRemovingFirst) {
        selectItem(-1, type);
        return;
      }
    }
    selectItem(itemInfo.nextIndex - 1, type);
    return;
  }
}

/**
 * Returns indexes of items, depends on it context:
 * colors or sizes
 */
function removingItemInfo(currentIndex, type) {
  let previousIndex = null;
  let nextIndex = null;
  let amountOfItems = null;
  let indexesOfItemsWithCurrentContext = [];

  if (type === "gender") {
    previousIndex = currentIndex - 1;
    nextIndex = currentIndex + 1;
    amountOfItems = sectionStore.newMultipleItems.genders.length;

    let genders = sectionStore.newMultipleItems.genders;
    genders.forEach((genders, index) => {
      indexesOfItemsWithCurrentContext.push(index);
    });
  }

  if (type === "color") {
    let colors = sectionStore.newMultipleItems.colors;
    let genderArrayIndexContext =
      colors[currentIndex].connections.genderArrayIndex;

    colors.forEach((color, index) => {
      if (genderArrayIndexContext == color.connections.genderArrayIndex)
        indexesOfItemsWithCurrentContext.push(index);
    });

    let positionOfDeletedItemInsideContextedColorsArray =
      indexesOfItemsWithCurrentContext.indexOf(currentIndex);

    previousIndex =
      positionOfDeletedItemInsideContextedColorsArray != 0
        ? indexesOfItemsWithCurrentContext[
            positionOfDeletedItemInsideContextedColorsArray - 1
          ]
        : -1;

    nextIndex =
      positionOfDeletedItemInsideContextedColorsArray + 1 ==
        indexesOfItemsWithCurrentContext.length &&
      indexesOfItemsWithCurrentContext.length != 1
        ? null
        : indexesOfItemsWithCurrentContext[
            positionOfDeletedItemInsideContextedColorsArray + 1
          ];

    amountOfItems = indexesOfItemsWithCurrentContext.length;
  }

  if (type === "size") {
    let sizes = sectionStore.newMultipleItems.sizes;
    let genderArrayIndexContext =
      sizes[currentIndex].connections.genderArrayIndex;
    let colorArrayIndexContext =
      sizes[currentIndex].connections.colorArrayIndex;

    sizes.forEach((size, index) => {
      if (
        genderArrayIndexContext == size.connections.genderArrayIndex &&
        colorArrayIndexContext == size.connections.colorArrayIndex
      )
        indexesOfItemsWithCurrentContext.push(index);
    });

    let positionOfDeletedItemInsideContextedSizesArray =
      indexesOfItemsWithCurrentContext.indexOf(currentIndex);

    previousIndex =
      positionOfDeletedItemInsideContextedSizesArray != 0
        ? indexesOfItemsWithCurrentContext[
            positionOfDeletedItemInsideContextedSizesArray - 1
          ]
        : -1;

    nextIndex =
      positionOfDeletedItemInsideContextedSizesArray + 1 ==
        indexesOfItemsWithCurrentContext.length &&
      indexesOfItemsWithCurrentContext.length != 1
        ? null
        : indexesOfItemsWithCurrentContext[
            positionOfDeletedItemInsideContextedSizesArray + 1
          ];

    amountOfItems = indexesOfItemsWithCurrentContext.length;
  }

  return {
    previousIndex: previousIndex,
    nextIndex: nextIndex,
    isSelectedLast:
      selectedIndexes[`${type}s`] ==
      indexesOfItemsWithCurrentContext[
        indexesOfItemsWithCurrentContext.length - 1
      ],
    isRemovingLast:
      currentIndex ==
      indexesOfItemsWithCurrentContext[
        indexesOfItemsWithCurrentContext.length - 1
      ],
    isSelectedFirst:
      selectedIndexes[`${type}s`] == indexesOfItemsWithCurrentContext[0],
    isRemovingFirst: currentIndex == indexesOfItemsWithCurrentContext[0],
    amountOfContextedItems: amountOfItems,
  };
}
</script>
<style scoped>
.active-item-component {
  color: #a32cc7;
}
.list-item-body {
  display: flex;
  align-items: center;
}
.items-wrapper {
  border: 1px solid rgba(96, 0, 92, 0.18);
  border-radius: 4px;
}
.item-chip {
  width: fit-content;
  height: fit-content;
  border-radius: 20px;
  border: 1px solid rgba(96, 0, 92, 0.18);
  cursor: pointer;
  transition: all 0.1s ease-in-out;
}
.selected-item {
  border: 1px solid #a32cc7;
  background-color: #a32cc709;
}
</style>
