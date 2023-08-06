<template>
  <q-dialog v-model="sectionStore.dialogs.createMultiple.isShown">
    <q-card style="width: 95vw; max-width: 600px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="apps" color="black" size="md" class="q-mr-sm" />
          Група предметів г: {{ selectedIndexes.genders }}, c:
          {{ selectedIndexes.colors }}, s: {{ selectedIndexes.sizes }},
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
              <q-separator class="q-mt-md" />
              <SelectedGenderFormComponent
                v-if="selectedIndexes.genders != -1"
                :genderArrayIndex="selectedIndexes.genders"
                :rules="genderFieldsRules"
                :lastUsedCharacteristic="usedCharacteristics.splice(-1)[0]"
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
            :lastUsedCharacteristic="usedCharacteristics.splice(-1)[0]"
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
            :lastUsedCharacteristic="usedCharacteristics.splice(-1)[0]"
            :rules="sizeFieldsRules"
            @selectSize="selectItem"
            @removeSize="removeItem"
          />
          <AddAvailableInComponent
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
  genders: true,
  colors: true,
  sizes: true,
});

const newMultupleItemsDefaultState = {
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
    (val) => val.length <= 10 || "Не більше 10 символів",
  ],
  model: [
    (val) => (val !== null && val !== "") || "Введіть модель",
    (val) => val.length <= 255 || "Не більше 255 символів",
  ],
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

let colorFieldsRules = {
  model: [
    (val) => (val !== null && val !== "") || "Введіть модель",
    (val) => val.length <= 255 || "Не більше 255 символів",
  ],
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
  model: [
    (val) => (val !== null && val !== "") || "Введіть модель",
    (val) => val.length <= 255 || "Не більше 255 символів",
  ],
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
    newGenderTemplate.detail = cloneObject(
      sectionStore.newMultipleItems.main.detail
    );
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
 * @param {string} type validation target type (gender, size or color)
 * @param {boolean} restricted if true - limit amount of rules for some types to minimum, for instance:
 * genders - only checking for article and amount of colors and sizes.
 * colors - only checking for amount of sizes
 * @return {array} list of items (with selected type), their indexes, amount of errors and errors info
 */
function validator(type, restricted = false) {
  let errorsCollector = [];
  let items = sectionStore.newMultipleItems[`${type}s`];

  items.forEach((item, itemIndex) => {
    let fieldsValues = item.detail;
    let errorsFor = {
      model: {
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
    };

    if (restricted === true && (type === "gender" || type === "color")) {
      errorsFor = {};
    }

    errorsCollector.push({
      indexInArray: itemIndex,
      amountOfErrors: 0,
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
 * General function
 */
function submit() {
  let formFields = {
    genders: validator(
      "gender",
      isUsed.genders && (isUsed.colors || isUsed.sizes)
    ),
    colors: validator("color", isUsed.sizes),
    sizes: validator("size"),
  };
  let combinedFields = [
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

  console.log("submited with: ", formFields);
  if (amountOfErrors > 0) {
    let errorMessage = generateErrorMessage(formFields);
    appStore.showErrorMessage(errorMessage, true);
    return;
  }
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
  let gendersAndTheirErrors = [];
  let colorsAndTheirErrors = [];
  let sizesAndTheirErrors = [];

  //getting genders errors
  validatedFormFields.genders.forEach((gender, genderIndex) => {
    gendersAndTheirErrors.push({
      name: "",
      errors: [],
    });
    gendersAndTheirErrors[genderIndex].name =
      sectionStore.newMultipleItems.genders[gender.indexInArray].name;
    Object.keys(gender.errorsFor).forEach((fieldName) => {
      if (gender.errorsFor[fieldName].display != "") {
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
    });
    colorsAndTheirErrors[colorIndex].name =
      sectionStore.newMultipleItems.colors[color.indexInArray].description;
    Object.keys(color.errorsFor).forEach((fieldName) => {
      if (color.errorsFor[fieldName].display != "") {
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
    });
    sizesAndTheirErrors[sizeIndex].name =
      sectionStore.newMultipleItems.sizes[size.indexInArray].value;
    Object.keys(size.errorsFor).forEach((fieldName) => {
      if (size.errorsFor[fieldName].display != "") {
        sizesAndTheirErrors[sizeIndex].errors.push(
          size.errorsFor[fieldName].display
        );
      }
    });
  });

  let htmlErrorList = "";

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
      amountOfErrorsInRelatedColors += color.errors.length;
    });
    genderRelatedSizes.forEach((size) => {
      amountOfErrorsInRelatedSizes += size.errors.length;
    });

    //dont show gender if no errors in dependent colors, sizes and gender itself
    if (
      amountOfErrorsInRelatedColors === 0 &&
      amountOfErrorsInRelatedSizes === 0 &&
      gender.errors.length === 0
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

    //Devider
    if (genderRelatedColors.length > 0 || genderRelatedSizes.length > 0) {
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
      amountOfErrorsInRelatedSizes += size.errors.length;
    });

    //dont show color if no errors in dependent sizes and color itself
    if (amountOfErrorsInRelatedSizes === 0 && color.errors.length === 0) return;

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

    //Devider
    if (colorRelatedSizes.length > 0) {
      htmlErrorList += `
      <div class="error-list-entity-devider q-mt-sm"></div>`;
    }

    //INNER CONENT
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
    if (size.errors.length === 0) return;

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

  sectionStore.newMultipleItems = JSON.parse(
    JSON.stringify(newMultupleItemsDefaultState)
  );

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
  console.log(usedCharacteristics.value);
});

watch(
  () => sectionStore.dialogs.createMultiple.isShown,
  (newValue) => {
    if (newValue == false) {
      selectedIndexes.genders = -1;
      selectedIndexes.colors = -1;
      selectedIndexes.sizes = -1;
      sectionStore.newMultipleItems = {};
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

    sectionStore.newMultipleItems = JSON.parse(
      JSON.stringify(newMultupleItemsDefaultState)
    );
  }
);
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
  border: 1px solid rgba(0, 0, 0, 0.18);
  padding: 8px 15px;
  border-radius: 4px;
  margin-bottom: 8px;
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
