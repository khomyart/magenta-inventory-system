<template>
  <div class="col-12 q-mb-md">
    <div
      class="text-h6 q-mb-md q-mb-sm-sm text-weight-medium text-left q-pl-md"
    >
      Склад
    </div>
    <div class="row q-col-gutter-md q-mb-sm">
      <q-select
        dense
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
        dense
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
        dense
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
          dense
          outlined
          class="col-12 col-sm-6 q-pb-sm"
        />
        <q-input
          outlined
          dense
          class="col-12 col-sm-6"
          label="Назва причини"
          v-model="sectionStore.outcome.additionalReasonName"
          v-if="sectionStore.outcome.reasonName === 'other'"
          :rules="[
            (val) => val.length > 0 || 'Введіть назву причини',
            (val) => val.length < 255 || 'Не більше 255 символів',
          ]"
        />
        <q-input
          outlined
          dense
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
      <div class="row q-col-gutter-md">
        <q-select
          dense
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
          class="col-12"
          :loading="loadingStates.items === true"
          hide-dropdown-icon
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
                class="col-12 row item-component items-center"
                :class="{
                  'active-item-component': isItemExistedInList(scope.opt.id),
                }"
              >
                <div class="img-component-holder">
                  <img
                    v-if="scope.opt.image"
                    :src="`${appConfigStore.imagesStoreUrl}/${scope.opt.image}`"
                    alt=""
                  />
                </div>
                <div class="q-pl-md description d-flex column">
                  <div class="title text-subtitle1">{{ scope.opt.title }}</div>
                  <div class="size-and-color row">
                    <div class="article q-mr-xs">
                      {{ scope.opt.article }}
                    </div>
                    <div
                      v-if="scope.opt.color_value"
                      class="color q-mr-xs"
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
                  <div class="amount q-mt-sm">
                    Наявність: {{ scope.opt.amount }}
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
      <div class="row col-12" v-if="sectionStore.outcome.items.length > 0">
        <div class="col-12 items-wrapper q-pa-md q-mb-md">
          <div class="q-gutter-lg col-12">
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

function isItemExistedInList(itemId) {
  let items = sectionStore.outcome.items;

  return items.filter((item) => item.id === itemId).length > 0 ? true : false;
}

function addSelectedItemToStore(val) {
  let isValueExist = isItemExistedInList(val.id);

  if (!isValueExist) {
    sectionStore.outcome.items.push(val);
  }

  tempItemHolder.value = {};
}
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

.title {
  /* width: 110px; */
  width: 100%;
  max-width: 600px;
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
.items-wrapper {
  border: 1px solid rgba(96, 0, 92, 0.18);
  border-radius: 4px;
}
</style>
