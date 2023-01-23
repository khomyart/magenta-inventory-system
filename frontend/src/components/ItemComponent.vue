<template>
  <tr :height="props.gap"></tr>
  <tr class="item">
    <td class="item-cell">
      <div>
        <div class="item-article">
          {{ props.itemInfo.article }}
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
    <td class="item-cell">
      <div class="item-name-and-buttons-holder">
        <div class="item-menu-buttons items-center flex q-pr-lg">
          <q-btn
            round
            flat
            color="black"
            dense
            icon="edit"
            @click="showEditItemDialog = !showEditItemDialog"
          >
            <q-tooltip
              class="bg-black text-body2"
              anchor="bottom left"
              :offset="[-37, 7]"
            >
              Редагувати
            </q-tooltip>
          </q-btn>
          <q-btn
            round
            flat
            color="red"
            dense
            icon="delete"
            @click="showRemoveItemDialog = !showRemoveItemDialog"
          >
            <q-tooltip
              class="bg-black text-body2"
              anchor="bottom left"
              :offset="[-15, 7]"
            >
              Видалити
            </q-tooltip>
          </q-btn>
          <q-separator vertical></q-separator>
          <q-btn
            round
            flat
            color="black"
            @click="copyValue(props.itemInfo.name, 'Назву')"
            dense
            icon="content_paste"
          >
            <q-tooltip
              class="bg-black text-body2"
              anchor="bottom left"
              :offset="[-15, 7]"
            >
              Копіювати назву
            </q-tooltip>
          </q-btn>
          <q-btn
            round
            flat
            color="black"
            dense
            icon="qr_code"
            @click="generateQrCode(props.itemInfo.id)"
          >
            <q-tooltip
              class="bg-black text-body2"
              anchor="bottom left"
              :offset="[-15, 7]"
            >
              Згенерувати QR-код
            </q-tooltip>
          </q-btn>
          <q-separator vertical></q-separator>
          <q-btn
            @click="showImage = !showImage"
            round
            flat
            :disable="props.itemInfo.images.length == 0"
            color="black"
            dense
            icon="photo"
          >
            <q-tooltip
              class="bg-black text-body2"
              anchor="center right"
              self="center left"
              :offset="[10, 10]"
            >
              {{ showImageTooltip }}
            </q-tooltip>
          </q-btn>
        </div>
        <div class="item-text">
          {{ props.itemInfo.name }}
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
    <td class="item-cell">
      <div>
        <div class="item-type">
          <img
            class="item-type-icon"
            :src="`${props.itemInfo.type.icon}`"
            alt=""
          />
          <span>{{ props.itemInfo.type.name }}</span>
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
    <td class="item-cell">
      <div>
        <div class="item-gender">
          <img
            class="item-type-icon"
            :src="`${props.itemInfo.gender.icon}`"
            alt=""
          />
          <span>{{ props.itemInfo.gender.name }}</span>
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
    <td class="item-cell">
      <div
        :id="`size-of-item-${props.itemInfo.id}`"
        style="cursor: pointer"
        @click="
          copyValue(
            `${props.itemInfo.size.name} - ${props.itemInfo.size.description}`,
            'Розмір'
          )
        "
      >
        <div class="item-size">
          <span>
            {{ props.itemInfo.size.name }}
          </span>
          <q-tooltip
            :offset="[10, 5]"
            :target="`#size-of-item-${props.itemInfo.id}`"
            class="bg-black text-body2"
            anchor="center left"
            self="center right"
            >{{ props.itemInfo.size.description }}</q-tooltip
          >
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
    <td class="item-cell">
      <div class="item-color-container">
        <div
          :id="`color-of-item-${props.itemInfo.id}`"
          class="item-color"
          :style="`background-color: ${props.itemInfo.color.value};`"
          @click="copyValue(props.itemInfo.color.value, 'Колір')"
        >
          <span
            :style="`color: ${props.itemInfo.color.textColor}`"
            class="item-color-text"
            >{{ props.itemInfo.color.value }}</span
          >
        </div>
        <q-tooltip
          :offset="[10, 5]"
          :target="`#color-of-item-${props.itemInfo.id}`"
          class="bg-black text-body2"
          anchor="center left"
          self="center right"
          >{{ props.itemInfo.color.name }}</q-tooltip
        >
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
    <td class="item-cell">
      <div>
        <div class="item-amount">
          {{ props.itemInfo.amount }}
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
    <td class="item-cell">
      <div>
        <div class="item-units">
          {{ props.itemInfo.units }}
        </div>
      </div>
    </td>
  </tr>

  <q-dialog v-model="showEditItemDialog" seamless>
    <q-card>
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6 flex items-center">
          <q-icon name="edit" color="black" size="md" /><b class="q-ml-sm"
            >Редагування</b
          >
        </div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>
      <q-card-section class="q-pa-md flex justify-center">
        <div class="q-pa-md" style="width: 400px">
          <form
            @submit.prevent.stop="onSubmit"
            @reset.prevent.stop="onReset"
            class="q-gutter-md"
          >
            <q-input
              square
              ref="editItemNameRef"
              filled
              label="Назва предмету"
            />

            <div>
              <q-btn label="Submit" type="submit" color="primary" />
              <q-btn
                label="Reset"
                type="reset"
                color="primary"
                flat
                class="q-ml-sm"
              />
            </div>
          </form>
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>

  <q-dialog v-model="showImage" seamless>
    <q-card>
      <q-card-section class="row items-center q-pb-md">
        <div class="text-h6 flex items-center">
          <q-icon name="photo" color="black" size="md" /><b class="q-ml-sm">{{
            props.itemInfo.name
          }}</b>
        </div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>
      <q-card-section class="q-pa-md flex justify-center">
        <template v-if="props.itemInfo.images.length > 1">
          <q-carousel
            animated
            v-model="slide"
            arrows
            navigation
            infinite
            control-color="primary"
            style="width: 500px"
            class="flex justify-center"
          >
            <q-carousel-slide
              style="
                width: 300px;
                margin-top: -15px;
                background-size: contain;
                background-repeat: no-repeat;
              "
              v-for="(image, index) in props.itemInfo.images"
              :key="index"
              :name="index + 1"
              :img-src="image"
            />
          </q-carousel>
        </template>
        <img
          class="q-px-md"
          v-else
          style="width: 400px"
          :src="props.itemInfo.images[0]"
          alt=""
        />
      </q-card-section>
      <q-card-section class="flex justify-center">
        <q-btn
          color="primary q-mr-md"
          @click="downloadImage(props.itemInfo.images[slide - 1])"
          >Завантажити</q-btn
        >
        <q-btn
          color="primary"
          @click="copyImage(props.itemInfo.images[slide - 1])"
          >Копіювати</q-btn
        >
      </q-card-section>
    </q-card>
  </q-dialog>

  <q-dialog v-model="showRemoveItemDialog" seamless>
    <q-card>
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6 flex items-center">
          <q-icon name="warning" color="negative" size="md" /><b class="q-ml-sm"
            >Видалення</b
          >
        </div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section>
        Ви справді бажаєте знищити предмет: "{{ props.itemInfo.name }}"?
      </q-card-section>
      <q-card-actions align="right">
        <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
        <q-btn flat color="negative" @click="removeItem(props.itemInfo.id)"
          ><b>Так</b></q-btn
        >
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { useQuasar } from "quasar";
import { computed, ref, reactive } from "vue";

