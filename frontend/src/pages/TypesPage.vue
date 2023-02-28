<template>
  <div class="page">
    <div class="toolbar row q-px-md q-mt-md">
      <q-input
        v-model="appConfigStore.filters.types.selectedParams.name.value"
        debounce="700"
        outlined
        placeholder="Введіть назву виду"
        rounded
        dense
        class="col-4 q-mr-md"
        :loading="typeStore.data.isTypesLoading"
      >
        <template v-slot:append v-if="!typeStore.data.isTypesLoading">
          <q-icon name="search" />
        </template>
      </q-input>
      <q-btn flat round color="black" icon="add" @click="showCreateDialog">
        <q-tooltip
          anchor="bottom left"
          :offset="[-20, 7]"
          class="bg-black text-body2"
        >
          Створити
        </q-tooltip>
      </q-btn>
      <q-btn flat round color="black" icon="sync" @click="typeStore.receive()">
        <q-tooltip
          anchor="bottom left"
          :offset="[-20, 7]"
          class="bg-black text-body2"
        >
          Оновити список
        </q-tooltip>
      </q-btn>
    </div>

    <div class="content">
      <q-inner-loading :showing="typeStore.data.isTypesLoading">
        <q-spinner-gears size="50px" color="primary" />
      </q-inner-loading>
      <q-toolbar class="text-black filter q-px-none q-py-md bg-white">
        <q-btn icon="filter_list" round flat style="margin: 0px 5px 0px 11px">
          <q-badge
            v-if="filterOrder.field === 'id'"
            color="red"
            floating
            style="margin-top: -4px"
            ><q-icon
              size="14px"
              :name="
                filterOrder.field === 'id'
                  ? filterOrder.value === 'asc'
                    ? 'expand_less'
                    : 'expand_more'
                  : ''
              "
            />
          </q-badge>
          <q-tooltip
            anchor="bottom left"
            :offset="[55, -37]"
            class="bg-black text-body2"
          >
            Сортування
          </q-tooltip>
          <q-menu self="top middle" :offset="[-24, 8]">
            <q-list style="width: 270px">
              <q-item
                v-ripple
                clickable
                :active="
                  filterOrder.field === 'id' && filterOrder.value === 'desc'
                "
                @click="setFilterOrder('id', 'desc')"
                active-class="text-purple"
              >
                <q-item-section>Від новішого до старішого</q-item-section>
                <q-item-section
                  v-if="
                    filterOrder.field === 'id' && filterOrder.value === 'desc'
                  "
                  avatar
                >
                  <q-icon name="done" />
                </q-item-section>
              </q-item>
              <q-item
                v-ripple
                clickable
                :active="
                  filterOrder.field === 'id' && filterOrder.value === 'asc'
                "
                @click="setFilterOrder('id', 'asc')"
                active-class="text-purple"
              >
                <q-item-section>Від старішого до новішого</q-item-section>
                <q-item-section
                  v-if="
                    filterOrder.field === 'id' && filterOrder.value === 'asc'
                  "
                  avatar
                >
                  <q-icon name="done" />
                </q-item-section>
              </q-item>
              <q-separator></q-separator>
              <q-item v-ripple clickable v-close-popup @click="clearAllFilters">
                <q-item-section>Скинути усі фільтри</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
        <div class="filter-separator">
          <div class="vertical-line"></div>
        </div>
        <q-btn flat stretch class="filter-button">
          <q-badge
            v-if="
              filterLabels.article.value != '' ||
              filterLabels.article.order != ''
            "
            color="red"
            class="q-mr-sm"
            floating
            style="height: 19px"
            align="middle"
            ><span v-if="filterLabels.article.value != ''">
              {{ filterLabels.article.mode }}
            </span>
            <span
              style="margin-left: 4px"
              v-if="
                filterLabels.article.value != '' &&
                filterLabels.article.order != ''
              "
            ></span>
            <q-icon
              v-if="filterLabels.article.order != ''"
              size="14px"
              :name="
                filterLabels.article.order == 'asc'
                  ? 'expand_less'
                  : 'expand_more'
              "
          /></q-badge>
          <div
            :style="`min-width: ${fieldWidths.article}px; text-align: start`"
          >
            Артикль
          </div>

          <q-menu
            self="bottom middle"
            :offset="[-fieldWidths.article / 2 - 16, -55]"
          >
            <q-inner-loading :showing="typeStore.data.isTypesLoading">
              <q-spinner-gears size="50px" color="primary" />
            </q-inner-loading>
            <div style="min-width: 250px; min-height: fit-content">
              <div class="row justify-end q-mb-sm">
                <q-btn flat v-close-popup dense icon="close"></q-btn>
              </div>
              <div class="row">
                <div class="filter-body col-12 q-px-md">
                  <q-input
                    class="col-12 q-mb-md"
                    outlined
                    v-model="
                      appConfigStore.filters.types.selectedParams.article.value
                    "
                    placeholder="Значення артиклю"
                    dense
                    debounce="700"
                  />
                  <q-select
                    class="col-12 q-mb-md"
                    dense
                    outlined
                    v-model="
                      appConfigStore.filters.types.selectedParams.article
                        .filterMode
                    "
                    :options="appConfigStore.filters.availableParams.items"
                    @update:model-value="onChangedFieldFilterMode('article')"
                  >
                  </q-select>
                  <div class="row justify-between q-mb-md">
                    <q-btn
                      class="q-px-md"
                      style="width: 46%"
                      :color="
                        filterOrder.field === 'article' &&
                        filterOrder.value === 'asc'
                          ? 'primary'
                          : 'white'
                      "
                      :text-color="
                        filterOrder.field === 'article' &&
                        filterOrder.value === 'asc'
                          ? 'white'
                          : 'black'
                      "
                      @click="setFilterOrder('article', 'asc')"
                      ><q-icon name="arrow_upward"
                    /></q-btn>
                    <q-btn
                      class="q-px-md"
                      style="width: 46%"
                      :color="
                        filterOrder.field === 'article' &&
                        filterOrder.value === 'desc'
                          ? 'primary'
                          : 'white'
                      "
                      :text-color="
                        filterOrder.field === 'article' &&
                        filterOrder.value === 'desc'
                          ? 'white'
                          : 'black'
                      "
                      @click="setFilterOrder('article', 'desc')"
                      ><q-icon name="arrow_downward"
                    /></q-btn>
                  </div>
                  <div class="row q-mb-md">
                    <q-btn
                      v-close-popup
                      class="col-12"
                      @click="clearFilter('article')"
                      >Скинути</q-btn
                    >
                  </div>
                </div>
              </div>
            </div>
          </q-menu>
        </q-btn>
        <div class="filter-separator" name="article">
          <div class="vertical-line"></div>
        </div>
        <q-btn flat stretch class="filter-button">
          <q-badge
            v-if="
              filterLabels.name.value != '' || filterLabels.name.order != ''
            "
            color="red"
            class="q-mr-sm"
            floating
            style="height: 19px"
            align="middle"
            ><span v-if="filterLabels.name.value != ''">
              {{ filterLabels.name.mode }}
            </span>
            <span
              style="margin-left: 4px"
              v-if="
                filterLabels.name.value != '' && filterLabels.name.order != ''
              "
            ></span>
            <q-icon
              v-if="filterLabels.name.order != ''"
              size="14px"
              :name="
                filterLabels.name.order == 'asc' ? 'expand_less' : 'expand_more'
              "
          /></q-badge>
          <div
            :style="`min-width: ${
              fieldWidths.name - filterWidthSettings.options.xScrollWidth
            }px; text-align: start`"
          >
            Назва
          </div>

          <q-menu
            self="bottom middle"
            :offset="[-fieldWidths.name / 2 - 16, -55]"
          >
            <q-inner-loading :showing="typeStore.data.isTypesLoading">
              <q-spinner-gears size="50px" color="primary" />
            </q-inner-loading>
            <div style="min-width: 250px; min-height: fit-content">
              <div class="row justify-end q-mb-sm">
                <q-btn flat v-close-popup dense icon="close"></q-btn>
              </div>
              <div class="row">
                <div class="filter-body col-12 q-px-md">
                  <q-input
                    class="col-12 q-mb-md"
                    outlined
                    v-model="
                      appConfigStore.filters.types.selectedParams.name.value
                    "
                    placeholder="Значення назви"
                    dense
                    debounce="700"
                  />
                  <q-select
                    class="col-12 q-mb-md"
                    dense
                    outlined
                    v-model="
                      appConfigStore.filters.types.selectedParams.name
                        .filterMode
                    "
                    :options="appConfigStore.filters.availableParams.items"
                    @update:model-value="onChangedFieldFilterMode('name')"
                  >
                  </q-select>
                  <div class="row justify-between q-mb-md">
                    <q-btn
                      class="q-px-md"
                      style="width: 46%"
                      :color="
                        filterOrder.field === 'name' &&
                        filterOrder.value === 'asc'
                          ? 'primary'
                          : 'white'
                      "
                      :text-color="
                        filterOrder.field === 'name' &&
                        filterOrder.value === 'asc'
                          ? 'white'
                          : 'black'
                      "
                      @click="setFilterOrder('name', 'asc')"
                      ><q-icon name="arrow_upward"
                    /></q-btn>
                    <q-btn
                      class="q-px-md"
                      style="width: 46%"
                      :color="
                        filterOrder.field === 'name' &&
                        filterOrder.value === 'desc'
                          ? 'primary'
                          : 'white'
                      "
                      :text-color="
                        filterOrder.field === 'name' &&
                        filterOrder.value === 'desc'
                          ? 'white'
                          : 'black'
                      "
                      @click="setFilterOrder('name', 'desc')"
                      ><q-icon name="arrow_downward"
                    /></q-btn>
                  </div>
                  <div class="row q-mb-md">
                    <q-btn
                      v-close-popup
                      class="col-12"
                      @click="clearFilter('name')"
                      >Скинути</q-btn
                    >
                  </div>
                </div>
              </div>
            </div>
          </q-menu>
        </q-btn>
        <div class="filter-separator" name="name">
          <div class="vertical-line"></div>
        </div>
      </q-toolbar>
      <table class="items">
        <tr>
          <td :width="60"></td>
          <td :width="filterWidthSettings.options.separatorWidth"></td>
          <td
            :width="
              fieldWidths.article +
              filterWidthSettings.options.filterButtonXPadding
            "
          ></td>
          <td :width="filterWidthSettings.options.separatorWidth"></td>
          <td
            :width="
              fieldWidths.name +
              4 +
              filterWidthSettings.options.filterButtonXPadding -
              filterWidthSettings.options.xScrollWidth
            "
          ></td>
        </tr>
        <template v-for="(item, index) in typeStore.items" :key="index">
          <type-component
            @show-remove-dialog="showRemoveDialog"
            @show-edit-dialog="showUpdateDialog"
            @clear-updated-item-id="clearUpdatedItemId"
            :itemInfo="item"
            :gap="5"
            :updated="item.id == typeStore.data.updatedItemId"
          ></type-component>
        </template>
      </table>
    </div>

    <div class="footer">
      <div class="footer-left-part flex items-center">
        <span class="q-mr-sm">Записів на сторінці</span>
        <q-select
          class="item-per-page-selector"
          outlined
          v-model="appConfigStore.amountOfItemsPerPages.types"
          :options="appConfigStore.availableAmaountOfItemsPerPage"
        />
        <q-separator vertical class="q-mx-md"></q-separator>
        <q-pagination
          v-model="appConfigStore.currentPages.types"
          color="purple"
          :max="typeStore.data.lastPage"
          :max-pages="6"
          boundary-numbers
        />
      </div>
      <div class="footer-right-part q-mr-md">
        Кількість: {{ typeStore.data.amountOfItems }}
      </div>
    </div>
    <!-- CREATION DIALOG -->
    <q-dialog v-model="typeStore.dialogs.create.isShown">
      <q-card>
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon name="interests" color="black" size="md" class="q-mr-sm" />
            Вид
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-form @submit="typeStore.create(newType)">
          <q-card-section
            style="max-height: 50vh; width: 300px"
            class="scroll q-pt-md"
          >
            <q-input
              class="q-mb-sm"
              outlined
              v-model="newType.article"
              autofocus
              label="Артикль"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть артикль',
                (val) => val.length <= 8 || 'Не більше 8 символів',
              ]"
            />
            <q-input
              outlined
              v-model="newType.name"
              label="Назва"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть назву',
                (val) => val.length <= 128 || 'Не більше 128 символів',
              ]"
            />
          </q-card-section>

          <q-separator />

          <q-card-actions align="right">
            <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
            <q-btn
              flat
              color="primary"
              type="submit"
              :loading="typeStore.dialogs.create.isLoading"
              ><b>Створити</b></q-btn
            >
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!--UPDATING DIALOG-->
    <q-dialog v-model="typeStore.dialogs.update.isShown">
      <q-card>
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon name="interests" color="black" size="md" class="q-mr-sm" />
            Вид
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-form @submit="typeStore.update(updatedType)">
          <q-card-section
            style="max-height: 50vh; width: 300px"
            class="scroll q-pt-md"
          >
            <q-input
              class="q-mb-sm"
              outlined
              v-model="updatedType.article"
              autofocus
              label="Артикль"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть артикль',
                (val) => val.length <= 8 || 'Не більше 8 символів',
              ]"
            />
            <q-input
              outlined
              v-model="updatedType.name"
              label="Назва"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть назву',
                (val) => val.length <= 128 || 'Не більше 128 символів',
              ]"
            />
          </q-card-section>

          <q-separator />

          <q-card-actions align="right">
            <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
            <q-btn
              flat
              color="primary"
              type="submit"
              :loading="typeStore.dialogs.update.isLoading"
              ><b>Редагувати</b></q-btn
            >
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- DELETING DIALOG -->
    <q-dialog v-model="typeStore.dialogs.delete.isShown">
      <q-card>
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon name="warning" color="red" size="md" class="q-mr-sm" />
            Видалення
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-card-section style="width: 350px">
          Ви справді бажаєте знищити вид: "{{ deletedType.name }}"?
        </q-card-section>
        <q-separator></q-separator>
        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn
            @click="typeStore.delete(deletedType.id)"
            flat
            type="submit"
            color="negative"
            :loading="typeStore.dialogs.delete.isLoading"
            ><b>Так</b></q-btn
          >
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from "vue";
import TypeComponent from "src/components/TypeComponent.vue";
import { useRouter } from "vue-router";
import { useTypeStore } from "src/stores/typeStore";
import { useAppConfigStore } from "src/stores/appConfigStore";

const appConfigStore = useAppConfigStore();
const typeStore = useTypeStore();
const fieldsSequance = ["article", "name"];
const router = useRouter();

let newType = reactive({
  article: "",
  name: "",
});

let updatedType = reactive({
  id: "",
  article: "",
  name: "",
});

let deletedType = reactive({
  id: "",
  name: "",
});

let searchInput = ref("");
let fieldWidths = reactive({
  //px
  article: 0,
  name: 0,
  icon: 0,
});
let tempFieldWidths = reactive({
  //px
  article: 0,
  name: 0,
});

let filterWidthSettings = {
  fieldMinWidths: {
    //px
    article: 100,
    name: 100,
  },
  fieldWidthsInPercentages: {
    //%
    article: 5,
    name: 5,
  },
  options: {
    //px
    minFilterWidth: 90,
    separatorWidth: 11,
    xScrollWidth: 20,
    filterButtonXPadding: 32,
    //affected || straight
    resizeMode: "straight",
  },
};

function clearUpdatedItemId() {
  let interval = setTimeout(() => {
    typeStore.data.updatedItemId = 0;
    clearInterval(interval);
  }, 2000);
}

function showCreateDialog() {
  newType.article = "";
  newType.name = "";
  typeStore.dialogs.create.isShown = true;
}

function showUpdateDialog(item) {
  updatedType.id = item.id;
  updatedType.article = item.article;
  updatedType.name = item.name;
  typeStore.dialogs.update.isShown = true;
}

function showRemoveDialog(id, name) {
  deletedType.id = id;
  deletedType.name = name;
  typeStore.dialogs.delete.isShown = true;
}

function setFilterOrder(field, fieldOrder) {
  let order = appConfigStore.filters.types.selectedParams.order;
  if (order.field === field && order.value === fieldOrder) {
    order.field = "";
    order.value = "";
    order.combined = "";
  } else {
    order.field = field;
    order.value = fieldOrder;
    order.combined = `${field}${fieldOrder}`;
  }
}

function clearFilter(field) {
  let filters = appConfigStore.filters;

  filters.types.selectedParams[field].value = "";
  filters.types.selectedParams[field].filterMode =
    filters.availableParams.items[0];

  if (filters.types.selectedParams.order.field === field) {
    filters.types.selectedParams.order.field = "";
    filters.types.selectedParams.order.value = "";
    filters.types.selectedParams.order.combined = "";
  }
}

function clearAllFilters() {
  let filters = appConfigStore.filters;

  for (const [key, value] of Object.entries(filters.types.selectedParams)) {
    filters.types.selectedParams[key].value = "";
    filters.types.selectedParams[key].filterMode =
      filters.availableParams.items[0];
  }

  filters.types.selectedParams.order.field = "";
  filters.types.selectedParams.order.value = "";
  filters.types.selectedParams.order.combined = "";
}

function onChangedFieldFilterMode(fieldName) {
  if (appConfigStore.filters.types.selectedParams[fieldName].value != "") {
    typeStore.receive();
  }
}

const filterOrder = computed(() => {
  return {
    field: appConfigStore.filters.types.selectedParams.order.field,
    value: appConfigStore.filters.types.selectedParams.order.value,
  };
});

const filterLabels = computed(() => {
  let articleFilter = appConfigStore.filters.types.selectedParams.article;
  let nameFilter = appConfigStore.filters.types.selectedParams.name;

  return {
    article: {
      value: articleFilter.value != "" ? "V" : "",
      mode: articleFilter.filterMode.shortName,
      order:
        appConfigStore.filters.types.selectedParams.order.field == "article"
          ? appConfigStore.filters.types.selectedParams.order.value == "asc"
            ? "asc"
            : "desc"
          : "",
    },
    name: {
      value: nameFilter.value != "" ? "V" : "",
      mode: nameFilter.filterMode.shortName,
      order:
        appConfigStore.filters.types.selectedParams.order.field == "name"
          ? appConfigStore.filters.types.selectedParams.order.value == "asc"
            ? "asc"
            : "desc"
          : "",
    },
  };
});

watch([() => appConfigStore.currentPages.types], ([currentPage]) => {
  router.push(`/types/${currentPage}`);
  typeStore.receive();
});

watch([() => appConfigStore.amountOfItemsPerPages.types], ([amountPerPage]) => {
  if (appConfigStore.currentPages.types != 1) {
    appConfigStore.currentPages.types = 1;
  } else {
    typeStore.receive();
  }
  router.push(`/types/${appConfigStore.currentPages.types}`);
});

//filter watcher
watch(
  [
    () => appConfigStore.filters.types.selectedParams.order.combined,
    () => appConfigStore.filters.types.selectedParams.article.value,
    () => appConfigStore.filters.types.selectedParams.name.value,
  ],
  () => {
    typeStore.receive();
  }
);

onMounted(() => {
  typeStore.items = [];
  appConfigStore.currentPages.types = Number(
    router.currentRoute.value.params.page
  );
  /*
    setting up default values for filter fields width according to config
  */
  let contentElement = document.querySelector(".content");
  //get .content div padding
  let contentWidth = contentElement.offsetWidth;
  let contentPaddingX =
    parseFloat(getComputedStyle(contentElement).paddingLeft) +
    parseFloat(getComputedStyle(contentElement).paddingRight);
  //get width of separator
  let separatorWidth = document.querySelector(".filter-separator").offsetWidth;

  let fieldNumber = 1;
  for (const fieldName in fieldWidths) {
    fieldWidths[fieldName] =
      contentWidth *
        (filterWidthSettings.fieldWidthsInPercentages[fieldName] / 100) -
      filterWidthSettings.options.filterButtonXPadding;
    //substracting value according to container padding devided by amount of filter parameters
    fieldWidths[fieldName] -= contentPaddingX / Object.keys(fieldWidths).length;
    //if filter item is not last -> substract width of separator
    if (Object.keys(fieldWidths).length != fieldNumber) {
      fieldWidths[fieldName] -= separatorWidth;
    }
    //set minimum width of fields in case, if calculated is less than established minimum
    if (
      fieldWidths[fieldName] < filterWidthSettings.fieldMinWidths[fieldName]
    ) {
      fieldWidths[fieldName] = filterWidthSettings.fieldMinWidths[fieldName];
    }

    fieldNumber += 1;
  }

  /*
    adding to all separators drag actions
  */
  let qApp = document.querySelector("#q-app");
  let pageContainer = document.querySelector(".content");
  let filter = document.querySelector(".filter");

  /**
   * @param {htmlObject} separatorObject
   * @param {string} fieldName
   * @param {string} affectedFieldName
   */
  function addEventToSeparator(
    separatorObject,
    fieldName,
    affectedFieldName = null
  ) {
    //display strip when column is moving
    function separatorMovementVisualisation() {
      let devider = document.createElement("div");
      devider.classList.add("filter-width-helper");
      devider.style.height = `${pageContainer.clientHeight}px`;
      pageContainer.appendChild(devider);
      devider.style.top = `${pageContainer.offsetTop}px`;
      devider.style.left = `${
        separatorObject.getBoundingClientRect().x -
        pageContainer.getBoundingClientRect().x +
        filterWidthSettings.options.separatorWidth / 2
      }px`;

      return devider;
    }

    separatorObject.onmousedown = (mouseDownEvent) => {
      separatorObject.onmouseup = () => {
        onSeparatorRelease();
        console.log("release");
      };
      document.body.onmouseup = () => {
        onSeparatorRelease();
      };
      //disabling interaction with other element except filter separator
      qApp.classList.add("disable-interaction");
      //applying current filter width values to temp filter object
      Object.keys(fieldWidths).forEach((field) => {
        tempFieldWidths[field] = fieldWidths[field];
      });

      let initCursorCoord = mouseDownEvent.pageX;
      let initFieldWidth = fieldWidths[fieldName];
      let initAffectedFieldWidth =
        affectedFieldName == null ? null : fieldWidths[affectedFieldName];
      let minFilterWidth = filterWidthSettings.options.minFilterWidth;
      //working with visualistion of separator movement
      let devider = separatorMovementVisualisation();
      let initDeviderOffsetLeft = devider.offsetLeft;

      function onSeparatorRelease() {
        devider.remove();
        document.body.onmousemove = null;
        document.body.onmouseup = null;
        qApp.classList.remove("disable-interaction");

        if (affectedFieldName != null) {
          //set field width according to minFilterWidth if satisfies the condition
          if (tempFieldWidths[fieldName] < minFilterWidth) {
            tempFieldWidths[fieldName] = minFilterWidth;
            tempFieldWidths[affectedFieldName] =
              initAffectedFieldWidth + (initFieldWidth - minFilterWidth);
          } else if (tempFieldWidths[affectedFieldName] < minFilterWidth) {
            tempFieldWidths[affectedFieldName] = minFilterWidth;
            tempFieldWidths[fieldName] =
              initFieldWidth + (initAffectedFieldWidth - minFilterWidth);
          }

          //bringing active value back to actual fieldWidth object
          fieldWidths[fieldName] = tempFieldWidths[fieldName];
          fieldWidths[affectedFieldName] = tempFieldWidths[affectedFieldName];
        } else {
          if (tempFieldWidths[fieldName] < minFilterWidth) {
            tempFieldWidths[fieldName] = minFilterWidth;
          }

          fieldWidths[fieldName] = tempFieldWidths[fieldName];
        }
      }

      document.body.onmousemove = (mouseMoveEvent) => {
        devider.style.left = `${
          initDeviderOffsetLeft + mouseMoveEvent.pageX - initCursorCoord
        }px`;

        tempFieldWidths[fieldName] =
          initFieldWidth + mouseMoveEvent.pageX - initCursorCoord;

        if (affectedFieldName != null) {
          tempFieldWidths[affectedFieldName] =
            initAffectedFieldWidth - mouseMoveEvent.pageX + initCursorCoord;
        }
      };
    };
  }

  for (let i = 0; i < fieldsSequance.length; i++) {
    let currentItem = document.querySelector(
      `.filter-separator[name='${fieldsSequance[i]}']`
    );

    if (filterWidthSettings.options.resizeMode == "straight") {
      addEventToSeparator(currentItem, fieldsSequance[i]);
    }

    if (filterWidthSettings.options.resizeMode == "affected") {
      if (i > fieldsSequance.length - 2) {
        continue;
      }
      addEventToSeparator(
        currentItem,
        fieldsSequance[i],
        fieldsSequance[i + 1]
      );
    }
  }
});
// document.body.addEventListener("click", function (e) {
//   console.log("bodya");
//   console.log(e);
// });
</script>

<style></style>
