<template>
  <div class="row no-wrap q-mt-md item q-py-sm">
    <div
      class="item-cell"
      :style="`width: ${props.cellsWidth.name}px;`"
      @mouseenter="showItemName = false"
      @mouseleave="showItemName = true"
    >
      <div
        class="item-menu-buttons items-center flex q-pr-lg"
        :class="{ 'activated-buttons-item': !showItemName }"
        v-if="!showItemName"
      >
        <q-btn round flat color="black" dense icon="edit" />
        <q-btn
          round
          flat
          color="red"
          dense
          icon="delete"
          @click="showRemoveItemDialog = true"
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
          :disable="props.itemInfo.image == null"
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
      <div class="item-text" v-if="showItemName">
        {{ props.itemInfo.name }}
      </div>
    </div>
    <div class="filter-separator name-separator"></div>
    <div class="item-cell" :style="`width: ${props.cellsWidth.type}px;`">
      <div class="item-type">
        <img
          class="item-type-icon"
          :src="`${props.itemInfo.type.icon}`"
          alt=""
        />
        <span>{{ props.itemInfo.type.name }}</span>
      </div>
    </div>
    <div class="filter-separator name-separator"></div>
    <div class="item-cell" :style="`width: ${props.cellsWidth.gender}px;`">
      <div class="item-gender">
        <img
          class="item-type-icon"
          :src="`${props.itemInfo.gender.icon}`"
          alt=""
        />
        <span>{{ props.itemInfo.gender.name }}</span>
      </div>
    </div>
    <div class="filter-separator name-separator"></div>
    <div class="item-cell" :style="`width: ${props.cellsWidth.size}px;`">
      <div class="item-size">
        <span
          :id="`size-of-item-${props.itemInfo.id}`"
          style="cursor: pointer"
          @click="
            copyValue(
              `${props.itemInfo.size.name} - ${props.itemInfo.size.description}`,
              'Розмір'
            )
          "
        >
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
    <div class="filter-separator name-separator"></div>
    <div
      class="item-cell q-pa-md"
      :style="`width: ${props.cellsWidth.color}px;`"
    >
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
    <div class="filter-separator name-separator"></div>
    <div class="item-cell" :style="`width: ${props.cellsWidth.amount}px;`">
      <div class="item-amount">
        {{ props.itemInfo.amount }}
      </div>
    </div>
  </div>

  <q-dialog v-model="showImage" seamless>
    <q-card>
      <q-card-section class="row items-center q-pa-md">
        <div
          class="text-h6 col-10"
          style="white-space: nowrap; text-overflow: ellipsis"
        >
          {{ props.itemInfo.name }}
        </div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>
      <q-separator></q-separator>
      <q-card-section class="q-pa-md flex justify-center">
        <img style="width: 400px" :src="props.itemInfo.image" alt="" />
      </q-card-section>
    </q-card>
  </q-dialog>

  <q-dialog v-model="showRemoveItemDialog">
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
import { computed, ref } from "vue";

const props = defineProps(["itemInfo", "cellsWidth"]);
const $q = useQuasar();
const showImageTooltip = computed(() => {
  return showImage.value ? "Сховати зображення" : "Показати зображення";
});

let showItemName = ref(true);
let showImage = ref(false);
let showRemoveItemDialog = ref(false);

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
</script>

<style scoped>
.item {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 3px;
  width: 100%;
}
.item-cell {
  padding: 2px 16px;
  height: 40px;
  display: flex;
  align-items: center;
  box-sizing: content-box;
  overflow: hidden;
}
.item-text {
  white-space: nowrap;
  text-overflow: ellipsis;
}
.item-menu-buttons {
  height: 100%;
  width: fit-content;
  transition: all 0.5s ease-in-out;
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
  --icon-width: 28px;
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
.item-color {
  width: 100%;
  height: 32px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 5px;
  cursor: pointer;
}
</style>
