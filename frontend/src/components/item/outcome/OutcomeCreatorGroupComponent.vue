<template>
  <div class="col-12">
    <div
      class="text-h6 q-mb-md q-mb-sm-sm text-weight-medium text-left q-pl-md"
    >
      Склад
    </div>
    <div class="row q-gutter-md q-mb-md">
      <div
        class="favorite-warehouse-button q-pa-sm"
        @click="fillWarehouseFromFavorite(index)"
        v-for="(warehouseInfo, index) in warehouseStore.favoriteWarehouses"
        :key="index"
      >
        {{ warehouseInfo.warehouse.name }} ({{ warehouseInfo.city.name }},
        {{ warehouseInfo.warehouse.address }} )
      </div>
    </div>
    <div class="row q-col-gutter-md q-mb-sm">
      <q-select
        autocomplete="false"
        :hide-dropdown-icon="sectionStore.outcome.country != null"
        outlined
        v-model="sectionStore.outcome.country"
        use-input
        hide-selected
        fill-input
        label="Країна"
        input-debounce="400"
        :options="countryStore.items"
        option-value="id"
        option-label="name"
        @update:model-value="countryUpdate"
        @filter="countryFilter"
        :loading="loadingStates.country"
        class="col-12 col-sm-4 q-pt-sm q-pt-sm-md"
        :rules="[
          () =>
            (sectionStore.outcome.country != null &&
              sectionStore.outcome.country.id != undefined) ||
            'Оберіть країну',
        ]"
      >
        <template
          v-if="sectionStore.outcome.country && !loadingStates.country"
          v-slot:append
        >
          <q-icon
            name="cancel"
            @click.stop.prevent="
              sectionStore.outcome.country = null;
              countryUpdate();
            "
            class="cursor-pointer"
          />
        </template>
      </q-select>
      <q-select
        autocomplete="false"
        :hide-dropdown-icon="
          sectionStore.outcome.country == null ||
          sectionStore.outcome.country.id == undefined ||
          sectionStore.outcome.city != null
        "
        outlined
        v-model="sectionStore.outcome.city"
        label="Місто"
        use-input
        hide-selected
        fill-input
        input-debounce="400"
        :options="cityStore.items"
        option-value="id"
        option-label="name"
        @update:model-value="cityUpdate"
        @filter="cityFilter"
        :loading="loadingStates.city"
        class="col-12 col-sm-4 q-pt-sm q-pt-sm-md"
        :disable="
          sectionStore.outcome.country == null ||
          sectionStore.outcome.country.id == undefined
        "
        :rules="[
          () =>
            (sectionStore.outcome.city != null &&
              sectionStore.outcome.city.id != undefined) ||
            'Оберіть місто',
        ]"
      >
        <template
          v-if="sectionStore.outcome.city && !loadingStates.city"
          v-slot:append
        >
          <q-icon
            name="cancel"
            @click.stop.prevent="
              sectionStore.outcome.city = null;
              cityUpdate();
            "
            class="cursor-pointer"
          />
        </template>
      </q-select>
      <q-select
        autocomplete="false"
        :hide-dropdown-icon="
          sectionStore.outcome.city == null ||
          sectionStore.outcome.city.id == undefined ||
          sectionStore.outcome.warehouse != null
        "
        outlined
        v-model="sectionStore.outcome.warehouse"
        label="Склад"
        use-input
        hide-selected
        fill-input
        input-debounce="400"
        :options="warehouseStore.simpleItems"
        option-value="id"
        option-label="name"
        @filter="warehouseFilter"
        @update:model-value="warehouseUpdate"
        :loading="loadingStates.warehouse"
        class="col-12 col-sm-4 q-pt-sm q-pt-sm-md"
        :disable="
          sectionStore.outcome.city == null ||
          sectionStore.outcome.city.id == undefined
        "
        :rules="[
          () =>
            (sectionStore.outcome.warehouse != null &&
              sectionStore.outcome.warehouse.id != undefined) ||
            'Оберіть склад',
        ]"
      >
        <template v-slot:option="scope">
          <q-item v-bind="scope.itemProps" class="flex items-center">
            {{ scope.opt.name }} ({{ scope.opt.address }})
          </q-item>
        </template>
        <template
          v-if="sectionStore.outcome.warehouse && !loadingStates.warehouse"
          v-slot:append
        >
          <q-icon
            name="cancel"
            @click.stop.prevent="sectionStore.outcome.warehouse = null"
            class="cursor-pointer"
          />
        </template>
      </q-select>
    </div>

    <div class="q-mt-sm" v-if="sectionStore.outcome.warehouse != null">
      <q-separator class="q-mb-md" />
      <div
        class="text-h6 q-mb-sm q-mb-sm-sm text-weight-medium text-left q-pl-md"
      >
        Причина
      </div>
      <div class="row q-col-gutter-md">
        <q-select
          v-model="sectionStore.outcome.reasonName"
          :options="reasons"
          label="Оберіть причину"
          emit-value
          map-options
          outlined
          class="col-12 col-sm-6 q-pb-sm"
        />
        <q-input
          outlined
          class="col-12 col-sm-6"
          label="Назва причини"
          v-model="sectionStore.outcome.additionalReasonName"
          v-if="sectionStore.outcome.reasonName === 'other'"
          :reactive-rules="true"
          :rules="
            isAllItemsLockedToCustomValues().reason
              ? [(val) => true]
              : [
                  (val) => val.length > 0 || 'Введіть назву причини',
                  (val) => val.length < 255 || 'Не більше 255 символів',
                ]
          "
        />
        <q-input
          outlined
          label="Деталі"
          v-model="sectionStore.outcome.reasonDetail"
          :class="{
            'col-12 col-sm-6': sectionStore.outcome.reasonName != 'other',
            'col-12 q-pt-xs col-sm-12 q-pt-sm-sm':
              sectionStore.outcome.reasonName === 'other',
          }"
        />
      </div>
    </div>

    <div class="q-mt-lg" v-if="sectionStore.outcome.warehouse != null">
      <q-separator class="q-mb-md" />
      <div
        class="text-h6 q-mb-sm q-mb-sm-sm text-weight-medium text-left q-pl-md"
      >
        Предмети
      </div>
      <div id="article_select_input" class="row q-col-gutter-md">
        <q-select
          autocomplete="false"
          outlined
          use-input
          hide-selected
          v-model="tempItemHolder"
          label="Артикль предмета"
          input-debounce="400"
          :options="sectionStore.itemsFoundByArticle.data"
          @filter="itemArticleFilter"
          @update:model-value="addSelectedItemToStore"
          :loading="loadingStates.items === true"
          hide-dropdown-icon
          class="col-12 col-sm-7"
          :rules="[
            () =>
              sectionStore.outcome.items.length >= 1 ||
              'Оберіть хоча б один предмет',
          ]"
        >
          <template v-slot:append v-if="loadingStates.items === false">
            <q-avatar>
              <q-icon size="23px" name="search"></q-icon>
            </q-avatar>
          </template>
          <template v-slot:option="scope">
            <q-item v-bind="scope.itemProps">
              <div
                class="list-item-body"
                :class="{
                  'active-item-component': isItemExistInList(scope.opt.id),
                }"
              >
                <div class="list-item-description-image-holder">
                  <img
                    v-if="scope.opt.image != null"
                    class="list-item-description-image"
                    :src="`${appConfigStore.imagesStoreUrl}/${scope.opt.image}`"
                  />
                </div>
                <div class="list-item-description-text-holder q-ml-md">
                  <div class="title">
                    {{ scope.opt.title }}
                  </div>
                  <div class="article">
                    <div class="article-text q-mr-xs">
                      {{ scope.opt.article }}
                    </div>
                    <div
                      v-if="scope.opt.color_value"
                      class="article-color q-mr-xs"
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
                  <div class="available">Наявність: {{ scope.opt.amount }}</div>
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
        <q-input
          outlined
          class="col-12 col-sm-5 q-pt-sm q-pt-sm-md"
          label="Кількість"
          step="1"
          v-model="sectionStore.outcome.outcomeAmount"
          :reactive-rules="true"
          :rules="
            isAllItemsLockedToCustomValues().amount
              ? [(val) => true]
              : [
                  (val) => val != '' || 'Вкажіть кількість',
                  (val) => val > 0 || 'Не менше 1',
                ]
          "
          mask="######"
        />
      </div>
      <div class="row col-12" v-if="sectionStore.outcome.items.length > 0">
        <div
          class="col-12 items-wrapper q-px-md q-pt-lg q-pb-md q-mb-sm q-mt-sm q-mt-sm-sm"
        >
          <div class="q-gutter-md">
            <OutcomeCreatorGroupItemComponent
              v-for="(item, itemIndex) in sectionStore.outcome.items"
              :key="item.id"
              :itemDetail="item"
              :itemIndex="itemIndex"
              :imagesStoreUrl="appConfigStore.imagesStoreUrl"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { watch, onMounted, onBeforeUnmount, reactive } from "vue";
