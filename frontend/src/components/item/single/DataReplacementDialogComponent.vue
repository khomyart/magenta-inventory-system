<template>
  <q-dialog
    v-model="sectionStore.dialogs.replaceDataInCreateItemWindow.isShown"
    transition-show="scale"
    transition-hide="scale"
  >
    <q-card style="width: 380px">
      <q-card-section>
        <div class="text-h6 flex items-center row">
          <q-icon
            size="md"
            class="q-mr-sm col-2"
            name="warehouse"
            color="black"
          ></q-icon>
          <div class="col-9">Автоматичне заповнення форми</div>
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <div>
        <q-card-section
          class="q-pa-md"
          style="height: 50vh; max-height: 400px; overflow: auto"
        >
          <p>
            - Артикль:
            {{ sectionStore.bufferedItems[dataReplacementDialogPage].article }}
          </p>
          <p>
            - Назва:
            {{ sectionStore.bufferedItems[dataReplacementDialogPage].title }}
          </p>
          <p>
            - Опис:
            {{
              sectionStore.bufferedItems[dataReplacementDialogPage].description
            }}
          </p>
          <p>
            - Вид:
            {{
              sectionStore.bufferedItems[dataReplacementDialogPage].type.name
            }}
          </p>
          <p>
            - Одиниця виміру:
            {{
              sectionStore.bufferedItems[dataReplacementDialogPage].unit.name
            }}
          </p>
          <p>
            - Колір:
            {{
              sectionStore.bufferedItems[dataReplacementDialogPage].color !=
              null
                ? sectionStore.bufferedItems[dataReplacementDialogPage].color
                    .description
                : " немає "
            }}
          </p>
          <p>
            - Розмір:
            {{
              sectionStore.bufferedItems[dataReplacementDialogPage].size != null
                ? sectionStore.bufferedItems[dataReplacementDialogPage].size
                    .value
                : " немає "
            }}
          </p>
          <p>
            - Гендер:
            {{
              sectionStore.bufferedItems[dataReplacementDialogPage].gender !=
              null
                ? sectionStore.bufferedItems[dataReplacementDialogPage].gender
                    .name
                : " немає "
            }}
          </p>
          <p>
            - Ціна:
            {{ sectionStore.bufferedItems[dataReplacementDialogPage].price }}
          </p>
          <p>
            - Валюта:
            {{ sectionStore.bufferedItems[dataReplacementDialogPage].currency }}
          </p>
          <p>
            - Нестача:
            {{ sectionStore.bufferedItems[dataReplacementDialogPage].lack }}
          </p>
          <q-separator class="q-mb-md" />
          <div class="q-mb-sm">- Зображення:</div>
          <div class="row q-mb-sm">
            <div
              v-if="
                sectionStore.bufferedItems[dataReplacementDialogPage].images
                  .length != 0
              "
              class="col-12 row image-container q-col-gutter-sm"
              style="padding-right: 8px; padding-bottom: 8px"
            >
              <ImageComponent
                v-for="(image, index) in sectionStore.bufferedItems[
                  dataReplacementDialogPage
                ].images"
                :key="index"
                :amountOfImages="
                  sectionStore.bufferedItems[dataReplacementDialogPage].images
                    .length
                "
                :imageUrl="image.url"
                :index="index"
                :previewMode="true"
              />
            </div>
          </div>
        </q-card-section>
      </div>
      <q-separator></q-separator>
      <q-card-actions class="flex justify-between">
        <div class="row">
          <q-btn
            round
            flat
            icon="arrow_left"
            @click="switchPageInDataReplacementDialog('left')"
          />
          <div class="q-px-md flex items-center">
            {{ dataReplacementDialogPage + 1 }} /
            {{ sectionStore.bufferedItems.length }}
          </div>
          <q-btn
            round
            flat
            icon="arrow_right"
            @click="switchPageInDataReplacementDialog('right')"
          />
        </div>
        <q-btn
          flat
          color="purple"
          @click="fillFormWithReplacementData"
          v-close-popup
          >Заповнити</q-btn
        >
        <q-btn flat color="black" v-close-popup>Закрити</q-btn>
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { useItemStore } from "src/stores/itemStore";
import ImageComponent from "./ImageComponent.vue";
import { watch, ref } from "vue";

const sectionStore = useItemStore();

let dataReplacementDialogPage = ref(0);

function switchPageInDataReplacementDialog(direction) {
  let amountOfItems = sectionStore.bufferedItems.length;
  switch (direction) {
    case "right":
      if (dataReplacementDialogPage.value < amountOfItems - 1) {
        dataReplacementDialogPage.value += 1;
      } else {
        dataReplacementDialogPage.value = 0;
      }
      break;
    case "left":
      if (dataReplacementDialogPage.value > 0) {
        dataReplacementDialogPage.value -= 1;
      } else {
        dataReplacementDialogPage.value = amountOfItems - 1;
      }
      break;
  }
}

function fillFormWithReplacementData() {
  let selectedItem = {
    ...sectionStore.bufferedItems[dataReplacementDialogPage.value],
  };
  console.log(selectedItem.images);
  sectionStore.newItem.article = selectedItem.article;
  sectionStore.newItem.title = selectedItem.title;
  sectionStore.newItem.description = selectedItem.description;
  sectionStore.newItem.price = selectedItem.price;
  sectionStore.newItem.lack = selectedItem.lack;
  sectionStore.newItem.currency = selectedItem.currency;
  sectionStore.newItem.type = selectedItem.type;
  sectionStore.newItem.gender = selectedItem.gender;
  sectionStore.newItem.size = selectedItem.size;
  sectionStore.newItem.color = selectedItem.color;
  sectionStore.newItem.unit = selectedItem.unit;
  sectionStore.newItem.images = selectedItem.images;
}

watch(
  () => sectionStore.dialogs.replaceDataInCreateItemWindow.isShown,
  (newValue) => {
    if (newValue === true) {
      dataReplacementDialogPage.value = 0;
    }
  }
);
</script>

<style scoped></style>
