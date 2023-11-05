<template>
  <q-dialog v-model="sectionStore.dialogs.createMultiple.isShown" persistent>
    <q-card style="width: 95vw; max-width: 800px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="apps" color="black" size="md" class="q-mr-sm" />
          Група предметів
          <!-- <q-btn class="q-mx-md" @click="fillSectionStoreWithTemplate()"
            >fill</q-btn -->
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
                (val) => val.length <= 100 || 'Не більше 100 символів',
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

            <div class="col-12 q-pt-sm q-mb-md q-wysiwyg">
              <q-editor
                ref="editorRef"
                @paste="onPaste"
                :toolbar="[
                  [
                    {
                      label: 'Вирівнювання',
                      icon: $q.iconSet.editor.align,
                      fixedLabel: true,
                      list: 'only-icons',
                      options: ['left', 'center', 'right', 'justify'],
                    },
                  ],
                  [
                    {
                      label: 'Текст',
                      icon: $q.iconSet.editor.bold,
                      fixedLabel: true,
                      list: 'only-icons',
                      options: [
                        'bold',
                        'italic',
                        'strike',
                        'underline',
                        'subscript',
                        'superscript',
                      ],
                    },
                  ],
                  [
                    {
                      label: 'Список',
                      icon: $q.iconSet.editor.orderedList,
                      fixedLabel: true,
                      list: 'only-icons',
                      options: ['unordered', 'ordered'],
                    },
                  ],
                  ['removeFormat', 'viewsource'],
                ]"
                outlined
                v-model="sectionStore.newMultipleItems.main.detail.description"
                placeholder="Опис"
              />
            </div>

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

            <q-select
              :hide-dropdown-icon="
                sectionStore.newMultipleItems.main.unit != null &&
                sectionStore.newMultipleItems.main.unit.id != undefined
              "
              outlined
              v-model="sectionStore.newMultipleItems.main.unit"
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
              class="col-6 q-pt-sm"
              :rules="[
                () =>
                  (sectionStore.newMultipleItems.main.unit != null &&
                    sectionStore.newMultipleItems.main.unit.id != undefined) ||
                  'Оберіть одиницю виміру',
              ]"
            >
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps" class="flex items-center">
                  {{ scope.opt.name }} ({{ scope.opt.description }})
                </q-item>
              </template>

              <template
                v-if="
                  sectionStore.newMultipleItems.main.unit &&
                  !unitStore.data.isItemsLoading
                "
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="
                    sectionStore.newMultipleItems.main.unit = null
                  "
                  class="cursor-pointer"
                />
              </template>
            </q-select>

            <q-input
              class="col-4 q-pt-sm"
              outlined
              label="Ціна"
              type="number"
              step="0.01"
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
            <div class="col-12 q-pt-xs">
              <AddImagesComponent type="main" :index="0" />
            </div>
          </div>
          <q-separator
            class="q-mb-md"
            v-if="isUsed.genders || isUsed.colors || isUsed.sizes"
          />

          <div class="q-mt-sm" v-if="isUsed.genders === true">
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
              class="col-12 items-wrapper q-px-md q-pt-md q-mb-sm q-mt-sm q-mt-sm-sm"
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
              <q-separator class="q-mt-md q-mb-sm" />
              <q-expansion-item
                default-opened
                dense-toggle
                class="q-mb-sm"
                :label="'Форма для гендеру'"
                :header-style="{
                  borderRadius: '5px',
                }"
              >
                <SelectedGenderFormComponent
                  v-if="selectedIndexes.genders != -1"
                  :genderArrayIndex="selectedIndexes.genders"
                  :rules="genderFieldsRules"
                  :lastUsedCharacteristic="
                    usedCharacteristics[usedCharacteristics.length - 1]
                  "
                />
              </q-expansion-item>
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
            :lastUsedCharacteristic="
              usedCharacteristics[usedCharacteristics.length - 1]
            "
            :rules="colorFieldsRules"
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
            :lastUsedCharacteristic="
              usedCharacteristics[usedCharacteristics.length - 1]
            "
            :rules="sizeFieldsRules"
            @selectSize="selectItem"
            @removeSize="removeItem"
          />
          <AddAvailableInComponent
            :type="'main'"
            :index="0"
            v-if="usedCharacteristics.length === 0"
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
import { reactive, watch, ref, onMounted } from "vue";
import { useAppConfigStore } from "src/stores/appConfigStore";
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
import AddImagesComponent from "./AddImagesComponent.vue";
import AddAvailableInComponent from "./AddAvailableInComponent.vue";

const appStore = useAppConfigStore();
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
  genders: false,
  colors: false,
  sizes: false,
});

const newMultupleItemsDefaultState = {
  main: {
    groupID: "",
    type: null,
    unit: null,
    detail: {
      title: "",
      description: "",
      article: "",
      price: "",
      currency: "UAH",
      lack: 10,
      images: [],
      availableIn: [],
    },
  },
  genders: [],
  colors: [],
  sizes: [],
};

//array of string, looks like:
//['genders', 'colors', 'sizes']
let usedCharacteristics = ref([]);

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

/**
 * Rules
 */
let genderFieldsRules = {
  article: [
    (val) => (val !== null && val !== "") || "Введіть артикль",
    (val) => val.length <= 100 || "Артикль не більше 100 символів",
    (val) => {
      let articleMatches = sectionStore.newMultipleItems.genders.filter(
        (gender) => {
          return gender.detail.article === val;
        }
      );

      return articleMatches.length <= 1 || "Артиклі не можуть повторюватися";
    },
  ],
  description: [(val) => val.length <= 5000 || "Опис не більше 5000 символів"],
  title: [
    (val) => (val !== null && val !== "") || "Введіть назву",
    (val) => val.length <= 255 || "Назва не довша 255 символів",
  ],
  price: [
    (val) => (val !== null && val !== "") || "Вкажіть ціну",
    (val) => val.length <= 13 || "Ціна не довша 13 символів",
    (val) => val >= 1 || "Ціна не менше 1",
  ],
  lack: [
    (val) => (val !== null && val !== "") || "Вкажіть нестачу",
    (val) => val >= 1 || "Нестача не менше одиниці",
  ],
};

