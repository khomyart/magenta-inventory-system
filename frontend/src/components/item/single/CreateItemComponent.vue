<template>
  <q-dialog v-model="sectionStore.dialogs.create.isShown">
    <q-card style="width: 95vw; max-width: 600px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="apps" color="black" size="md" class="q-mr-sm" />
          Предмет
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="sectionStore.create()">
        <q-card-section
          style="max-height: 700px; height: 60vh"
          class="scroll col-12 q-pt-lg"
        >
          <div class="row q-col-gutter-md q-mb-sm q-pt-sm">
            <q-input
              class="col-5 q-pt-sm"
              outlined
              v-model="sectionStore.newItem.article"
              label="Артикль"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть артикль',
                (val) => val.length <= 10 || 'Не більше 10 символів',
              ]"
            />
            <q-input
              class="col-7 q-pt-sm"
              outlined
              v-model="sectionStore.newItem.title"
              label="Назва"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть назву',
                (val) => val.length <= 255 || 'Не більше 255 символів',
              ]"
            />
            <q-input
              class="col-5 q-pt-sm"
              outlined
              v-model="sectionStore.newItem.model"
              label="Модель"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть модель',
                (val) => val.length <= 255 || 'Не більше 255 символів',
              ]"
            />
            <q-input
              class="col-7 q-pt-sm"
              outlined
              v-model="sectionStore.newItem.group_id"
              :debounce="700"
              label="ID групи"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть ID групи',
                (val) => val.length <= 36 || 'Не більше 36 символів',
              ]"
              :loading="sectionStore.data.isItemDataLoading"
            >
              <template v-slot:append>
                <q-icon
                  v-if="!sectionStore.data.isItemDataLoading"
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
              <span
                v-if="sectionStore.bufferedItems.length > 0"
                style="
                  position: absolute;
                  bottom: -23px;
                  right: -40px;
                  color: green;
                  font-size: 12px;
                  cursor: pointer;
                  z-index: 9999;
                "
                @click.prevent.stop="showDataReplacementDialog"
              >
                Знайдено відповідність у базі даних
              </span>
            </q-input>
            <q-select
              :hide-dropdown-icon="
                sectionStore.newItem.type != null &&
                sectionStore.newItem.type.id != undefined
              "
              outlined
              v-model="sectionStore.newItem.type"
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
                  (sectionStore.newItem.type != null &&
                    sectionStore.newItem.type.id != undefined) ||
                  'Оберіть вид',
              ]"
            >
              <template
                v-if="
                  sectionStore.newItem.type && !typeStore.data.isItemsLoading
                "
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="sectionStore.newItem.type = null"
                  class="cursor-pointer"
                />
              </template>
            </q-select>

            <q-select
              :hide-dropdown-icon="
                sectionStore.newItem.unit != null &&
                sectionStore.newItem.unit.id != undefined
              "
              outlined
              v-model="sectionStore.newItem.unit"
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
                  (sectionStore.newItem.unit != null &&
                    sectionStore.newItem.unit.id != undefined) ||
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
                  sectionStore.newItem.unit && !unitStore.data.isItemsLoading
                "
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="sectionStore.newItem.unit = null"
                  class="cursor-pointer"
                />
              </template>
            </q-select>

            <q-select
              :hide-dropdown-icon="sectionStore.newItem.color != null"
              outlined
              v-model="sectionStore.newItem.color"
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
              class="col-4 q-pt-sm q-pb-md"
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
                v-if="
                  sectionStore.newItem.color && !colorStore.data.isItemsLoading
                "
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="sectionStore.newItem.color = null"
                  class="cursor-pointer"
                />
              </template>
            </q-select>

            <q-select
              :hide-dropdown-icon="sectionStore.newItem.size != null"
              outlined
              v-model="sectionStore.newItem.size"
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
              class="col-4 q-pt-sm q-pb-md"
            >
              <template
                v-if="
                  sectionStore.newItem.size && !sizeStore.data.isItemsLoading
                "
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="sectionStore.newItem.size = null"
                  class="cursor-pointer"
                />
              </template>
            </q-select>

            <q-select
              :hide-dropdown-icon="sectionStore.newItem.gender != null"
              outlined
              v-model="sectionStore.newItem.gender"
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
              class="col-4 q-pt-sm q-pb-md"
            >
              <template
                v-if="
                  sectionStore.newItem.gender &&
                  !genderStore.data.isItemsLoading
                "
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="sectionStore.newItem.gender = null"
                  class="cursor-pointer"
                />
              </template>
            </q-select>

            <q-input
              class="col-4 q-pt-sm"
              outlined
              v-model="sectionStore.newItem.price"
              label="Ціна"
              type="number"
              :rules="[
                (val) => (val !== null && val !== '') || 'Вкажіть ціну',
                (val) => val.length <= 13 || 'Не більше 13 символів',
                (val) => val >= 1 || 'Не менше 1',
              ]"
            />
            <q-select
              hide-dropdown-icon
              outlined
              v-model="sectionStore.newItem.currency"
              label="Валюта"
              :options="['UAH', 'USD', 'EUR']"
              class="col-4 q-pt-sm"
            />
            <q-input
              class="col-4 q-pt-sm"
              outlined
              v-model="sectionStore.newItem.lack"
              label="Нестача"
              type="number"
              :rules="[
                (val) => (val !== null && val !== '') || 'Вкажіть нестачу',
                (val) => val >= 1 || 'Не менше одиниці',
              ]"
            />
          </div>
          <q-separator class="q-mb-sm" />
          <div class="row q-mb-sm text-h6">
            <div class="col-4"></div>
            <div class="col-4 text-center">
              Зображення
              <input
                id="imagesInput"
                style="display: none"
                type="file"
                multiple
                @change="onImageInput"
              />
            </div>
            <div class="col-4">
              <q-btn
                class="q-mr-sm"
                round
                flat
                icon="add"
                @click="triggerFileInput"
              >
                <q-tooltip
                  class="bg-black text-body2"
                  anchor="bottom middle"
                  self="top middle"
                  :offset="[0, 5]"
                >
                  Додати зображення
                </q-tooltip>
              </q-btn>
              <q-btn
                class="q-mr-sm"
                round
                flat
                icon="delete"
                @click="removeAllImages"
              >
                <q-tooltip
                  class="bg-black text-body2"
                  anchor="bottom middle"
                  self="top middle"
                  :offset="[0, 5]"
                >
                  Видалити всі зображення
                </q-tooltip>
              </q-btn>
            </div>
          </div>
          <div class="row q-mb-sm">
            <div
              v-if="sectionStore.newItem.images.length != 0"
              class="col-12 row image-container q-col-gutter-md"
            >
              <ImageComponent
                v-for="(image, index) in sectionStore.newItem.images"
                :key="index"
                :amountOfImages="sectionStore.newItem.images.length"
                :imageUrl="image.url"
                :index="index"
                :previewMode="false"
                @remove="removeImage"
                @move="moveImage"
              />
            </div>
          </div>
          <div class="row q-mb-sm text-h6">
            <div class="col-4"></div>
            <div class="col-4 text-center">Наявність</div>
            <div class="col-4">
              <q-btn
                class="q-mr-sm"
                round
                flat
                icon="add"
                @click="addAvailableIn"
              >
                <q-tooltip
                  class="bg-black text-body2"
                  anchor="bottom middle"
                  self="top middle"
                  :offset="[0, 5]"
                >
                  Додати склад
                </q-tooltip>
              </q-btn>
              <q-btn
                class="q-mr-sm"
                round
                flat
                icon="delete"
                @click="removeAllWarehouses"
              >
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
            v-for="(item, index) in sectionStore.newItem.availableIn"
            :key="index"
            :index="index"
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

  <!-- DATA REPLACEMENT DIALOG -->
  <DataReplacementDialogComponent />
