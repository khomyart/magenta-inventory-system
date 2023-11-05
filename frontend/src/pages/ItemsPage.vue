<template>
  <div class="page">
    <div class="toolbar row q-mt-md q-pr-md">
      <q-input
        v-model="
          appStore.filters.data[currentSection].selectedParams.title.value
        "
        debounce="700"
        outlined
        placeholder="Введіть назву предмету"
        dense
        class="q-mr-md"
        style="width: 300px"
        :loading="sectionStore.data.isItemsLoading"
      >
        <template v-slot:append v-if="!sectionStore.data.isItemsLoading">
          <q-icon name="search" />
        </template>
      </q-input>
      <q-btn
        flat
        round
        color="black"
        icon="sync"
        @click="sectionStore.receive()"
      >
        <q-tooltip
          anchor="bottom left"
          :offset="[-20, 7]"
          class="bg-black text-body2"
        >
          Оновити список
        </q-tooltip>
      </q-btn>
      <q-space></q-space>
      <FilterByWarehouseComponent />
      <q-separator vertical class="q-mx-sm"></q-separator>
      <IncomeCreatorComponent v-if="allowenses.income" />
      <OutcomeCreatorComponent v-if="allowenses.outcome" />
      <!-- <q-btn
        flat
        round
        color="black"
        @click="switchItemsView"
        :icon="showGroupedItems ? 'unfold_more' : 'unfold_less'"
      >
        <q-tooltip
          class="bg-black text-body2"
          anchor="bottom left"
          :offset="[-20, 7]"
        >
          {{ groupedItemsButtonTooltip }}
        </q-tooltip>
      </q-btn> -->
      <q-btn v-if="allowenses.create" flat round color="black" icon="add">
        <q-tooltip
          anchor="bottom left"
          :offset="[-20, 7]"
          class="bg-black text-body2"
        >
          Створити
        </q-tooltip>
        <q-menu self="bottom middle" :offset="[0, -50]">
          <q-list style="min-width: 150px">
            <q-item
              clickable
              v-close-popup
              @click="sectionStore.dialogs.create.isShown = true"
            >
              <q-item-section>Одиночний предмет</q-item-section>
            </q-item>
            <q-item
              clickable
              v-close-popup
              @click="sectionStore.dialogs.createMultiple.isShown = true"
            >
              <q-item-section>Групу предметів</q-item-section>
            </q-item>
          </q-list>
        </q-menu>
      </q-btn>
      <ItemMoveComponent v-if="allowenses.move" />
    </div>

    <div class="content">
      <q-inner-loading :showing="sectionStore.data.isItemsLoading">
        <q-spinner-puff size="50px" color="primary" />
      </q-inner-loading>
      <q-toolbar class="text-black filter q-px-none q-py-md bg-white">
        <SortingComponent
          :filterIn="currentSection"
          :sectionStore="sectionStore"
        />
        <div class="filter-separator">
          <div class="vertical-line"></div>
        </div>
        <template v-for="(item, index) in fieldsSequance" :key="index">
          <component
            :is="
              item === 'color'
                ? ColorButtonComponent
                : item === 'price'
                ? PriceButtonComponent
                : ButtonComponent
            "
            :appStore="appStore"
            :sectionName="currentSection"
            :sectionStore="sectionStore"
            :name="fieldsSequance[index]"
            :label="fieldsDetails[index].label"
            :searchBarLabel="fieldsDetails[index].searchBarLabel"
            :orderButtonLabels="fieldsDetails[index].orderButtonLabels"
            :width="computedFilterWidth.buttons[fieldsSequance[index]]"
            :mode="fieldsDetails[index].type"
            @clear-filter="clearFilter"
            @change-filter-mode="onChangedFieldFilterMode"
            @set-filter-order="setFilterOrder"
          />
        </template>
      </q-toolbar>
      <table class="items">
        <tr>
          <td :width="60"></td>
          <td :width="computedFilterWidth.fields.separator"></td>
          <template v-for="(item, index) in fieldsSequance" :key="index">
            <td
              v-if="index != fieldsSequance.length - 1"
              :width="computedFilterWidth.fields[fieldsSequance[index]]"
            ></td>
            <td
              v-if="index != fieldsSequance.length - 1"
              :width="computedFilterWidth.fields.separator"
            ></td>
            <td
              v-if="index == fieldsSequance.length - 1"
              :width="
                computedFilterWidth.fields[fieldsSequance[index]] +
                computedFilterWidth.fields.lastSeparator
              "
            ></td>
          </template>
        </tr>
        <template v-for="(item, index) in sectionStore.items" :key="index">
          <ItemComponent
            @show-remove-dialog="showRemoveDialog"
            @fill-create-window-with-item-data="fillCreateWindowWithItemData"
            @fill-update-window-with-item-data="fillUpdateWindowWithItemData"
            @clear-updated-item-id="clearUpdatedItemId"
            @show-amounts-in-warehouses-dialog="showAmountsInWarehousesDialog"
            @copy-value="copyValue"
            @show-item-description-dialog="showItemDescriptionDialog"
            :allowenses="{
              create: allowenses.create,
              update: allowenses.update,
              delete: allowenses.delete,
            }"
            :itemInfo="item"
            :gap="appStore.other.visualTheme.gapsBetweenItems[currentSection]"
            :updated="item.id == sectionStore.data.updatedItemId"
            :appStore="appStore"
            :sectionStore="sectionStore"
            :index="index"
            :isFirst="index == 0"
            :isLast="index == sectionStore.items.length - 1"
            :itemsBorderRadius="
              appStore.other.visualTheme.itemsBorderRadius[currentSection]
            "
          />
        </template>
      </table>
    </div>

    <div class="footer">
      <div class="footer-left-part flex items-center">
        <span class="q-mr-sm">Записів на сторінці</span>
        <q-select
          class="item-per-page-selector"
          outlined
          dense
          v-model="appStore.amountOfItemsPerPages[currentSection]"
          :options="appStore.availableAmaountOfItemsPerPage"
          options-dense
        />
        <q-separator vertical class="q-mx-md"></q-separator>
        <q-pagination
          v-model="appStore.currentPages[currentSection]"
          color="purple"
          :max="sectionStore.data.lastPage"
          :max-pages="6"
          boundary-numbers
        />
      </div>
      <div class="footer-right-part q-mr-md">
        <button
          @click="sectionStore.dialogs.warehouseDetail.isShown = true"
          v-if="
            appStore.filters.data[currentSection].selectedParams.warehouse !=
            null
          "
          class="warehouse-description-button"
        >
          {{ itemsSearchingSource }}
        </button>
        <span
          class="q-px-md q-mr-md"
          v-if="
            appStore.filters.data[currentSection].selectedParams.warehouse ==
            null
          "
          >{{ itemsSearchingSource }}</span
        >
        Кількість:
        {{ sectionStore.data.amountOfItems }}
      </div>
    </div>
    <!--CREATING DIALOG-->
    <CreateItemComponent />
    <!--CREATING MULTIPLE DIALOG-->
    <CreateMultipleItemsComponent />
    <!--UPDATING DIALOG-->
    <UpdateItemComponent />
    <!-- DELETING DIALOG -->
    <q-dialog v-model="sectionStore.dialogs.delete.isShown">
      <q-card>
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon name="warning" color="red" size="md" class="q-mr-sm" />
            Видалення
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-card-section style="width: 350px">
          Ви справді бажаєте знищити предмет: "{{ deletedItem.title }}"?
        </q-card-section>
        <q-separator></q-separator>
        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn
            @click="sectionStore.delete(deletedItem.id)"
            flat
            type="submit"
            color="negative"
            :loading="sectionStore.dialogs.delete.isLoading"
            ><b>Так</b></q-btn
          >
        </q-card-actions>
      </q-card>
    </q-dialog>
    <!-- WAREHOUSE INFO DIALOG -->
    <q-dialog
      v-model="sectionStore.dialogs.warehouseDetail.isShown"
      transition-show="scale"
      transition-hide="scale"
    >
      <q-card style="width: 350px">
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon
              size="md"
              class="q-mr-sm"
              name="warehouse"
              color="black"
            ></q-icon>
            Склад
          </div>
        </q-card-section>
        <q-separator></q-separator>

        <q-card-section class="q-pt-md">
          <p>
            Країна:
            {{
              appStore.filters.data.items.selectedParams.warehouse.country_name
            }}
          </p>
          <p>
            Місто:
            {{ appStore.filters.data.items.selectedParams.warehouse.city_name }}
          </p>
          <p>
            Вулиця:
            <a
              :href="`https://www.google.com.ua/maps/place/${appStore.filters.data.items.selectedParams.warehouse.address} ${appStore.filters.data.items.selectedParams.warehouse.city_name}`"
              target="_blank"
            >
              {{ appStore.filters.data.items.selectedParams.warehouse.address }}
            </a>
          </p>
          <p>
            Назва:
            {{ appStore.filters.data.items.selectedParams.warehouse.name }}
          </p>
          <p>
            Опис:
            {{
              appStore.filters.data.items.selectedParams.warehouse.description
            }}
          </p>
        </q-card-section>

        <q-separator></q-separator>
        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup>Гаразд</q-btn>
        </q-card-actions>
      </q-card>
    </q-dialog>
    <!-- AMOUNTS WAREHOUSES DIALOG -->
    <AmountWarehousesDialogComponent />
    <!-- SHOW DESCRIPTION DIALOG -->
    <ShowDescriptionDialogComponent />
  </div>