import { useCityStore } from "src/stores/helpers/cityStore";
import { useCountryStore } from "src/stores/helpers/countryStore";
import { useItemStore } from "src/stores/itemStore";
import { useWarehouseStore } from "src/stores/warehouseStore";
import { useAppConfigStore } from "src/stores/appConfigStore";
import OutcomeCreatorGroupItemComponent from "./OutcomeCreatorGroupItemComponent.vue";
const appConfigStore = useAppConfigStore();
const sectionStore = useItemStore();
const countryStore = useCountryStore();
const cityStore = useCityStore();
const warehouseStore = useWarehouseStore();

const props = defineProps(["index"]);
const additionalOutcomeInfoTemplate = {
  reasonName: "sell",
  additionalReasonName: "",
  reasonDetail: "",
  outcomeAmount: "",
  outcomeAmountCustomMode: false,
  reasonCustomMode: false,
};
//makes possible to do loading animation for every individual
//set of inputs (co., ci., wa.)
let loadingStates = reactive({
  country: false,
  city: false,
  warehouse: false,
  items: false,
});

let reasons = [
  {
    label: "Продаж",
    value: "sell",
  },
  {
    label: "Утилізація",
    value: "recycle",
  },
  {
    label: "Інша",
    value: "other",
  },
];

let tempItemHolder = reactive({});