let colorFieldsRules = {
  description: [(val) => val.length <= 5000 || "Не більше 5000 символів"],
  title: [
    (val) => (val !== null && val !== "") || "Введіть назву",
    (val) => val.length <= 255 || "Не більше 255 символів",
  ],
  price: [
    (val) => (val !== null && val !== "") || "Вкажіть ціну",
    (val) => val.length <= 13 || "Не більше 13 символів",
    (val) => val >= 1 || "Ціна не менше 1",
  ],
  lack: [
    (val) => (val !== null && val !== "") || "Вкажіть нестачу",
    (val) => val >= 1 || "Нестача не менше одиниці",
  ],
};

let sizeFieldsRules = {
  description: [(val) => val.length <= 5000 || "Не більше 5000 символів"],
  title: [
    (val) => (val !== null && val !== "") || "Введіть назву",
    (val) => val.length <= 255 || "Не більше 255 символів",
  ],
  price: [
    (val) => (val !== null && val !== "") || "Вкажіть ціну",
    (val) => val.length <= 13 || "Не більше 13 символів",
    (val) => val >= 1 || "Ціна не менше 1",
  ],
  lack: [
    (val) => (val !== null && val !== "") || "Вкажіть нестачу",
    (val) => val >= 1 || "Нестача не менше одиниці",
  ],
};

let batchFieldsRules = {
  amount: [
    (val) => (val !== null && val !== "") || "Введіть кількість",
    (val) => val >= 1 || "Кількість не менше 1",
  ],
  price: [
    (val) => (val !== null && val !== "") || "Введіть ціну",
    (val) => val >= 1 || "Ціна не менше 1",
  ],
};

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