</template>

<script setup>
import { reactive, onMounted, watch, computed, ref, onUpdated } from "vue";
import { useRouter } from "vue-router";
import { useItemStore } from "src/stores/itemStore";
import { useAppConfigStore } from "src/stores/appConfigStore";
import { useQuasar } from "quasar";
import ItemComponent from "src/components/item/ItemComponent.vue";
import CreateItemComponent from "src/components/item/single/CreateItemComponent.vue";
import CreateMultipleItemsComponent from "src/components/item/createMultiple/CreateMultipleItemsComponent.vue";
import UpdateItemComponent from "src/components/item/single/UpdateItemComponent.vue";
import SortingComponent from "src/components/filter_bar/SortingComponent.vue";
import ButtonComponent from "src/components/filter_bar/ButtonComponent.vue";
import ColorButtonComponent from "src/components/filter_bar/ColorButtonComponent.vue";
import PriceButtonComponent from "src/components/filter_bar/PriceButtonComponent.vue";
import FilterByWarehouseComponent from "src/components/item/FilterByWarehouseComponent.vue";
import IncomeCreatorComponent from "src/components/item/income/IncomeCreatorComponent.vue";
import OutcomeCreatorComponent from "src/components/item/outcome/OutcomeCreatorComponent.vue";
import ItemMoveComponent from "src/components/item/move/ItemMoveComponent.vue";
import ShowDescriptionDialogComponent from "src/components/item/single/ShowDescriptionDialogComponent.vue";
import AmountWarehousesDialogComponent from "src/components/item/single/AmountWarehousesDialogComponent.vue";

