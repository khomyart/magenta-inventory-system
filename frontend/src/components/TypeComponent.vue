<template>
  <tr :height="props.gap"></tr>
  <tr class="item" :id="`item${props.itemInfo.id}`">
    <td class="item-cell">
      <div>
        <q-btn icon="list" round flat>
          <q-menu :offset="[5, 5]">
            <q-list style="min-width: 100px">
              <q-item
                clickable
                v-close-popup
                @click="showEditItemDialog = !showEditItemDialog"
              >
                <div class="context-menu-item">
                  <q-icon size="sm" name="edit" left></q-icon>
                  <span>Редагувати</span>
                </div>
              </q-item>
              <q-item
                clickable
                v-close-popup
                @click="showRemoveItemDialog = !showRemoveItemDialog"
              >
                <div class="context-menu-item">
                  <q-icon size="sm" color="red" name="delete" left></q-icon>
                  <span>Видалити</span>
                </div>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
    <td class="item-cell">
      <div
        style="cursor: pointer"
        @click="copyValue(props.itemInfo.article, 'Артикль')"
      >
        <div class="item-article">
          {{ props.itemInfo.article }}
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
    <td class="item-cell">
      <div class="item-name" @click="copyValue(props.itemInfo.name, 'Назву')">
        {{ props.itemInfo.name }}
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
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
        Ви справді бажаєте знищити вид: "{{ props.itemInfo.name }}"?
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

let contextMenuOptions = reactive({
  isShown: false,
  offset: {
    x: 0,
    y: 0,
  },
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

tr td:nth-child(n + 1):nth-child(-n + 25) > div {
  border-top: var(--cell-border-style);
  border-bottom: var(--cell-border-style);
}

tr td:last-child > div {
  border-right: var(--cell-border-style);
  border-top: var(--cell-border-style);
  border-bottom: var(--cell-border-style);
  border-radius: 0 3px 3px 0;
}

.context-menu-item {
  display: flex;
  align-items: center;
}
.context-menu-item span {
  /* font-size: 1rem; */
}
.item-name {
  white-space: nowrap;
  text-overflow: ellipsis;
  cursor: pointer;
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