</template>
<script setup>
import { v4 as uuidv4 } from "uuid";
import { watch } from "vue";
import { useCountryStore } from "src/stores/helpers/countryStore";
import { useCityStore } from "src/stores/helpers/cityStore";
import { useItemStore } from "src/stores/itemStore";
import { useTypeStore } from "src/stores/typeStore";
import { useSizeStore } from "src/stores/sizeStore";
import { useGenderStore } from "src/stores/genderStore";
import { useColorStore } from "src/stores/colorStore";
import { useWarehouseStore } from "src/stores/warehouseStore";
import { useUnitStore } from "src/stores/unitStore";
import WarehouseFormComponent from "src/components/item/single/WarehouseFormComponent.vue";
import ImageComponent from "./ImageComponent.vue";
import DataReplacementDialogComponent from "./DataReplacementDialogComponent.vue";

const sectionStore = useItemStore();
const countryStore = useCountryStore();
const cityStore = useCityStore();
const typeStore = useTypeStore();
const sizeStore = useSizeStore();
const genderStore = useGenderStore();
const colorStore = useColorStore();
const warehouseStore = useWarehouseStore();
const unitStore = useUnitStore();

const warehouseTemplate = {
  country: null,
  city: null,
  warehouse: null,
  batches: [
    {
      amount: "",
      price: 0,
      currency: "UAH",
    },
  ],
};

function showDataReplacementDialog() {
  sectionStore.dialogs.replaceDataInCreateItemWindow.isShown = true;
}