const currentSection = "items";
const appStore = useAppConfigStore();
const sectionStore = useItemStore();
const router = useRouter();
const $q = useQuasar();

const fieldsSequance = [
  "group_id",
  "article",
  "title",
  "description",
  "price",
  "type",
  "gender",
  "size",
  "color",
  "amount",
  "units",
];
const fieldsDetails = [
  {
    label: "ID групи",
    searchBarLabel: "Значення ID групи",
    type: "universal",
    orderButtonLabels: {
      up: "Від 0 до 9, від A до Z, від А до Я",
      down: "Від Я до А, від Z до A, від 9 до 0",
    },
  },
  {
    label: "Артикль",
    searchBarLabel: "Значення артиклю",
    type: "universal",
    orderButtonLabels: {
      up: "Від 0 до 9, від A до Z, від А до Я",
      down: "Від Я до А, від Z до A, від 9 до 0",
    },
  },
  {
    label: "Назва",
    searchBarLabel: "Назва",
    type: "universal",
    orderButtonLabels: {
      up: "Від 0 до 9, від A до Z, від А до Я",
      down: "Від Я до А, від Z до A, від 9 до 0",
    },
  },
  {
    label: "Опис",
    searchBarLabel: "Опис",
    type: "universal",
    orderButtonLabels: {
      up: "Від 0 до 9, від A до Z, від А до Я",
      down: "Від Я до А, від Z до A, від 9 до 0",
    },
  },
  {
    label: "Вартість",
    searchBarLabel: "Вартість (грн.)",
    type: "number",
    orderButtonLabels: {
      up: "Від дешевшого до дорожчого",
      down: "Від дорожчого до дешевшого",
    },
  },
  {
    label: "Вид",
    searchBarLabel: "Значення типу",
    type: "universal",
    orderButtonLabels: {
      up: "Від першого до останнього (за списком)",
      down: "Від останнього до першого (за списком)",
    },
  },
  {
    label: "Гендер",
    searchBarLabel: "Назва гендеру",
    type: "universal",
    orderButtonLabels: {
      up: "Від першого до останнього (за списком)",
      down: "Від останнього до першого (за списком)",
    },
  },
  {
    label: "Розмір",
    searchBarLabel: "Значення розміру",
    type: "universal",
    orderButtonLabels: {
      up: "Від першого до останнього (за списком)",
      down: "Від останнього до першого (за списком)",
    },
  },
  {
    label: "Колір",
    searchBarLabel: "Назва кольору",
    type: "universal",
    orderButtonLabels: {
      up: "Від 0 до 9, від A до Z, від А до Я",
      down: "Від Я до А, від Z до A, від 9 до 0",
    },
  },
  {
    label: "Кількість",
    searchBarLabel: "Кількість",
    type: "number",
    orderButtonLabels: {
      up: "Від меншого до більшого",
      down: "Від більшого до меншого",
    },
  },
  {
    label: "Одиниці",
    searchBarLabel: "Назва одиниці",
    type: "universal",
    orderButtonLabels: {
      up: "Від 0 до 9, від A до Z, від А до Я",
      down: "Від Я до А, від Z до A, від 9 до 0",
    },
  },
];