const props = defineProps(["itemInfo", "gap"]);
const $q = useQuasar();
const showImageTooltip = computed(() => {
  return showImage.value ? "Сховати зображення" : "Показати зображення";
});

let showItemName = ref(true),
  slide = ref(1),
  showImage = ref(false),
  showEditItemDialog = ref(false),
  showRemoveItemDialog = ref(false),
  editItemNameRef = ref(null);

let editItemForm = reactive({
  id: "",
  name: "",
  type: "",
});

function copyValue(value, paramName) {
  navigator.clipboard.writeText(value);
  $q.notify({
    position: "top",
    color: "primary",
    message: `${paramName} зкопійовано: "${value}"`,
    group: false,
  });
}
function generateQrCode(itemId) {
  console.log(itemId);
}
function removeItem(itemId) {
  showRemoveItemDialog.value = false;
  console.log(itemId);
}
function downloadImage(imageSrc) {
  let link = document.createElement("a");
  link.href = imageSrc;
  link.download = imageSrc.split("/")[imageSrc.split("/").length - 1];
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}
async function copyImage(imageSrc) {
  try {
    const response = await fetch("." + imageSrc);
    const blob = await response.blob();
    await navigator.clipboard.write([
      new ClipboardItem({
        [blob.type]: blob,
      }),
    ]);
    $q.notify({
      position: "top",
      color: "primary",
      message: `Зображення зкопійовано`,
      group: false,
    });
  } catch (err) {
    console.error(err.name, err.message);
  }
}
</script>

<style>
:root {
  --cell-height: 50px;
  --cell-border-style: 1px solid rgba(0, 0, 0, 0.18);
}
.item {
  /* border: 1px solid rgba(0, 0, 0, 0.12); */
}
.item:hover td > div {
  background-color: rgba(255, 0, 217, 0.088);
}
.item-cell {
  padding: 0px;
}
.item-cell > div {
  width: 100%;
  display: flex;
  align-items: center;
  padding: 5px 10px;
  height: var(--cell-height);
  /* justify-content: center; */
}

.separator-cell div {
  width: 100%;
  height: var(--cell-height);
  box-sizing: border-box;
  /* background-color: rgba(14, 14, 14, 0.032); */
}
tr td {
  padding: 0;
  margin: 0;
}
tr td:first-child > div {
  border-left: var(--cell-border-style);
  border-top: var(--cell-border-style);
  border-bottom: var(--cell-border-style);
  border-radius: 3px 0 0 3px;
}

tr td:nth-child(n + 1):nth-child(-n + 14) > div {
  border-top: var(--cell-border-style);
  border-bottom: var(--cell-border-style);
}

tr td:last-child > div {
  border-right: var(--cell-border-style);
  border-top: var(--cell-border-style);
  border-bottom: var(--cell-border-style);
  border-radius: 0 3px 3px 0;
}

.item-text {
  white-space: nowrap;
  text-overflow: ellipsis;
}
.item-name-and-buttons-holder {
  cursor: pointer;
}
.item-name-and-buttons-holder:hover .item-menu-buttons {
  display: flex !important;
}
.item-name-and-buttons-holder:hover .item-text {
  display: none !important;
}
.item-name-and-buttons-holder {
}
.item-menu-buttons {
  width: fit-content;
  transition: all 0.5s ease-in-out;
  display: none !important;
}
.item-menu-buttons > * {
  margin-right: 10px;
}
.activated-text-item {
  margin-left: 30px;
}
.item-type {
  display: flex;
  align-items: center;
}
.item-type > span {
  white-space: nowrap;
  text-overflow: ellipsis;
}
.item-type-icon {
  --icon-width: 35px;
  padding-right: 8px;
  min-width: var(--icon-width);
  max-width: var(--icon-width);
}
.item-gender {
  display: flex;
  align-items: center;
}
.item-gender > span {
  white-space: nowrap;
  text-overflow: ellipsis;
}
.item-color-container {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}
.item-color {
  width: 70%;
  height: 80%;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 5px;
  cursor: pointer;
}
</style>
