<template>
  <q-dialog v-model="sectionStore.dialogs.createMultiple.isShown">
    <q-card style="width: 95vw; max-width: 600px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="apps" color="black" size="md" class="q-mr-sm" />
          Група предметів, г: {{ selectedIndexes.genders }}, к:
          {{ selectedIndexes.colors }}, р: {{ selectedIndexes.sizes }}
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit.prevent="submit">
        <q-card-section
          style="max-height: 700px; height: 60vh"
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
              class="col-12 items-wrapper q-px-md q-pt-md q-pb-md q-mb-lg q-mt-sm q-mt-sm-sm"
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
function selectItem(itemIndex, itemType) {
  console.log("selected");
  selectedIndexes[`${itemType}s`] = itemIndex;

  //when selecting gender, chose first color of it
  if (itemType === "gender") {
    let genderColors = sectionStore.newMultipleItems.colors.filter(
      (color) => color.connections.genderArrayIndex === selectedIndexes.genders
    );
    let firstColorItemIndexInGender =
      genderColors.length > 0 ? genderColors[0].indexInArray : -1;

    selectedIndexes.colors = firstColorItemIndexInGender;
  }
}

function recalculateColorsArrayIndexes() {
  sectionStore.newMultipleItems.colors =
    sectionStore.newMultipleItems.colors.map((color, index) => {
      color.indexInArray = index;
      return color;
    });
}
/**
 * Colors are connected to genders
 * So when we deleting gender, should recalculate this connection too
 */
function recalculateColorsConnectionIndexes(deletedGenderIndex) {
  sectionStore.newMultipleItems.colors.map((color, index) => {
    if (color.connections.genderArrayIndex > deletedGenderIndex) {
      color.connections.genderArrayIndex -= 1;
    }

    return color;
  });
}

function removeItem(itemIndex, type) {
  let itemsAmount = sectionStore.newMultipleItems[`${type}s`].length;
  //if removing gender, remove all colors, which are belong to it
  if (type === "gender") {
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
  }

  sectionStore.newMultipleItems[`${type}s`].splice(itemIndex, 1);

  if (type === "color") {
    recalculateColorsArrayIndexes();
  }
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
    if (itemIndex + 1 === itemsAmount) {
      selectItem(selectedIndexes[`${type}s`] - 1, type);
    } else {
      selectItem(selectedIndexes[`${type}s`], type);
    }
    return;
  }
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
