<template>
  <div class="list-item q-pa-md col-12 column">
    <div class="row items-center">
      <div class="row">
        <div class="img-component-holder">
          <img
            v-if="props.itemDetail.image"
            :src="`${props.imagesStoreUrl}/${props.itemDetail.image}`"
            alt=""
          />
        </div>
        <div class="q-pl-md description d-flex column">
          <div class="title text-subtitle1">{{ props.itemDetail.title }}</div>
          <div class="size-and-color row">
            <div class="article q-mr-xs">
              {{ props.itemDetail.article }}
            </div>
            <div
              v-if="props.itemDetail.color_value"
              class="color q-mr-xs"
              :style="{ backgroundColor: props.itemDetail.color_value }"
            >
              <span :style="{ color: props.itemDetail.text_color_value }">{{
                props.itemDetail.color_article
              }}</span>
            </div>
            <div v-if="props.itemDetail.size" class="size">
              {{ props.itemDetail.size }}
            </div>
          </div>
          <div class="amount q-mt-sm">
            Наявність: {{ props.itemDetail.amount }}
          </div>
        </div>
      </div>
      <q-space></q-space>
      <div class="row buttons-holder">
        <q-btn icon="edit" rounded flat>
          <q-tooltip
            class="bg-black text-body2"
            anchor="bottom middle"
            self="top middle"
            :offset="[0, 5]"
          >
            Вказати кількість
          </q-tooltip>
        </q-btn>
        <q-btn icon="edit_note" rounded flat>
          <q-tooltip
            class="bg-black text-body2"
            anchor="bottom middle"
            self="top middle"
            :offset="[0, 5]"
          >
            Вказати причину
          </q-tooltip>
        </q-btn>
        <q-btn icon="delete" rounded flat @click="removeItem(props.itemIndex)">
          <q-tooltip
            class="bg-black text-body2"
            anchor="bottom middle"
            self="top middle"
            :offset="[0, 5]"
          >
            Видалити
          </q-tooltip>
        </q-btn>
      </div>
    </div>
    <q-separator class="q-mt-sm"></q-separator>
    <div class="q-mt-sm">
      <div>Вказана кількість на списання:</div>
      <div class="q-mt-sm">Вказана причина списання:</div>
    </div>
  </div>
</template>
<script setup>
import { useItemStore } from "src/stores/itemStore";
const sectionStore = useItemStore();

const props = defineProps(["itemDetail", "itemIndex", "imagesStoreUrl"]);

function removeItem(itemIndex) {
  sectionStore.outcome.items.splice(itemIndex, 1);
}
</script>
<style scoped>
.list-item {
  height: fit-content;
  border-radius: 5px;
  border: 1px solid rgba(96, 0, 92, 0.18);
}
.item-chip-color {
  height: 15px;
  width: 15px;
  border-radius: 3px;
}
.title {
  max-width: 200px;
  /* width: 100%; */
  display: block;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.img-component-holder {
  width: 75px;
  height: 75px;
  overflow: hidden;
  display: flex;
  justify-content: center;
  border-radius: 5px;
  background-color: rgb(217, 217, 205);
}
.img-component-holder img {
  height: 100%;
  width: auto;
}
.color {
  width: fit-content;
  padding: 0 10px;
  height: 20px;
  border-radius: 3px;
  font-size: 0.8em;
  display: flex;
  align-items: center;
}

.buttons-holder {
  height: fit-content;
}
.buttons-holder button {
  width: 42px;
  height: 42px;
}
</style>