const allowenses = {
  create: appStore.allowenses.isValidFor("create", currentSection),
  update: appStore.allowenses.isValidFor("update", currentSection),
  delete: appStore.allowenses.isValidFor("delete", currentSection),
  income: appStore.allowenses.isValidFor("income", currentSection),
  outcome: appStore.allowenses.isValidFor("outcome", currentSection),
  move: appStore.allowenses.isValidFor("move", currentSection),
};

let deletedItem = reactive({
  id: "",
  title: "",
});

let tempFieldWidths = reactive({
  //px
  article: 0,
  name: 0,
});

let showGroupedItems = ref(false);
const groupedItemsButtonTooltip = computed(() => {
  return showGroupedItems.value ? "Розділити" : "Групувати";
});

function switchItemsView() {
  showGroupedItems.value = !showGroupedItems.value;
}

function clearUpdatedItemId() {
  let interval = setTimeout(() => {
    sectionStore.data.updatedItemId = 0;
    clearInterval(interval);
  }, 2000);
}

function fillUpdateWindowWithItemData(itemIdInDB, itemIndexInArray) {
  sectionStore.receiveItemToFillUpdateWindow(itemIdInDB, itemIndexInArray);
}
function fillCreateWindowWithItemData(itemIdInDB, itemIndexInArray) {
  sectionStore.receiveItemToFillCreateWindow(itemIdInDB, itemIndexInArray);
}

function copyValue(value, paramName) {
  navigator.clipboard.writeText(value);
  $q.notify({
    position: "top",
    color: "primary",
    message: `${paramName} зкопійовано: "${value}"`,
    // group: false,
    actions: [
      {
        icon: "close",
        color: "white",
        round: true,
        handler: () => {},
      },
    ],
  });
}

function showItemDescriptionDialog(title, description) {
  sectionStore.dialogs.itemDescription.isShown = true;
  sectionStore.dialogs.itemDescription.title = title;
  sectionStore.dialogs.itemDescription.content = description;
}

function showRemoveDialog(id, title) {
  deletedItem.id = id;
  deletedItem.title = title;
  sectionStore.dialogs.delete.isShown = true;
}

function showAmountsInWarehousesDialog(warehousesAmounts, itemTitle) {
  sectionStore.dialogs.amountsWarehouses.isShown = true;
  sectionStore.dialogs.amountsWarehouses.content = warehousesAmounts;
  sectionStore.dialogs.amountsWarehouses.itemTitle = itemTitle;
}
/**
 * button events
 */
function clearFilter(field, filterType = "all") {
  let filters = appStore.filters;

  filters.data[currentSection].selectedParams[field].value = "";
  filters.data[currentSection].selectedParams[field].filterMode =
    filters.availableParams.items[0];

  if (filterType === "number") {
    filters.data[currentSection].selectedParams[field].filterMode =
      filters.availableParams.items[2];
  }

  if (filters.data[currentSection].selectedParams.order.field === field) {
    filters.data[currentSection].selectedParams.order.field = "";
    filters.data[currentSection].selectedParams.order.value = "";
    filters.data[currentSection].selectedParams.order.combined = "";
  }
}