function clearCreateItemDialogData() {
  sectionStore.newItem.group_id = "";
  sectionStore.newItem.article = "";
  sectionStore.newItem.title = "";
  sectionStore.newItem.model = "";
  sectionStore.newItem.price = "";
  sectionStore.newItem.lack = 10;
  sectionStore.newItem.currency = "UAH";
  sectionStore.newItem.type = null;
  sectionStore.newItem.gender = null;
  sectionStore.newItem.size = null;
  sectionStore.newItem.color = null;
  sectionStore.newItem.unit = null;
  sectionStore.newItem.availableIn = [];
  sectionStore.newItem.images = [];
  sectionStore.bufferedItems = [];
}

function addAvailableIn() {
  let warehouse = JSON.parse(JSON.stringify(warehouseTemplate));
  sectionStore.newItem.availableIn.push(warehouse);
}

function removeAllWarehouses() {
  sectionStore.newItem.availableIn = [];
}

function generateGroupID() {
  sectionStore.newItem.group_id = uuidv4();
}

function moveImage(imageIndex, direction) {
  let currentImage = sectionStore.newItem.images[imageIndex];
  let targetIndex =
    direction == "right" && sectionStore.newItem.images.length != imageIndex + 1
      ? imageIndex + 1
      : direction == "left" && imageIndex != 0
      ? imageIndex - 1
      : imageIndex;
  let targetImage = sectionStore.newItem.images[targetIndex];

  sectionStore.newItem.images[targetIndex] = currentImage;
  sectionStore.newItem.images[imageIndex] = targetImage;
}
function removeImage(index) {
  sectionStore.newItem.images.splice(index, 1);
}
function removeAllImages() {
  sectionStore.newItem.images = [];
}

function triggerFileInput() {
  let imageInput = document.getElementById("imagesInput");
  imageInput.click();
}

function onImageInput(ev) {
  console.log("called");

  const files = ev.target.files;
  console.log(files);

  Object.keys(files).forEach((i) => {
    const reader = new FileReader();

    reader.onload = () => {
      console.log(reader);
      sectionStore.newItem.images.push({
        url: reader.result,
        file: files[i],
      });
    };

    reader.readAsDataURL(files[i]);
  });
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

watch(
  () => sectionStore.dialogs.create.isShown,
  (newValue) => {
    if (
      newValue === true &&
      sectionStore.data.creatingItemIndexInArray === -1
    ) {
      /**
       * clear data only if clicked "create new item",
       * if clicked "create new item from another item" - prevent data clearing
       */
      clearCreateItemDialogData();
    }

    /**
     * if create item dialog has opened with prepared data, proceed
     * calculating of accepted images from db - make blob from them
     * and prepare those files for sending them to backend
     */
    if (newValue === true && sectionStore.data.creatingItemIndexInArray != -1) {
      sectionStore.newItem.images.forEach((image, index) => {
        fetch(image.url)
          .then((res) => res.blob())
          .then((blob) => {
            sectionStore.newItem.images[index].file = new File(
              [blob],
              image.name,
              { type: image.mimeType }
            );
          });
      });
    }
  }
);

/**
 * if item data replacement dialog is opened, we have relevant data
 * in "sectionStore.bufferedItems" array. Also, there images are stored.
 * As we need to use those images later in backend, we should prepare them
 * and transform them into blob files
 */
watch(
  () => sectionStore.dialogs.replaceDataInCreateItemWindow.isShown,
  (newValue) => {
    if (newValue === true) {
      sectionStore.bufferedItems.forEach((item, bufferedItemIndex) => {
        item.images.forEach((image, index) => {
          fetch(image.url)
            .then((res) => res.blob())
            .then((blob) => {
              sectionStore.bufferedItems[bufferedItemIndex].images[index].file =
                new File([blob], image.name, { type: image.mimeType });
            });
        });
      });
    }
  }
);

/**
 * Clear "sectionStore.bufferedItems" variable if we are typing more than 36
 * symbols in "group_id" field of item creation dialog
 */
watch(
  () => sectionStore.newItem.group_id,
  (newValue) => {
    if (newValue.length <= 36) {
      sectionStore.receiveItemsByGroupID(newValue);
    } else {
      sectionStore.bufferedItems = [];
    }
  }
);
</script>
<style scoped>
.color {
  width: 30px;
  height: 30px;
  border-radius: 5px;
  border: 1px solid rgba(0, 0, 0, 0.18);
}
.image-container {
  margin-top: 0px;
  margin-left: 0px;
  padding-right: 16px;
  padding-bottom: 16px;
  border: 1px solid rgba(0, 0, 0, 0.18);
  border-radius: 4px;
}
</style>
