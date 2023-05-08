<template>
  <q-dialog v-model="sectionStore.dialogs.update.isShown">
    <q-card style="width: 95vw; max-width: 600px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="apps" color="black" size="md" class="q-mr-sm" />
          Предмет
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="sectionStore.update()">
        <q-card-section
          style="max-height: 700px; height: 60vh"
          class="scroll col-12 q-pt-lg"
        >
          <div class="row q-col-gutter-md q-mb-sm q-pt-sm">
            <q-input
              class="col-5 q-pt-sm"
              autofocus
              outlined
              v-model="sectionStore.selectedItemForUpdating.article"
              label="Артикль"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть артикль',
                (val) => val.length <= 10 || 'Не більше 10 символів',
              ]"
            />
            <q-input
              class="col-7 q-pt-sm"
              outlined
              v-model="sectionStore.selectedItemForUpdating.title"
              label="Назва"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть назву',
                (val) => val.length <= 255 || 'Не більше 255 символів',
              ]"
            />
            <q-input
              class="col-5 q-pt-sm"
              outlined
              v-model="sectionStore.selectedItemForUpdating.model"
              label="Модель"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть модель',
                (val) => val.length <= 255 || 'Не більше 255 символів',
              ]"
            />
            <q-input
              outlined
              v-model="sectionStore.selectedItemForUpdating.group_id"
              label="ID групи"
              readonly
              class="col-7 q-pt-sm"
            />

            <q-input
              outlined
              v-model="sectionStore.selectedItemForUpdating.type.name"
              label="Вид"
              readonly
              class="col-6 q-pt-sm q-pb-md"
            />

            <q-input
              outlined
              v-model="sectionStore.selectedItemForUpdating.unit.name"
              label="Одиниця виміру"
              readonly
              class="col-6 q-pt-sm q-pb-md"
            />

            <q-select
              :hide-dropdown-icon="
                sectionStore.selectedItemForUpdating.color != null
              "
              outlined
              v-model="sectionStore.selectedItemForUpdating.color"
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
                  sectionStore.selectedItemForUpdating.color &&
                  !colorStore.data.isItemsLoading
                "
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="
                    sectionStore.selectedItemForUpdating.color = null
                  "
                  class="cursor-pointer"
                />
              </template>
            </q-select>

            <q-select
              :hide-dropdown-icon="
                sectionStore.selectedItemForUpdating.size != null
              "
              outlined
              v-model="sectionStore.selectedItemForUpdating.size"
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
                  sectionStore.selectedItemForUpdating.size &&
                  !sizeStore.data.isItemsLoading
                "
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="
                    sectionStore.selectedItemForUpdating.size = null
                  "
                  class="cursor-pointer"
                />
              </template>
            </q-select>

            <q-select
              :hide-dropdown-icon="
                sectionStore.selectedItemForUpdating.gender != null
              "
              outlined
              v-model="sectionStore.selectedItemForUpdating.gender"
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
                  sectionStore.selectedItemForUpdating.gender &&
                  !genderStore.data.isItemsLoading
                "
                v-slot:append
              >
                <q-icon
                  name="cancel"
                  @click.stop.prevent="
                    sectionStore.selectedItemForUpdating.gender = null
                  "
                  class="cursor-pointer"
                />
              </template>
            </q-select>

            <q-input
              class="col-4 q-pt-sm"
              outlined
              v-model="sectionStore.selectedItemForUpdating.price"
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
              v-model="sectionStore.selectedItemForUpdating.currency"
              label="Валюта"
              :options="['UAH', 'USD', 'EUR']"
              class="col-4 q-pt-sm"
            />
            <q-input
              class="col-4 q-pt-sm"
              outlined
              v-model="sectionStore.selectedItemForUpdating.lack"
              label="Нестача"
              type="number"
              :rules="[
                (val) => (val !== null && val !== '') || 'Вкажіть нестачу',
                (val) => val >= 1 || 'Не менше 1',
              ]"
            />
          </div>
          <q-separator class="q-mb-sm" />
          <div class="row q-mb-sm text-h6">
            <div class="col-4"></div>
            <div class="col-4 flex justify-center items-center">
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
              v-if="sectionStore.selectedItemForUpdating.images.length != 0"
              class="col-12 row image-container q-col-gutter-md"
            >
              <ImageComponent
                v-for="(image, index) in sectionStore.selectedItemForUpdating
                  .images"
                :key="index"
                :amountOfImages="
                  sectionStore.selectedItemForUpdating.images.length
                "
                :imageUrl="image.url"
                :index="index"
                @remove="removeImage"
                @move="moveImage"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn
            flat
            color="primary"
            type="submit"
            :loading="sectionStore.dialogs.update.isLoading"
            ><b>Оновити</b></q-btn
          >
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
<script setup>
import { onMounted, watch } from "vue";
import { useCountryStore } from "src/stores/helpers/countryStore";
import { useCityStore } from "src/stores/helpers/cityStore";
import { useItemStore } from "src/stores/itemStore";
import { useTypeStore } from "src/stores/typeStore";
import { useSizeStore } from "src/stores/sizeStore";
import { useGenderStore } from "src/stores/genderStore";
import { useColorStore } from "src/stores/colorStore";
import { useUnitStore } from "src/stores/unitStore";
import ImageComponent from "./ImageComponent.vue";

const sectionStore = useItemStore();
const countryStore = useCountryStore();
const cityStore = useCityStore();
const typeStore = useTypeStore();
const sizeStore = useSizeStore();
const genderStore = useGenderStore();
const colorStore = useColorStore();
const unitStore = useUnitStore();

function moveImage(imageIndex, direction) {
  let currentImage = sectionStore.selectedItemForUpdating.images[imageIndex];
  let targetIndex =
    direction == "right" &&
    sectionStore.selectedItemForUpdating.images.length != imageIndex + 1
      ? imageIndex + 1
      : direction == "left" && imageIndex != 0
      ? imageIndex - 1
      : imageIndex;
  let targetImage = sectionStore.selectedItemForUpdating.images[targetIndex];

  sectionStore.selectedItemForUpdating.images[targetIndex] = currentImage;
  sectionStore.selectedItemForUpdating.images[imageIndex] = targetImage;
}
function removeImage(index) {
  sectionStore.selectedItemForUpdating.images.splice(index, 1);
}
function removeAllImages() {
  sectionStore.selectedItemForUpdating.images = [];
}

function triggerFileInput() {
  let imageInput = document.getElementById("imagesInput");
  imageInput.click();
}

function onImageInput(ev) {
  const files = ev.target.files;
  console.log(files);

  Object.keys(files).forEach((i) => {
    const reader = new FileReader();

    reader.onload = () => {
      console.log(reader);
      sectionStore.selectedItemForUpdating.images.push({
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
  () => sectionStore.dialogs.update.isShown,
  (newValue) => {
    if (newValue == true) {
      sectionStore.selectedItemForUpdating.images.forEach((image, index) => {
        fetch(image.url)
          .then((res) => res.blob())
          .then((blob) => {
            sectionStore.selectedItemForUpdating.images[index].file = new File(
              [blob],
              image.name,
              { type: image.mimeType }
            );
          });
      });
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