const editorRef = ref(null);
function onPaste(evt) {
  if (evt.target.nodeName === "INPUT") return;
  let text, onPasteStripFormattingIEPaste;
  evt.preventDefault();
  evt.stopPropagation();
  if (evt.originalEvent && evt.originalEvent.clipboardData.getData) {
    text = evt.originalEvent.clipboardData.getData("text/plain");
    editorRef.value.runCmd("insertText", text);
  } else if (evt.clipboardData && evt.clipboardData.getData) {
    text = evt.clipboardData.getData("text/plain");
    editorRef.value.runCmd("insertText", text);
  } else if (window.clipboardData && window.clipboardData.getData) {
    if (!onPasteStripFormattingIEPaste) {
      onPasteStripFormattingIEPaste = true;
      editorRef.value.runCmd("ms-pasteTextOnly", text);
    }
    onPasteStripFormattingIEPaste = false;
  }
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

/**
 * Clones object
 * @param {object} object object
 * @return {object} clone object
 */
function cloneObject(object) {
  let objectClone = {};
  objectClone = JSON.parse(JSON.stringify(object));
  return objectClone;
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

    newGenderTemplate.detail.images = [
      ...sectionStore.newMultipleItems.main.detail.images,
    ];
    newGenderTemplate.detail.availableIn = [
      ...sectionStore.newMultipleItems.main.detail.availableIn,
    ];
    sectionStore.newMultipleItems.genders.push(newGenderTemplate);
    console.log(newGenderTemplate);
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

/**
 * @param {string} type validation target type (gender, size or color)
 * @param {boolean} restricted if true - limit amount of rules for some types to minimum, for instance:
 * genders - only checking for article and amount of colors and sizes.
 * colors - only checking for amount of sizes
 * @return {array} list of items (with selected type), their indexes, amount of errors and errors info
 */
function validator(type, restricted = false) {
  let errorsCollector = [];

  if (type === "main") {
    let warehousesValidationResults = warehouseValidation(
      sectionStore.newMultipleItems.main.detail.availableIn
    );
    errorsCollector = [
      {
        amountOfErrors: warehousesValidationResults.amountOfErrors,
        errorsFor: {
          warehouses: warehousesValidationResults,
        },
      },
    ];
    return errorsCollector;
  }

  let items = sectionStore.newMultipleItems[`${type}s`];
  items.forEach((item, itemIndex) => {
    let fieldsValues = item.detail;
    let warehousesValidationInfo = warehouseValidation(
      fieldsValues.availableIn
    );
    let errorsFor = {
      description: {
        list: [],
        display: "",
      },
      title: {
        list: [],
        display: "",
      },
      price: {
        list: [],
        display: "",
      },
      lack: {
        list: [],
        display: "",
      },
      warehouses: warehousesValidationInfo,
    };

    if (restricted === true && (type === "gender" || type === "color")) {
      errorsFor = {};
    }

    errorsCollector.push({
      indexInArray: itemIndex,
      amountOfErrors: warehousesValidationInfo.amountOfErrors,
      errorsFor: { ...errorsFor },
    });

    let fieldsRules = [];
    switch (type) {
      case "gender":
        fieldsRules = genderFieldsRules;
        //if we are using sizes or colors and did not selected anything from it
        //count as error
        if (isUsed.colors === true) {
          let dependentColors = sectionStore.newMultipleItems.colors.filter(
            (color) => color.connections.genderArrayIndex === itemIndex
          );

          if (dependentColors.length === 0) {
            errorsCollector[itemIndex].errorsFor.colors = {
              list: [],
              display: "Оберіть хоча б один колір",
            };
            errorsCollector[itemIndex].amountOfErrors += 1;
          }
        }

        if (isUsed.sizes === true && isUsed.colors === false) {
          let dependentSizes = sectionStore.newMultipleItems.sizes.filter(
            (size) => size.connections.genderArrayIndex === itemIndex
          );

          if (dependentSizes.length === 0) {
            errorsCollector[itemIndex].errorsFor.sizes = {
              list: [],
              display: "Оберіть хоча б один розмір",
            };
            errorsCollector[itemIndex].amountOfErrors += 1;
          }
        }

        errorsCollector[itemIndex].errorsFor.article = {
          list: [],
          display: "",
        };

        break;
      case "color":
        fieldsRules = colorFieldsRules;
        errorsCollector[itemIndex].connections = item.connections;
        errorsCollector[itemIndex].value = item.value;
        //if we are using sizes and selected 0 sizes for particular color
        if (isUsed.sizes === true) {
          let dependentSizes = sectionStore.newMultipleItems.sizes.filter(
            (size) => size.connections.colorArrayIndex === item.indexInArray
          );

          if (dependentSizes.length === 0) {
            errorsCollector[itemIndex].errorsFor.sizes = {
              list: [],
              display: "Оберіть хоча б один розмір",
            };
            errorsCollector[itemIndex].amountOfErrors += 1;
          }
        }

        break;
      case "size":
        fieldsRules = sizeFieldsRules;
        errorsCollector[itemIndex].connections = item.connections;
        break;
    }

    Object.keys(fieldsValues).forEach((fieldName) => {
      if (
        fieldsRules[fieldName] != undefined &&
        errorsCollector[itemIndex].errorsFor[fieldName] != undefined
      ) {
        let listOfRulesForCurrentField = fieldsRules[fieldName];

        listOfRulesForCurrentField.forEach((rule) => {
          let validationResult = rule(fieldsValues[fieldName]);
          errorsCollector[itemIndex].errorsFor[fieldName].list.push(
            validationResult
          );
          if (
            typeof validationResult === "string" &&
            errorsCollector[itemIndex].errorsFor[fieldName].display === ""
          ) {
            errorsCollector[itemIndex].errorsFor[fieldName].display =
              validationResult;
            errorsCollector[itemIndex].amountOfErrors += 1;
          }
        });
      }
    });
  });

  return errorsCollector;
}

/**
 * Validating warehouses info
 * @param {array} itemAvailableIn item's availableIn, which are located -> item.detail.availableIn
 * @return {object} validated warehouses object and additional info with sturcture:
 * {
 *  amountOfErrors: number,
 *  warehouses: array of objects [
 *    {
 *      list: array of string [], //of errors
 *      display: string "", //first met error
 *      batches: array of objects [ //
 *         {
 *           list: array of string, [] //of errors
 *           display: string "", //first met error
 *         } ...
 *      ]
 *    } ...
 *  ]
 * }
 */
function warehouseValidation(itemAvailableIn) {
  //warehouses validation
  let validatedWarehouses = {
    amountOfErrors: 0,
    warehouses: [],
  };
  let warehouseTemplate = {
    list: [],
    display: "",
    batches: [],
  };
  let batchTemplate = {
    amount: {
      list: [],
      display: "",
    },
    price: {
      list: [],
      display: "",
    },
  };

  itemAvailableIn.forEach((availableIn, warehouseIndex) => {
    validatedWarehouses.warehouses.push(cloneObject(warehouseTemplate));

    if (availableIn.warehouse === null) {
      validatedWarehouses.warehouses[warehouseIndex].display = "Оберіть склад";
      validatedWarehouses.warehouses[warehouseIndex].list.push("Оберіть склад");
      validatedWarehouses.amountOfErrors += 1;
    }

    availableIn.batches.forEach((batch, batchIndex) => {
      validatedWarehouses.warehouses[warehouseIndex].batches.push(
        cloneObject(batchTemplate)
      );

      let fieldsRules = batchFieldsRules;
      //batches validation
      Object.keys(batch).forEach((fieldName) => {
        if (fieldsRules[fieldName] != undefined) {
          let listOfRulesForCurrentField = fieldsRules[fieldName];

          listOfRulesForCurrentField.forEach((rule) => {
            let validationResult = rule(batch[fieldName]);
            validatedWarehouses.warehouses[warehouseIndex].batches[batchIndex][
              fieldName
            ].list.push(validationResult);
            if (
              typeof validationResult === "string" &&
              validatedWarehouses.warehouses[warehouseIndex].batches[
                batchIndex
              ][fieldName].display === ""
            ) {
              validatedWarehouses.warehouses[warehouseIndex].batches[
                batchIndex
              ][fieldName].display = validationResult;
              validatedWarehouses.amountOfErrors += 1;
            }
          });
        }
      });
    });
  });

  return validatedWarehouses;
}

function submit() {
  let formFields = {
    main: validator("main"),
    genders: validator(
      "gender",
      isUsed.genders && (isUsed.colors || isUsed.sizes)
    ),
    colors: validator("color", isUsed.sizes),
    sizes: validator("size"),
  };
  let combinedFields = [
    ...formFields.main,
    ...formFields.genders,
    ...formFields.colors,
    ...formFields.sizes,
  ];
  let amountOfErrors = 0;

  combinedFields.forEach((field) => {
    if (field.amountOfErrors > 0) {
      amountOfErrors += 1;
    }
  });

  if (amountOfErrors > 0) {
    let errorMessage = generateErrorMessage(formFields);
    appStore.showErrorMessage(errorMessage, true);
    return;
  }

  let preparedItems = convertNewMultipleItemsToValidItemsArray(
    sectionStore.newMultipleItems
  );

  sectionStore.createMultiple(preparedItems);
}

/**
 *
 * @param {object} newMultipleItems object with usorted unforged inappropriate (for now) items structure
 * @returns {array} items
 */
function convertNewMultipleItemsToValidItemsArray(newMultipleItems) {
  let preparedItems = [];

  //selecting last selected type
  let type =
    usedCharacteristics.value.length > 0
      ? usedCharacteristics.value.slice(-1)[0]
      : "main";

  //creating copy of newMultipleItems for safety and ability to change it
  let items = Object.assign({}, newMultipleItems);
  items.main = [items.main];

  items[type].forEach((item, newItemIndex) => {
    let relatedGender = null;
    let relatedColor = null;
    let preparedItem = {};

    if (type === "genders") {
      preparedItem.gender_id = item.id;
    }

    if (type === "colors") {
      let genderIndex = item.connections.genderArrayIndex;
      relatedGender =
        genderIndex != -1 ? newMultipleItems.genders[genderIndex] : null;

      if (relatedGender != null) preparedItem.gender_id = relatedGender.id;

      preparedItem.color_id = item.id;
    }

    if (type === "sizes") {
      let genderIndex = item.connections.genderArrayIndex;
      let colorIndex = item.connections.colorArrayIndex;
      relatedGender =
        genderIndex != -1 ? newMultipleItems.genders[genderIndex] : null;
      relatedColor =
        colorIndex != -1 ? newMultipleItems.colors[colorIndex] : null;

      if (relatedGender != null) preparedItem.gender_id = relatedGender.id;
      if (relatedColor != null) preparedItem.color_id = relatedColor.id;

      preparedItem.size_id = item.id;
    }

    preparedItem.group_id = newMultipleItems.main.groupID;
    preparedItem.article =
      relatedGender != null
        ? relatedGender.detail.article
        : type === "genders"
        ? item.detail.article
        : newMultipleItems.main.detail.article;
    preparedItem.title = item.detail.title;
    preparedItem.description = item.detail.description;
    preparedItem.price = item.detail.price;
    preparedItem.currency = item.detail.currency;
    preparedItem.lack = item.detail.lack;
    preparedItem.type_id = newMultipleItems.main.type.id;
    preparedItem.unit_id = newMultipleItems.main.unit.id;

    //warehouses preparation
    item.detail.availableIn.forEach((availableIn, index) => {
      if (index == 0) preparedItem.warehouses = [];
      preparedItem.warehouses.push({
        id: availableIn.warehouse.id,
        batches: availableIn.batches,
      });
    });

    //images preparation
    item.detail.images.forEach((image, index) => {
      if (index == 0) preparedItem.images = [];
      preparedItem.images.push(image.file);
    });

    preparedItems.push(preparedItem);
  });

  return preparedItems;
}

/**
 *
 * @param {object} validatedFormFields object with structure:
 * {
 *  genders,
 *  colors,
 *  sizes
 * }
 */
function generateErrorMessage(validatedFormFields) {
  let mainAndItsErrors = [];
  let gendersAndTheirErrors = [];
  let colorsAndTheirErrors = [];
  let sizesAndTheirErrors = [];

  //getting genders errors
  validatedFormFields.genders.forEach((gender, genderIndex) => {
    gendersAndTheirErrors.push({
      name: "",
      errors: [],
      warehouses: gender.errorsFor.warehouses,
      amountOfErrors: gender.amountOfErrors,
    });
    gendersAndTheirErrors[genderIndex].name =
      sectionStore.newMultipleItems.genders[gender.indexInArray].name;
    Object.keys(gender.errorsFor).forEach((fieldName) => {
      if (
        gender.errorsFor[fieldName].display != "" &&
        fieldName != "warehouses"
      ) {
        gendersAndTheirErrors[genderIndex].errors.push(
          gender.errorsFor[fieldName].display
        );
      }
    });
  });

  //getting colors errors
  validatedFormFields.colors.forEach((color, colorIndex) => {
    colorsAndTheirErrors.push({
      name: "",
      value: color.value,
      connections: color.connections,
      errors: [],
      indexInArray: color.indexInArray,
      warehouses: color.errorsFor.warehouses,
      amountOfErrors: color.amountOfErrors,
    });
    colorsAndTheirErrors[colorIndex].name =
      sectionStore.newMultipleItems.colors[color.indexInArray].description;
    Object.keys(color.errorsFor).forEach((fieldName) => {
      if (
        color.errorsFor[fieldName].display != "" &&
        fieldName != "warehouses"
      ) {
        colorsAndTheirErrors[colorIndex].errors.push(
          color.errorsFor[fieldName].display
        );
      }
    });
  });

  //getting sizes errors
  validatedFormFields.sizes.forEach((size, sizeIndex) => {
    sizesAndTheirErrors.push({
      name: "",
      connections: size.connections,
      errors: [],
      indexInArray: size.indexInArray,
      warehouses: size.errorsFor.warehouses,
      amountOfErrors: size.amountOfErrors,
    });
    sizesAndTheirErrors[sizeIndex].name =
      sectionStore.newMultipleItems.sizes[size.indexInArray].value;
    Object.keys(size.errorsFor).forEach((fieldName) => {
      if (
        size.errorsFor[fieldName].display != "" &&
        fieldName != "warehouses"
      ) {
        sizesAndTheirErrors[sizeIndex].errors.push(
          size.errorsFor[fieldName].display
        );
      }
    });
  });

  let htmlErrorList = "";

  if (!isUsed.genders && !isUsed.colors && !isUsed.sizes) {
    htmlErrorList = generateListOfWarehousesErrors(
      validatedFormFields.main[0].errorsFor.warehouses.warehouses
    );
    return htmlErrorList;
  }

  if (!isUsed.genders && isUsed.colors) {
    htmlErrorList = generateListOfColorsErrors(
      colorsAndTheirErrors,
      sizesAndTheirErrors
    );
    return htmlErrorList;
  }

  if (!isUsed.genders && !isUsed.colors) {
    htmlErrorList = generateListOfSizesErrors(sizesAndTheirErrors);
    return htmlErrorList;
  }

  htmlErrorList = generateListOfGendersErrors(
    gendersAndTheirErrors,
    colorsAndTheirErrors,
    sizesAndTheirErrors
  );
  return htmlErrorList;
}

/**
 *
 * @param {array} gendersAndTheirErrors
 * @param {array} colorsAndTheirErrors all colors
 * @param {array} sizesAndTheirErrors all sizes
 *
 * @return {string} html list with genders, related types and their errors
 */
function generateListOfGendersErrors(
  gendersAndTheirErrors,
  colorsAndTheirErrors = [],
  sizesAndTheirErrors = []
) {
  let htmlErrorList = "";
  htmlErrorList += `
  <ul class="error-list">`;

  gendersAndTheirErrors.forEach((gender, genderIndex) => {
    let genderRelatedColors = colorsAndTheirErrors.filter(
      (color) => color.connections.genderArrayIndex === genderIndex
    );
    let genderRelatedSizes = sizesAndTheirErrors.filter(
      (size) => size.connections.genderArrayIndex === genderIndex
    );

    let amountOfErrorsInRelatedColors = 0;
    let amountOfErrorsInRelatedSizes = 0;

    genderRelatedColors.forEach((color) => {
      amountOfErrorsInRelatedColors += color.amountOfErrors;
    });
    genderRelatedSizes.forEach((size) => {
      amountOfErrorsInRelatedSizes += size.amountOfErrors;
    });

    //dont show gender if no errors in dependent colors, sizes and gender itself
    if (
      amountOfErrorsInRelatedColors === 0 &&
      amountOfErrorsInRelatedSizes === 0 &&
      gender.amountOfErrors === 0
    )
      return;

    htmlErrorList += `
    <li class="error-list-entity">
      <p class="error-list-entity-header">
        <span style="margin: -2px 5px 0px 0px;" class="material-symbols-outlined">
          face_retouching_natural
        </span>
        ${gender.name}
      </p>
      <ul class="errors">`;
    gender.errors.forEach((err) => {
      htmlErrorList += `
        <li class="errors-entity">
          ${err}`;
      htmlErrorList += `
        </li>`;
    });
    htmlErrorList += `
      </ul>`;

    if (gender.warehouses != undefined) {
      if (gender.warehouses.amountOfErrors > 0) {
        //Devider (warehouses)
        htmlErrorList += `
        <div class="error-list-entity-devider q-mt-sm"></div>`;

        //INNER CONENT (warehouses)
        htmlErrorList += generateListOfWarehousesErrors(
          gender.warehouses.warehouses
        );
      }
    }

    //Devider
    if (amountOfErrorsInRelatedColors + amountOfErrorsInRelatedSizes > 0) {
      htmlErrorList += `
      <div class="error-list-entity-devider q-mt-sm"></div>`;
    }

    //INNER CONENT
    if (isUsed.colors) {
      htmlErrorList += generateListOfColorsErrors(
        genderRelatedColors,
        sizesAndTheirErrors
      );
    }

    if (!isUsed.colors && isUsed.sizes) {
      htmlErrorList += generateListOfSizesErrors(genderRelatedSizes);
    }

    htmlErrorList += `
    </li>`;
  });

  htmlErrorList += `
  </ul>`;
  return htmlErrorList;
}

/**
 *
 * @param {array} colorsAndTheirErrors related to context colors
 * @param {array} sizesAndTheirErrors all sizes
 *
 * @return {string} html list with colors, related sizes and their errors
 */
function generateListOfColorsErrors(
  colorsAndTheirErrors,
  sizesAndTheirErrors = []
) {
  let htmlErrorList = "";
  htmlErrorList += `
  <ul class="error-list">`;

  colorsAndTheirErrors.forEach((color, colorIndex) => {
    let colorRelatedSizes = sizesAndTheirErrors.filter(
      (size) => size.connections.colorArrayIndex === color.indexInArray
    );

    let amountOfErrorsInRelatedSizes = 0;
    colorRelatedSizes.forEach((size) => {
      amountOfErrorsInRelatedSizes += size.amountOfErrors;
    });

    //do not show color if no errors in dependent sizes and color itself
    if (amountOfErrorsInRelatedSizes === 0 && color.amountOfErrors === 0)
      return;

    htmlErrorList += `
    <li class="error-list-entity">
      <div class="error-list-entity-header">
        <div style="margin: -2px 5px 0px 0px;" class="material-symbols-outlined">
          palette
        </div>
        ${color.name}
        <div style="
          display: inline-block;
          background: ${color.value};
          height: 15px;
          width: 15px;
          margin: -2px 0px 0px 5px;
          border-radius: 2px;
        ">
        </div>
      </div>
      <ul class="errors">`;
    color.errors.forEach((err) => {
      htmlErrorList += `
        <li class="errors-entity">
          ${err}`;
      htmlErrorList += `
        </li>`;
    });
    htmlErrorList += `
      </ul>`;

    if (color.warehouses != undefined) {
      if (color.warehouses.amountOfErrors > 0) {
        //Devider (warehouses)
        htmlErrorList += `
        <div class="error-list-entity-devider q-mt-sm"></div>`;

        //INNER CONENT (warehouses)
        htmlErrorList += generateListOfWarehousesErrors(
          color.warehouses.warehouses
        );
      }
    }

    //Devider (sizes)
    if (colorRelatedSizes.length > 0) {
      htmlErrorList += `
      <div class="error-list-entity-devider q-mt-sm"></div>`;
    }

    //INNER CONENT (sizes)
    htmlErrorList += generateListOfSizesErrors(colorRelatedSizes);
    htmlErrorList += `
    </li>`;
  });

  htmlErrorList += `
  </ul>`;
  return htmlErrorList;
}

/**
 *
 * @param {array} sizesAndTheirErrors related to context sizes
 *
 * @return {string} html list with sizes, related sizes and their errors
 */
function generateListOfSizesErrors(sizesAndTheirErrors) {
  let htmlErrorList = "";
  htmlErrorList += `
  <ul class="error-list">`;

  sizesAndTheirErrors.forEach((size) => {
    if (size.amountOfErrors === 0) return;

    htmlErrorList += `
    <li class="error-list-entity">
      <p class="error-list-entity-header">
        <span style="margin: -2px 5px 0px 0px;" class="material-symbols-outlined">
          straighten
        </span>
        ${size.name}
      </p>
      <ul class="errors">`;
    size.errors.forEach((err) => {
      htmlErrorList += `
        <li class="errors-entity">
          ${err}`;
      htmlErrorList += `
        </li>`;
    });
    htmlErrorList += `
      </ul>`;

    if (size.warehouses != undefined) {
      if (size.warehouses.amountOfErrors > 0) {
        //Devider (warehouses)
        htmlErrorList += `
        <div class="error-list-entity-devider q-mt-sm"></div>`;

        //INNER CONENT (warehouses)
        htmlErrorList += generateListOfWarehousesErrors(
          size.warehouses.warehouses
        );
      }
    }

    htmlErrorList += `
    </li>`;
  });

  htmlErrorList += `
  </ul>`;
  return htmlErrorList;
}

/**
 * @param {array} warehousesList
 * @return {string} html list with warehouses
 */
function generateListOfWarehousesErrors(warehousesList) {
  let htmlErrorList = "";
  htmlErrorList += `
  <ul class="error-list">`;

  warehousesList.forEach((warehouse, warehouseIndex) => {
    let amountOfBatchesErrors = 0;
    //counting amount of batches errors for particular warehouse
    warehouse.batches.forEach((batch) => {
      Object.keys(batch).forEach((field) => {
        if (batch[field].display != "") {
          amountOfBatchesErrors += 1;
        }
      });
    });

    if (amountOfBatchesErrors == 0 && warehouse.list.length == 0) return;

    htmlErrorList += `
    <li class="error-list-entity">
      <p class="error-list-entity-header">
        <span style="margin: -2px 5px 0px 0px;" class="material-symbols-outlined">
          warehouse
        </span>
        Склад ${warehouseIndex + 1}
      </p>
      <ul class="errors">`;
    warehouse.list.forEach((err) => {
      htmlErrorList += `
        <li class="errors-entity">
          ${err}`;
      htmlErrorList += `
        </li>`;
    });

    htmlErrorList += `
      </ul>`;

    if (amountOfBatchesErrors > 0) {
      //DEVIDER
      htmlErrorList += `
      <div class="error-list-entity-devider q-mt-sm"></div>`;

      //INNER CONENT
      htmlErrorList += generateListOfBatchesErrors(warehouse.batches);
    }
    htmlErrorList += `
    </li>`;
  });

  htmlErrorList += `
  </ul>`;
  return htmlErrorList;
}

/**
 * @param {array} batchesList
 * @return {string} html list with batches
 */
function generateListOfBatchesErrors(batchesList) {
  let htmlErrorList = "";
  htmlErrorList += `
  <ul class="error-list">`;

  batchesList.forEach((batch, batchIndex) => {
    let amountOfErrors = 0;
    Object.keys(batch).forEach((field) => {
      if (batch[field].display != "") {
        amountOfErrors += 1;
      }
    });

    if (amountOfErrors == 0) return;

    htmlErrorList += `
    <li class="error-list-entity">
      <p class="error-list-entity-header">
        <span style="margin: -2px 5px 0px 0px;" class="material-symbols-outlined">
          inventory_2
        </span>
        Партія ${batchIndex + 1}
      </p>
      <ul class="errors">`;
    Object.keys(batch).forEach((field) => {
      if (batch[field].display != "") {
        htmlErrorList += `
        <li class="errors-entity">
          ${batch[field].display}`;
        htmlErrorList += `
        </li>`;
      }
    });

    htmlErrorList += `
      </ul>`;

    htmlErrorList += `
    </li>`;
  });

  htmlErrorList += `
  </ul>`;
  return htmlErrorList;
}

function generateGroupID() {
  sectionStore.newMultipleItems.main.groupID = uuidv4();
}

function selectItem(itemIndex, itemType) {
  selectedIndexes[`${itemType}s`] = itemIndex;
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

function removeItem(itemIndex, type) {
  let itemInfo = removingItemInfo(itemIndex, type);

  //if removing gender, remove all colors and sizes which are belong to it
  if (type === "gender") {
    //select color with index -1 (it hides color form) to avoid unexpected issues
    selectItem(-1, "color");
    selectItem(-1, "size");

    //dependent colors
    let genderColors = sectionStore.newMultipleItems.colors.filter(
      (color) => color.connections.genderArrayIndex === itemIndex
    );
    let colorsIndexes = genderColors.map((color) => color.indexInArray);

    //dependent sizes
    let genderSizes = sectionStore.newMultipleItems.sizes.filter(
      (size) => size.connections.genderArrayIndex === itemIndex
    );
    let sizesIndexes = genderSizes.map((size) => size.indexInArray);

    //recalculate sizes-genders connection
    sectionStore.newMultipleItems.sizes =
      sectionStore.newMultipleItems.sizes.map((size) => {
        if (size.connections.genderArrayIndex > itemIndex) {
          size.connections.genderArrayIndex -= 1;
        }

        return size;
      });

    //sort color indexes from highest to lowest
    //and remove colors by their indexes in array
    colorsIndexes = colorsIndexes.sort((a, b) => b - a);
    colorsIndexes.forEach((colorIndex) => {
      removeItem(colorIndex, "color");
    });

    //if colors are nit used, just remove dependent sizes
    if (!isUsed.colors) {
      sizesIndexes = sizesIndexes.sort((a, b) => b - a);
      sizesIndexes.forEach((sizeIndex) => {
        removeItem(sizeIndex, "size");
      });
    }

    recalculateColorsArrayIndexes();
    recalculateSizesArrayIndexes();
    recalculateColorsConnectionIndexes(itemIndex);
  }

  //if removing color, remove all sizes which are belong to it
  if (type === "color") {
    selectItem(-1, "size");
    //selecting color indexes which are higher than deleting one
    let colorIndexes = sectionStore.newMultipleItems.colors.filter(
      (color, index) => index > itemIndex
    );
    colorIndexes = colorIndexes
      .map((el) => el.indexInArray)
      .sort((a, b) => a - b);

    //dependent sizes detecting, receiving their indexes
    let colorSizes = sectionStore.newMultipleItems.sizes.filter(
      (size) => size.connections.colorArrayIndex === itemIndex
    );
    let sizeIndexes = colorSizes.map((size) => size.indexInArray);

    //setting sizes new connection indexes
    let indexesOfReconnectedSizes = [];
    colorIndexes.forEach((colorIndex) => {
      sectionStore.newMultipleItems.sizes.forEach((size, index) => {
        if (size.connections.colorArrayIndex === colorIndex) {
          indexesOfReconnectedSizes.push(size.indexInArray);
        }
      });
    });
    indexesOfReconnectedSizes.forEach((sizeIndex) => {
      let targetColorIndex =
        sectionStore.newMultipleItems.sizes[sizeIndex].connections
          .colorArrayIndex - 1;
      //change parent color index
      sectionStore.newMultipleItems.sizes[
        sizeIndex
      ].connections.colorArrayIndex = targetColorIndex;
    });

    //sort size indexes from highest to lowest
    //and remove sizes by their indexes in array
    sizeIndexes = sizeIndexes.sort((a, b) => b - a);
    sizeIndexes.forEach((sizeIndex) => {
      removeItem(sizeIndex, "size");
    });

    recalculateSizesArrayIndexes();
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

watch([() => isUsed.genders, () => isUsed.colors, () => isUsed.sizes], () => {
  selectedIndexes.genders = -1;
  selectedIndexes.colors = -1;
  selectedIndexes.sizes = -1;

  sectionStore.newMultipleItems = {};
  sectionStore.newMultipleItems.main = { ...newMultupleItemsDefaultState.main };
  sectionStore.newMultipleItems.main.detail.title = "";
  sectionStore.newMultipleItems.main.detail.description = "";
  sectionStore.newMultipleItems.main.detail.article = "";
  sectionStore.newMultipleItems.main.detail.price = "";
  sectionStore.newMultipleItems.main.detail.currency = "UAH";

  let localStorageLack = localStorage.getItem("createItemLack");
  sectionStore.newMultipleItems.main.detail.lack =
    localStorageLack != null ? localStorageLack : 10;

  sectionStore.newMultipleItems.main.detail.images = [];
  sectionStore.newMultipleItems.main.detail.availableIn = [];
  sectionStore.newMultipleItems.genders = [];
  sectionStore.newMultipleItems.colors = [];
  sectionStore.newMultipleItems.sizes = [];

  //write "isUsed" to sessionStorage
  let isUsedStringify = JSON.stringify(isUsed);
  sessionStorage.setItem(
    "usedCharacteristicsForMultipleItemCreation",
    isUsedStringify
  );

  usedCharacteristics.value = [];
  if (isUsed.genders) usedCharacteristics.value.push("genders");
  if (isUsed.colors) usedCharacteristics.value.push("colors");
  if (isUsed.sizes) usedCharacteristics.value.push("sizes");
});

watch(
  () => sectionStore.newMultipleItems.main.detail.lack,
  (newValue) => {
    localStorage.setItem("createItemLack", newValue);
  }
);

//When dialog opening
watch(
  () => sectionStore.dialogs.createMultiple.isShown,
  (newValue) => {
    if (newValue == false) {
      selectedIndexes.genders = -1;
      selectedIndexes.colors = -1;
      selectedIndexes.sizes = -1;
      return;
    }

    //get "isUsed" from sessionStorage
    let isUsedFromStorage = sessionStorage.getItem(
      "usedCharacteristicsForMultipleItemCreation"
    );
    if (isUsedFromStorage != null) {
      isUsedFromStorage = JSON.parse(isUsedFromStorage);
      isUsed.genders = isUsedFromStorage.genders;
      isUsed.colors = isUsedFromStorage.colors;
      isUsed.sizes = isUsedFromStorage.sizes;
    }

    sectionStore.newMultipleItems = {};
    sectionStore.newMultipleItems.main = {
      ...newMultupleItemsDefaultState.main,
    };
    sectionStore.newMultipleItems.main.detail.title = "";
    sectionStore.newMultipleItems.main.detail.description = "";
    sectionStore.newMultipleItems.main.detail.article = "";
    sectionStore.newMultipleItems.main.detail.price = "";
    sectionStore.newMultipleItems.main.detail.currency = "UAH";

    let localStorageLack = localStorage.getItem("createItemLack");
    sectionStore.newMultipleItems.main.detail.lack =
      localStorageLack != null ? localStorageLack : 10;

    sectionStore.newMultipleItems.main.detail.images = [];
    sectionStore.newMultipleItems.main.detail.availableIn = [];
    sectionStore.newMultipleItems.genders = [];
    sectionStore.newMultipleItems.colors = [];
    sectionStore.newMultipleItems.sizes = [];
  }
);

function fillSectionStoreWithTemplate() {
  sectionStore.newMultipleItems = newMultipleItemTemplate;
}
let newMultipleItemTemplate2 = {
  main: {
    groupID: "1e5fcd32-e6f3-4fee-b086-8773a1ab30b3",
    type: { id: 54, name: "Кружки", number_in_row: 16 },
    detail: {
      title: "Кружки",
      description: "Glass Rose",
      article: "56-7126527",
      price: "350",
      currency: "UAH",
      lack: 10,
      images: [],
      availableIn: [],
    },
    unit: { id: 5, name: "шт", description: "штуки" },
  },
  genders: [],
  colors: [
    {
      id: 1,
      value: "#e61c1c",
      article: "WHI",
      description: "WRYYY",
      text_color_value: "#ffffff",
      detail: {
        title: "Кружки врушна",
        description: "Glass Rose",
        article: "56-7126527",
        price: "350",
        currency: "UAH",
        lack: "5",
        images: [],
        availableIn: [
          {
            country: { id: 1, name: "Україна" },
            city: { id: 1, country_id: 1, name: "Луцьк" },
            warehouse: {
              id: 1,
              country_id: 1,
              city_id: 1,
              address: "Київський майдан 6",
              name: "Підсобка 1",
              description:
                "Перша підсобка була створена для чогось більшого, аніж просто бути підсобкою. Там стоять маникени і лежить багато одежі, а ще...",
            },
            batches: [{ amount: "20", price: "250", currency: "UAH" }],
          },
        ],
      },
      connections: { genderArrayIndex: -1 },
      indexInArray: 0,
    },
    {
      id: 7,
      value: "#f5e798",
      article: "CR",
      description: "Кремі",
      text_color_value: "#000000",
      detail: {
        title: "Кружки кремі",
        description: "Glass Rose",
        article: "56-7126527",
        price: "350",
        currency: "UAH",
        lack: "5",
        images: [],
        availableIn: [
          {
            country: { id: 1, name: "Україна" },
            city: { id: 1, country_id: 1, name: "Луцьк" },
            warehouse: {
              id: 1,
              country_id: 1,
              city_id: 1,
              address: "Київський майдан 6",
              name: "Підсобка 1",
              description:
                "Перша підсобка була створена для чогось більшого, аніж просто бути підсобкою. Там стоять маникени і лежить багато одежі, а ще...",
            },
            batches: [{ amount: "10", price: "200", currency: "UAH" }],
          },
          {
            country: { id: 1, name: "Україна" },
            city: { id: 1, country_id: 1, name: "Луцьк" },
            warehouse: {
              id: 2,
              country_id: 1,
              city_id: 1,
              address: "Улупка Ульянвка 45, 54",
              name: "Заз",
              description: "Паз",
            },
            batches: [
              { amount: "15", price: "200", currency: "UAH" },
              { amount: "20", price: "200", currency: "UAH" },
            ],
          },
        ],
      },
      connections: { genderArrayIndex: -1 },
      indexInArray: 1,
    },
    {
      id: 3,
      value: "#2833fc",
      article: "Article2",
      description: "Каралоуий",
      text_color_value: "#000000",
      detail: {
        title: "Кружки каралоуна",
        descrition: "Glass Rose",
        article: "56-7126527",
        price: "450",
        currency: "UAH",
        lack: "5",
        images: [],
        availableIn: [],
      },
      connections: { genderArrayIndex: -1 },
      indexInArray: 2,
    },
  ],
  sizes: [],
};
let newMultipleItemTemplate = {
  main: {
    groupID: "1",
    type: { id: 29, name: "Старе Футболки", number_in_row: 1 },
    detail: {
      title: "2",
      description: "4",
      article: "",
      price: "5",
      currency: "UAH",
      lack: 10,
      images: [],
      availableIn: [],
    },
    unit: { id: 5, name: "шт", description: "штуки" },
  },
  genders: [
    {
      id: 10,
      name: "щось4",
      number_in_row: 1,
      detail: {
        title: "2",
        description: "4",
        article: "1",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
    },
    {
      id: 2,
      name: "4",
      number_in_row: 3,
      detail: {
        title: "2",
        description: "4",
        article: "2",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
    },
    {
      id: 5,
      name: "чоловіч",
      number_in_row: 6,
      detail: {
        title: "2",
        description: "4",
        article: "3",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
    },
  ],
  colors: [
    {
      id: 1,
      value: "#e61c1c",
      article: "WHI",
      description: "WRYYY",
      text_color_value: "#ffffff",
      detail: {
        title: "2",
        description: "4",
        article: "",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
      connections: { genderArrayIndex: 0 },
      indexInArray: 0,
    },
    {
      id: 6,
      value: "#14cc61",
      article: "GR",
      description: "Гріно",
      text_color_value: "#ffffff",
      detail: {
        title: "2",
        description: "4",
        article: "",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
      connections: { genderArrayIndex: 0 },
      indexInArray: 1,
    },
    {
      id: 3,
      value: "#2833fc",
      article: "Article2",
      description: "Каралоуий",
      text_color_value: "#000000",
      detail: {
        title: "2",
        description: "4",
        article: "",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
      connections: { genderArrayIndex: 1 },
      indexInArray: 2,
    },
    {
      id: 7,
      value: "#f5e798",
      article: "CR",
      description: "Кремі",
      text_color_value: "#000000",
      detail: {
        title: "2",
        description: "4",
        article: "",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
      connections: { genderArrayIndex: 1 },
      indexInArray: 3,
    },
    {
      id: 1,
      value: "#e61c1c",
      article: "WHI",
      description: "WRYYY",
      text_color_value: "#ffffff",
      detail: {
        title: "2",
        description: "4",
        article: "3",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
      connections: { genderArrayIndex: 2 },
      indexInArray: 4,
    },
  ],
  sizes: [
    {
      id: 1,
      value: "3XXL",
      description: "опопис",
      number_in_row: 1,
      detail: {
        title: "2",
        description: "4",
        article: "",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
      connections: { genderArrayIndex: 0, colorArrayIndex: 0 },
      indexInArray: 0,
    },
    {
      id: 2,
      value: "X",
      description: "мяв1",
      number_in_row: 2,
      detail: {
        title: "2",
        description: "4",
        article: "",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [
          {
            country: { id: 1, name: "Україна" },
            city: { id: 1, country_id: 1, name: "Луцьк" },
            warehouse: {
              id: 1,
              country_id: 1,
              city_id: 1,
              address: "Київський майдан 6",
              name: "Підсобка 1",
              description:
                "Перша підсобка була створена для чогось більшого, аніж просто бути підсобкою. Там стоять маникени і лежить багато одежі, а ще...",
            },
            batches: [{ amount: "10", price: "145", currency: "UAH" }],
          },
        ],
      },
      connections: { genderArrayIndex: 0, colorArrayIndex: 0 },
      indexInArray: 1,
    },
    {
      id: 3,
      value: "S",
      description: "маленька",
      number_in_row: 3,
      detail: {
        title: "2",
        description: "4",
        article: "",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
      connections: { genderArrayIndex: 0, colorArrayIndex: 0 },
      indexInArray: 2,
    },
    {
      id: 5,
      value: "L",
      description: "large",
      number_in_row: 5,
      detail: {
        title: "2",
        description: "4",
        article: "",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
      connections: { genderArrayIndex: 1, colorArrayIndex: 2 },
      indexInArray: 3,
    },
    {
      id: 4,
      value: "M",
      description: "medium",
      number_in_row: 4,
      detail: {
        title: "2",
        description: "4",
        article: "",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
      connections: { genderArrayIndex: 1, colorArrayIndex: 2 },
      indexInArray: 4,
    },
    {
      id: 2,
      value: "X",
      description: "мяв1",
      number_in_row: 2,
      detail: {
        title: "2",
        description: "4",
        article: "",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [
          {
            country: { id: 1, name: "Україна" },
            city: { id: 1, country_id: 1, name: "Луцьк" },
            warehouse: {
              id: 1,
              country_id: 1,
              city_id: 1,
              address: "Київський майдан 6",
              name: "Підсобка 1",
              description:
                "Перша підсобка була створена для чогось більшого, аніж просто бути підсобкою. Там стоять маникени і лежить багато одежі, а ще...",
            },
            batches: [{ amount: "10", price: "222", currency: "UAH" }],
          },
        ],
      },
      connections: { genderArrayIndex: 1, colorArrayIndex: 3 },
      indexInArray: 5,
    },
    {
      id: 3,
      value: "S",
      description: "маленька",
      number_in_row: 3,
      detail: {
        title: "2",
        description: "4",
        article: "",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
      connections: { genderArrayIndex: 0, colorArrayIndex: 1 },
      indexInArray: 6,
    },
    {
      id: 4,
      value: "M",
      description: "medium",
      number_in_row: 4,
      detail: {
        title: "2",
        description: "4",
        article: "",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
      connections: { genderArrayIndex: 0, colorArrayIndex: 1 },
      indexInArray: 7,
    },
    {
      id: 4,
      value: "M",
      description: "medium",
      number_in_row: 4,
      detail: {
        title: "2",
        description: "4",
        article: "",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [
          {
            country: { id: 1, name: "Україна" },
            city: { id: 1, country_id: 1, name: "Луцьк" },
            warehouse: {
              id: 4,
              country_id: 1,
              city_id: 1,
              address: "Улупка Ульянвка 45, 55",
              name: "Заз",
              description: "Шопис",
            },
            batches: [{ amount: "567", price: "70", currency: "UAH" }],
          },
        ],
      },
      connections: { genderArrayIndex: 1, colorArrayIndex: 3 },
      indexInArray: 8,
    },
    {
      id: 1,
      value: "3XXL",
      description: "опопис",
      number_in_row: 1,
      detail: {
        title: "Назвуська",
        description: "4",
        article: "3",
        price: "5",
        currency: "UAH",
        lack: 10,
        images: [],
        availableIn: [],
      },
      connections: { genderArrayIndex: 2, colorArrayIndex: 4 },
      indexInArray: 9,
    },
  ],
};
</script>
<style>
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
.error-list {
  list-style-type: none;
  padding-left: 0px;
}
.error-list-entity {
  border-left: 1px solid rgba(0, 0, 0, 0.18);
  border-top: 1px solid rgba(0, 0, 0, 0.18);
  padding: 8px 0px 0px 15px;
  border-radius: 4px 0px 0px 0px;
  margin-bottom: 15px;
}
.error-list-entity-header {
  font-weight: bold;
  margin-bottom: 0px;
  display: flex;
  flex-direction: row;
  align-items: center;
}
.error-list-entity-devider {
  width: 100%;
  height: 1px;
  /* border-top: 1px solid rgba(0, 0, 0, 0.18); */
}
.errors {
  padding-left: 15px;
  list-style-type: none;
}
.errors-entity::before {
  content: "-";
  padding-right: 6px;
}
</style>