function onChangedFieldFilterMode(field) {
  if (appStore.filters.data[currentSection].selectedParams[field].value != "") {
    sectionStore.receive();
  }
}

function setFilterOrder(field, fieldOrder) {
  let order = appStore.filters.data[currentSection].selectedParams.order;
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

const itemsSearchingSource = computed(() => {
  let itemsSearchingSource = "";
  let warehouse =
    appStore.filters.data[currentSection].selectedParams.warehouse;

  itemsSearchingSource =
    warehouse == null
      ? "Всі склади"
      : `${warehouse.name}, (${warehouse.address})`;

  return itemsSearchingSource;
});

const computedFilterWidth = computed(() => {
  return {
    buttons: {
      group_id:
        appStore.filters.data[currentSection].width.dynamic.group_id -
        appStore.filters.availableParams.filterButtonXPadding,
      article:
        appStore.filters.data[currentSection].width.dynamic.article -
        appStore.filters.availableParams.filterButtonXPadding,
      title:
        appStore.filters.data[currentSection].width.dynamic.title -
        appStore.filters.availableParams.filterButtonXPadding,
      description:
        appStore.filters.data[currentSection].width.dynamic.description -
        appStore.filters.availableParams.filterButtonXPadding,
      type:
        appStore.filters.data[currentSection].width.dynamic.type -
        appStore.filters.availableParams.filterButtonXPadding,
      price:
        appStore.filters.data[currentSection].width.dynamic.price -
        appStore.filters.availableParams.filterButtonXPadding,
      gender:
        appStore.filters.data[currentSection].width.dynamic.gender -
        appStore.filters.availableParams.filterButtonXPadding,
      size:
        appStore.filters.data[currentSection].width.dynamic.size -
        appStore.filters.availableParams.filterButtonXPadding,
      color:
        appStore.filters.data[currentSection].width.dynamic.color -
        appStore.filters.availableParams.filterButtonXPadding,
      amount:
        appStore.filters.data[currentSection].width.dynamic.amount -
        appStore.filters.availableParams.filterButtonXPadding,
      units:
        appStore.filters.data[currentSection].width.dynamic.units -
        appStore.filters.availableParams.filterButtonXPadding,
    },
    fields: {
      group_id: appStore.filters.data[currentSection].width.dynamic.group_id,
      article: appStore.filters.data[currentSection].width.dynamic.article,
      title: appStore.filters.data[currentSection].width.dynamic.title,
      description:
        appStore.filters.data[currentSection].width.dynamic.description,
      type: appStore.filters.data[currentSection].width.dynamic.type,
      price: appStore.filters.data[currentSection].width.dynamic.price,
      gender: appStore.filters.data[currentSection].width.dynamic.gender,
      size: appStore.filters.data[currentSection].width.dynamic.size,
      color: appStore.filters.data[currentSection].width.dynamic.color,
      amount: appStore.filters.data[currentSection].width.dynamic.amount,
      units: appStore.filters.data[currentSection].width.dynamic.units,
      separator: appStore.filters.availableParams.separatorWidth,
      lastSeparator: appStore.filters.availableParams.separatorWidth / 2 - 2,
    },
  };
});

watch([() => appStore.currentPages[currentSection]], ([currentPage]) => {
  router.push(`/${currentSection}/${currentPage}`);
  sectionStore.receive();
});

watch(
  [() => appStore.amountOfItemsPerPages[currentSection]],
  ([amountPerPage]) => {
    if (appStore.currentPages[currentSection] != 1) {
      appStore.currentPages[currentSection] = 1;
    } else {
      sectionStore.receive();
    }
    router.push(`/${currentSection}/${appStore.currentPages[currentSection]}`);
  }
);
//filter watcher
watch(
  [
    () => appStore.filters.data[currentSection].selectedParams.order.combined,
    () => appStore.filters.data[currentSection].selectedParams.group_id.value,
    () => appStore.filters.data[currentSection].selectedParams.article.value,
    () => appStore.filters.data[currentSection].selectedParams.title.value,
    () =>
      appStore.filters.data[currentSection].selectedParams.description.value,
    () => appStore.filters.data[currentSection].selectedParams.type.value,
    () => appStore.filters.data[currentSection].selectedParams.price.value,
    () => appStore.filters.data[currentSection].selectedParams.gender.value,
    () => appStore.filters.data[currentSection].selectedParams.size.value,
    () => appStore.filters.data[currentSection].selectedParams.color.value,
    () => appStore.filters.data[currentSection].selectedParams.amount.value,
    () => appStore.filters.data[currentSection].selectedParams.units.value,
    () => appStore.filters.data[currentSection].selectedParams.warehouse,
  ],
  () => {
    sectionStore.receive();
  }
);

onMounted(() => {
  if (appStore.errors.reauth.data.isLogoutThroughtLogoutMethod === true) {
    appStore.errors.reauth.data.isLogoutThroughtLogoutMethod = false;
    sectionStore.receive();
  }

  // sectionStore.items = [];
  appStore.currentPages[currentSection] = Number(
    router.currentRoute.value.params.page
  );
  /*
      setting up default values for filter fields width according to config
    */
  let contentElement = document.querySelector(".content");
  //get .content div padding
  let contentPaddingX =
    2 +
    parseFloat(getComputedStyle(contentElement).paddingLeft) +
    parseFloat(getComputedStyle(contentElement).paddingRight);

  //firstly we need to set all widths to default values if its dynamic param is less than minFilterWidth
  for (const fieldName in appStore.filters.data[currentSection].width.dynamic) {
    if (
      appStore.filters.data[currentSection].width.dynamic[fieldName] <
      appStore.filters.availableParams.minFilterWidth
    ) {
      appStore.filters.data[currentSection].width.dynamic[fieldName] =
        appStore.filters.data[currentSection].width.default[fieldName];
    }
  }

  /*
      adding to all separators drag actions
    */
  let qApp = document.querySelector("#q-app");
  let pageContainer = document.querySelector(".content");

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
        appStore.filters.availableParams.separatorWidth / 2
      }px`;

      return devider;
    }

    separatorObject.onmousedown = (mouseDownEvent) => {
      separatorObject.onmouseup = () => {
        onSeparatorRelease();
      };
      document.body.onmouseup = () => {
        onSeparatorRelease();
      };
      //disabling interaction with other element except filter separator
      qApp.classList.add("disable-interaction");
      //applying current filter width values to temp filter object
      Object.keys(appStore.filters.data[currentSection].width.dynamic).forEach(
        (field) => {
          tempFieldWidths[field] =
            appStore.filters.data[currentSection].width.dynamic[field];
        }
      );

      let initCursorCoord = mouseDownEvent.pageX;
      let initFieldWidth =
        appStore.filters.data[currentSection].width.dynamic[fieldName];
      let initAffectedFieldWidth =
        affectedFieldName == null
          ? null
          : appStore.filters.data[currentSection].width.dynamic[
              affectedFieldName
            ];

      let minFilterWidth = appStore.filters.availableParams.minFilterWidth;
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
          appStore.filters.data[currentSection].width.dynamic[fieldName] =
            tempFieldWidths[fieldName];
          appStore.filters.data[currentSection].width.dynamic[
            affectedFieldName
          ] = tempFieldWidths[affectedFieldName];
        } else {
          if (tempFieldWidths[fieldName] < minFilterWidth) {
            tempFieldWidths[fieldName] = minFilterWidth;
          }

          appStore.filters.data[currentSection].width.dynamic[fieldName] =
            tempFieldWidths[fieldName];
        }

        appStore.updateLocalStorageConfig();
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

    if (appStore.filters.availableParams.resizeMode == "straight") {
      addEventToSeparator(currentItem, fieldsSequance[i]);
    }

    if (appStore.filters.availableParams.resizeMode == "affected") {
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
</script>

<style scoped>
.warehouse-description-button {
  background-color: white;
  border: 1px solid rgba(0, 0, 0, 0.18);
  border-radius: 2px;
  padding: 5px 10px;
  margin-right: 10px;
  cursor: pointer;
  transition: all 0.1s ease-in;
}
.warehouse-description-button:hover {
  background-color: rgba(255, 0, 217, 0.088);
}
</style>
