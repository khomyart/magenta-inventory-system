<template>
  <div class="row q-mb-sm text-h6">
    <div class="col-4"></div>
    <div class="col-4 flex items-center justify-center">
      Зображення
      <input
        :id="`imagesInput_${props.type}_${props.index}`"
        style="display: none"
        type="file"
        multiple
        @change="onImageInput"
      />
    </div>
    <div class="col-4">
      <q-btn class="q-mr-sm" round flat icon="add" @click="triggerFileInput">
        <q-tooltip
          class="bg-black text-body2"
          anchor="bottom middle"
          self="top middle"
          :offset="[0, 5]"
        >
          Додати зображення
        </q-tooltip>
      </q-btn>
      <q-btn class="q-mr-sm" round flat icon="delete" @click="removeAllImages">
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
      v-if="target.images.length != 0"
      class="col-12 row image-container q-col-gutter-md q-mb-sm"
    >
      <ImageComponent
        v-for="(image, index) in target.images"
        :key="index"
        :amountOfImages="target.images.length"
        :imageUrl="image.url"
        :index="index"
        :previewMode="false"
        @remove="removeImage"
        @move="moveImage"
      />
    </div>
  </div>
</template>
<script setup>
import { watch, ref, onBeforeMount } from "vue";
import ImageComponent from "../single/ImageComponent.vue";
import { useAppConfigStore } from "src/stores/appConfigStore";
import { useItemStore } from "src/stores/itemStore";
const props = defineProps(["type", "index"]);

const appStore = useAppConfigStore();
const sectionStore = useItemStore();

let target =
  props.type === "main"
    ? sectionStore.newMultipleItems.main.detail
    : sectionStore.newMultipleItems[`${props.type}s`][props.index].detail;

//keep target element reactive
watch([() => props.index, () => props.type], () => {
  target =
    props.type === "main"
      ? sectionStore.newMultipleItems.main.detail
      : sectionStore.newMultipleItems[`${props.type}s`][props.index].detail;
});

function moveImage(imageIndex, direction) {
  let currentImage = target.images[imageIndex];
  let targetIndex =
    direction == "right" && target.images.length != imageIndex + 1
      ? imageIndex + 1
      : direction == "left" && imageIndex != 0
      ? imageIndex - 1
      : imageIndex;
  let targetImage = target.images[targetIndex];

  target.images[targetIndex] = currentImage;
  target.images[imageIndex] = targetImage;
}
function removeImage(index) {
  target.images.splice(index, 1);
}
function removeAllImages() {
  target.images = [];
}

function triggerFileInput() {
  let imageInput = document.getElementById(
    `imagesInput_${props.type}_${props.index}`
  );
  imageInput.click();
}

function onImageInput(ev) {
  const files = Array.from(ev.target.files);
  let amountOfErrors = 0;
  ev.target.value = "";

  //files existing in list
  amountOfErrors = 0;
  files.forEach((file) => {
    let matchedFiles = target.images.filter((image) => {
      return image.file.name == file.name && image.file.size == file.size;
    });
    if (matchedFiles.length > 0) amountOfErrors += 1;
  });

  if (amountOfErrors > 0) {
    let errorMessage = "";
    if (files.length > 1) {
      errorMessage = `Одне або більше з обраних зображень вже є у списку`;
    } else {
      errorMessage = `Зображення вже є у списку`;
    }
    appStore.showErrorMessage(errorMessage, false);
    return;
  }

  //files format and size validation
  amountOfErrors = 0;
  files.forEach((file) => {
    if (
      (file.type !== "image/png" &&
        file.type !== "image/jpg" &&
        file.type !== "image/jpeg") ||
      file.size > 5000000
    ) {
      amountOfErrors += 1;
    }
  });

  if (amountOfErrors > 0) {
    let errorMessage = `Дозволені формати файлів: png, jpg, jpeg. <br> Розмір не повинен перевищувати 5 MB`;
    appStore.showErrorMessage(errorMessage, true);
    return;
  }

  files.forEach((file) => {
    const reader = new FileReader();
    reader.onload = () => {
      target.images.push({
        url: reader.result,
        file: file,
      });
    };

    reader.readAsDataURL(file);
  });
}
</script>
<style scoped>
.image-container {
  margin-top: 0px;
  margin-left: 0px;
  padding-right: 16px;
  padding-bottom: 16px;
  border: 1px solid rgba(0, 0, 0, 0.18);
  border-radius: 4px;
}
</style>
