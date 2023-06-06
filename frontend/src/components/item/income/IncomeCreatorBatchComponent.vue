<template>
  <div class="col-12 batch-wrapper q-mb-md q-pt-md q-px-md">
    <div class="row q-col-gutter-lg q-pb-sm">
      <q-input
        class="col-4"
        label="Кількість"
        outlined
        dense
        v-model="
          sectionStore.income[props.warehouseIndex].batches[props.index].amount
        "
        type="number"
        :rules="[
          (val) => (val !== null && val !== '') || 'Введіть кількість',
          (val) => val >= 1 || 'Не менше 1',
        ]"
      ></q-input>
      <q-input
        class="col-3"
        label="Ціна"
        outlined
        dense
        v-model="
          sectionStore.income[props.warehouseIndex].batches[props.index].price
        "
        type="number"
        :rules="[
          (val) => (val !== null && val !== '') || 'Введіть ціну',
          (val) => val >= 1 || 'Не менше 1',
        ]"
      ></q-input>
      <q-select
        class="col-3"
        dense
        hide-dropdown-icon
        outlined
        v-model="
          sectionStore.income[props.warehouseIndex].batches[props.index]
            .currency
        "
        label="Валюта"
        :options="['UAH', 'USD', 'EUR']"
      />
      <div class="col-2 d-flex items-start">
        <q-btn
          class="q-mr-sm"
          flat
          icon="remove"
          style="height: 39px; width: 100%"
          @click="removeBatch"
        >
          <q-tooltip
            class="bg-black text-body2"
            anchor="bottom middle"
            self="top middle"
            :offset="[0, 5]"
          >
            Видалити партію
          </q-tooltip>
        </q-btn>
      </div>
    </div>
    <div
      class="row q-col-gutter-lg"
      :class="{
        'q-pb-sm':
          sectionStore.income[props.warehouseIndex].batches[props.index]
            .items == 0,
      }"
    >
      <q-select
        dense
        autocomplete="false"
        outlined
        use-input
        hide-selected
        v-model="
          sectionStore.income[props.warehouseIndex].batches[props.index].items
        "
        label="Артикль предмета"
        input-debounce="400"
        :options="sectionStore.itemsFoundByArticle.data"
        @filter="itemArticleFilter"
        class="col-12"
        :rules="[(val) => val.length >= 1 || 'Оберіть хоча б один предмет']"
        hide-dropdown-icon
      >
        <template v-slot:append>
          <q-avatar>
            <q-icon size="23px" name="search"></q-icon>
          </q-avatar>
        </template>
        <template v-slot:option="scope">
          <q-item v-bind="scope.itemProps">
            <div class="col-12 row item-component items-center">
              <div class="img-component-holder">
                <img
                  v-if="scope.opt.image"
                  :src="`${props.imagesStoreUrl}/${scope.opt.image}`"
                  alt=""
                />
              </div>
              <div class="q-pl-md description d-flex column">
                <div class="title">{{ scope.opt.title }}</div>
                <div class="size-and-color row q-pt-xs">
                  <div class="article q-mr-sm">
                    {{ scope.opt.article }}
                  </div>
                  <div
                    v-if="scope.opt.color_value"
                    class="color q-mr-sm"
                    :style="{ backgroundColor: scope.opt.color_value }"
                  >
                    <span :style="{ color: scope.opt.text_color_value }">{{
                      scope.opt.color_article
                    }}</span>
                  </div>
                  <div v-if="scope.opt.size" class="size">
                    {{ scope.opt.size }}
                  </div>
                </div>
              </div>
            </div>
          </q-item>
        </template>
        <template v-slot:after-options>
          <div class="col-12 q-py-sm q-px-md d-flex column items-end">
            <div>
              Всього:
              {{ sectionStore.itemsFoundByArticle.amountOfItems }}
            </div>
            <div>
              Відображено:
              {{ sectionStore.itemsFoundByArticle.data.length }}
            </div>
          </div>
        </template>
      </q-select>
    </div>
    <div
      class="row col-12"
      v-if="
        sectionStore.income[props.warehouseIndex].batches[props.index].items
          .length > 0
      "
    >
      <div class="col-12 items-wrapper q-pa-md q-mb-md">
        <div class="q-gutter-sm row">
          <div
            v-for="(item, itemIndex) in sectionStore.income[
              props.warehouseIndex
            ].batches[props.index].items"
            :key="item.id"
            class="item-chip q-py-xs q-pl-md q-pr-sm row d-flex items-center"
          >
            <q-tooltip
              class="bg-black text-body2"
              anchor="bottom middle"
              self="top middle"
              :offset="[0, 5]"
            >
              {{ item.title }}
            </q-tooltip>
            <div class="item-chip-article q-mr-sm">{{ item.article }}</div>
            <div v-if="item.size" class="item-chip-size q-mr-sm">
              {{ item.size }}
            </div>
            <div
              v-if="item.color_value"
              class="item-chip-color q-mr-xs"
              :style="{ backgroundColor: item.color_value }"
            ></div>
            <q-btn
              round
              color="grey"
              flat
              size="8px"
              icon="close"
              dense
              @click="removeItem(itemIndex)"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { useItemStore } from "src/stores/itemStore";
const sectionStore = useItemStore();
const props = defineProps(["warehouseIndex", "index", "imagesStoreUrl"]);

function itemArticleFilter(val, update, abort) {
  update(() => {
    console.log(val);
    if (val.length > 0) {
      sectionStore.itemsFoundByArticle.data = [];
      sectionStore.receiveItemsByArticle(val);
    }
  });
}

function removeBatch() {
  sectionStore.income[props.warehouseIndex].batches.splice(props.index, 1);
}
function removeItem(itemIndex) {
  sectionStore.income[props.warehouseIndex].batches[props.index].items.splice(
    itemIndex,
    1
  );
}
</script>
<style scoped>
.batch-wrapper {
  border: 1px solid rgba(96, 0, 92, 0.18);
  border-radius: 4px;
}
.item-component {
}

.img-component-holder {
  width: 50px;
  height: 50px;
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
}
.item-chip-color {
  height: 15px;
  width: 15px;
  border-radius: 3px;
}
</style>