function fillWarehouseFromFavorite(index) {
  sectionStore.outcome.country =
    warehouseStore.favoriteWarehouses[index].country;
  sectionStore.outcome.city = warehouseStore.favoriteWarehouses[index].city;
  sectionStore.outcome.warehouse =
    warehouseStore.favoriteWarehouses[index].warehouse;
}

function countryFilter(val, update, abort) {
  update(() => {
    loadingStates.country = true;
    countryStore.items = [];
    countryStore.receive(val, loadingStates);
  });
}

function cityFilter(val, update, abort) {
  update(() => {
    loadingStates.city = true;
    cityStore.items = [];
    cityStore.receive(sectionStore.outcome.country.id, val, loadingStates);
  });
}

function warehouseFilter(val, update, abort) {
  update(() => {
    loadingStates.warehouse = true;
    warehouseStore.simpleItems = [];
    warehouseStore.simpleReceive(
      val,
      sectionStore.outcome.city.id,
      loadingStates
    );
  });
}

//if changing country - clear city and warehouse
function countryUpdate() {
  sectionStore.outcome.city = null;
  sectionStore.outcome.warehouse = null;
}

//if changing city - clear warehouse
function cityUpdate() {
  sectionStore.outcome.warehouse = null;
}

//if changing warehouse - clear items
function warehouseUpdate() {
  sectionStore.outcome.items = [];
}

function itemArticleFilter(val, update, abort) {
  sectionStore.itemsFoundByArticle.data = [];
  update(() => {
    if (val.length > 0) {
      sectionStore.receiveItemsByArticle(
        val,
        loadingStates,
        sectionStore.outcome.warehouse.id
      );
    }
  });
}

function isItemExistInList(itemId) {
  let items = sectionStore.outcome.items;

  return items.filter((item) => item.id === itemId).length > 0 ? true : false;
}

function addSelectedItemToStore(val) {
  let isValueExist = isItemExistInList(val.id);

  if (!isValueExist) {
    let additionalInfoInjectionTargetIndex = sectionStore.outcome.items.length;
    sectionStore.outcome.items.push(val);

    Object.keys(additionalOutcomeInfoTemplate).forEach((key) => {
      sectionStore.outcome.items[additionalInfoInjectionTargetIndex][key] =
        sectionStore.outcome[key];
    });
  }

  tempItemHolder.value = {};
}
/**
 * Check are all items where modified by their inner dialog window.
 * If so, their values are specific to themselves and they have no need in
 * general, top-level value of group, like general "amount", or "reason"
 */
function isAllItemsLockedToCustomValues() {
  let amountOfCustomAmountElements = sectionStore.outcome.items.filter(
    (el) => el.outcomeAmountCustomMode === true
  );
  let amountOfCustomReasonElements = sectionStore.outcome.items.filter(
    (el) => el.reasonCustomMode === true
  );

  return {
    amount:
      amountOfCustomAmountElements.length === sectionStore.outcome.items.length
        ? true
        : false,
    reason:
      amountOfCustomReasonElements.length === sectionStore.outcome.items.length
        ? true
        : false,
  };
}

watch(
  () => loadingStates.items,
  (newState, oldState) => {
    //if items loading is done, item list appeared
    //so we can set proper width for it
    if (newState === false) {
      let articleSelectInput = document.querySelector("#article_select_input");
      let listMenu = document.querySelector("[role='listbox']");

      if (listMenu != null) {
        listMenu.style.width = `${articleSelectInput.offsetWidth - 16}px`;
      }
    }
  }
);
</script>
<style scoped>
.group-wrapper {
  border: 1px solid rgba(0, 0, 0, 0.185);
  box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.158);
  border-radius: 4px;
  padding: 15px 15px 0px;
}
.active-item-component {
  color: #a32cc7;
}
.list-item-body {
  display: flex;
  flex-direction: row;
  flex: 1 1 auto;
  min-width: 0;
}
.list-item-description-image-holder {
  min-width: 75px;
  min-height: 75px;
  width: 75px;
  height: 75px;
  overflow: hidden;
  display: flex;
  justify-content: center;
  border-radius: 5px;
  background-color: rgb(217, 217, 205);
  flex: 0 0 75px;
}
.list-item-description-image {
  height: 100%;
  width: auto;
}
.list-item-description-text-holder {
  display: flex;
  flex: 1 1 auto;
  min-width: 0px;
  flex-direction: column;
  justify-content: space-between;
}
.title {
  width: 100%;
  height: fit-content;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.article {
  display: flex;
  flex-direction: row;
}
.article-color {
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

.favorite-warehouse-button {
  border-radius: 5px;
  border: 1px solid rgb(0, 0, 0, 0.18);
  cursor: pointer;
  transition: all 0.2s ease-in-out;
}
.favorite-warehouse-button:hover {
  background-color: #b53cda;
  color: white;
}
</style>
