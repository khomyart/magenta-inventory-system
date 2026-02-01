<template>
  <div class="page">
    <div class="toolbar row q-mt-md">
      <q-input
        v-model="
          appStore.filters.data[currentSection].selectedParams.title.value
        "
        debounce="700"
        outlined
        placeholder="Введіть назву витрати"
        dense
        class="q-mr-md"
        style="width: 300px"
        :loading="sectionStore.data.isItemsLoading"
      >
        <template v-slot:append v-if="!sectionStore.data.isItemsLoading">
          <q-icon name="search"/>
        </template>
      </q-input>
      <CreateSpendComponent v-if="allowenses.create" />
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
    </div>

    <div class="content">
      <q-inner-loading :showing="sectionStore.data.isItemsLoading">
        <q-spinner-puff size="50px" color="primary"/>
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
                item === 'price'
                ? PriceButtonComponent
                : item === 'happened_at' || item === 'created_at'
                ? DateButtonComponent
                : ButtonComponent
            "
            :appStore="appStore"
            :sectionName="currentSection"
            :sectionStore="sectionStore"
            :name="fieldsSequance[index]"
            :targetFields="fieldsDetails[index].additionalFieldsForFiltering ?? []"
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
          <SpendComponent
            @show-remove-dialog="showRemoveDialog"
            @show-edit-dialog="showUpdateDialog"
            @clear-updated-item-id="clearUpdatedItemId"
            @copy-value="copyValue"
            :allowenses="{
              update: allowenses.update(userStore.data.id, item.created_by_user.id),
              delete: allowenses.delete(userStore.data.id, item.created_by_user.id),
            }"
            :itemInfo="item"
            :gap="appStore.other.visualTheme.gapsBetweenItems[currentSection]"
            :updated="item.id == sectionStore.data.updatedItemId"
            :sectionStore="sectionStore"
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
        Кількість: {{ sectionStore.data.amountOfItems }}
      </div>
    </div>

    <!--UPDATING DIALOG-->
    <q-dialog v-model="sectionStore.dialogs.update.isShown">
      <q-card>
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon name="recycling" color="black" size="md" class="q-mr-sm"/>
            Витрата
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-form @submit="sectionStore.update(updatedItem)">
          <q-card-section
            style="max-height: 50vh; width: 400px"
            class="scroll q-pt-md row q-col-gutter-sm"
          >
            <q-input
              class="col-12"
              outlined
              v-model="updatedItem.title"
              autofocus
              label="Назва"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть назву',
                (val) => val.length <= 255 || 'Не більше 255 символів',
              ]"
            />
            <q-select
              hide-dropdown-icon
              outlined
              v-model="updatedItem.currency"
              label="Валюта"
              :options="['UAH', 'USD', 'EUR']"
              class="col-12"
            />
            <q-input
              outlined
              class="col-4"
              v-model="updatedItem.amount_on_card"
              label="Картка"
              type="number"
              step="0.01"
              :rules="[
                (val) => val >= 0 || 'Не менше 0',
              ]"
            />
            <q-input
              outlined
              class="col-4"
              v-model="updatedItem.amount_via_terminal"
              label="Рахунок"
              type="number"
              step="0.01"
              :rules="[
                (val) => val >= 0 || 'Не менше 0',
              ]"
            />
            <q-input
              outlined
              class="col-4"
              v-model="updatedItem.amount_as_cash"
              label="Готівка"
              type="number"
              step="0.01"
              :rules="[
                (val) => val >= 0 || 'Не менше 0',
              ]"
            />
            <DateTimeInputComponent label="Дата витрати" class="full-width" v-model="updatedItem.happened_at" use-rules>
            </DateTimeInputComponent>
            <q-checkbox v-if="appStore.allowenses.isValidFor('hide', currentSection)"
                        v-model="updatedItem.is_hidden"
                        label="Приховано"
            />
          </q-card-section>

          <q-separator/>

          <q-card-actions align="right">
            <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
            <q-btn
              flat
              color="primary"
              type="submit"
              :loading="sectionStore.dialogs.update.isLoading"
            ><b>Оновити</b></q-btn
            >
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- DELETING DIALOG -->
    <q-dialog v-model="sectionStore.dialogs.delete.isShown">
      <q-card>
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon name="warning" color="red" size="md" class="q-mr-sm"/>
            Видалення
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-card-section style="width: 350px">
          Ви справді бажаєте видалити витрату: "{{ deletedItem.title }}"?
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
  </div>
</template>

<script setup>
import {reactive, onMounted, watch, computed, onBeforeMount, onBeforeUnmount} from "vue";
import {useRouter} from "vue-router";
import {useAppConfigStore} from "src/stores/appConfigStore";
import {useSpendStore} from "stores/spendStore";
import {useUserStore} from "stores/userStore";
import {useQuasar} from "quasar";
import CreateSpendComponent from "components/spends/CreateSpendComponent.vue";
import SortingComponent from "src/components/filter_bar/SortingComponent.vue";
import ButtonComponent from "src/components/filter_bar/ButtonComponent.vue";
import SpendComponent from "components/spends/SpendComponent.vue";
import PriceButtonComponent from "components/filter_bar/PriceButtonComponent.vue";
import DateButtonComponent from "components/filter_bar/DateButtonComponent.vue";
import DateTimeInputComponent from "components/input/DateTimeInputComponent.vue";
import {getClientTime} from "app/helpers/GeneralPurposeFunctions";

const currentSection = "spends";
const appStore = useAppConfigStore();
const sectionStore = useSpendStore();
const userStore = useUserStore();
const router = useRouter();
const $q = useQuasar();

const fieldsSequance = ["title", "price", "happened_at", "created_at", "created_by_user"];
const fieldsDetails = [
  {
    label: "Назва",
    searchBarLabel: "Назва витрати",
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
    label: "Дата витрати",
    searchBarLabel: ["Від", "До"],
    additionalFieldsForFiltering: ["happened_at_from", "happened_at_to"],
    type: "date",
    orderButtonLabels: {
      up: "Від старішої до новішої",
      down: "Від новішої до старішої",
    },
  },
  {
    label: "Дата створення",
    searchBarLabel: ["Від", "До"],
    additionalFieldsForFiltering: ["created_at_from", "created_at_to"],
    type: "date",
    orderButtonLabels: {
      up: "Від старішої до новішої",
      down: "Від новішої до старішої",
    },
  },
  {
    label: "Автор",
    searchBarLabel: "Ім'я",
    type: "universal",
    orderButtonLabels: {
      up: "Від 0 до 9, від A до Z, від А до Я",
      down: "Від Я до А, від Z до A, від 9 до 0",
    },
  },
];

const allowenses = {
  create: appStore.allowenses.isValidFor("create", currentSection),
  update: (currentUserId, spendOwnerId) => {
    let isAllowedToUpdate = appStore.allowenses.isValidFor("update", currentSection);
    if (!isAllowedToUpdate) {
      return false;
    }

    let isAllowedToUpdateNotOwned = appStore.allowenses.isValidFor("update_not_owned", currentSection);
    if (currentUserId !== spendOwnerId && !isAllowedToUpdateNotOwned) {
      return false;
    }

    return true;
  },
  delete: (currentUserId, spendOwnerId) => {
    let isAllowedToDelete = appStore.allowenses.isValidFor("delete", currentSection);
    if (!isAllowedToDelete) {
      return false;
    }

    let isAllowedToDeleteNotOwned = appStore.allowenses.isValidFor("delete_not_owned", currentSection);
    if (currentUserId !== spendOwnerId && !isAllowedToDeleteNotOwned) {
      return false;
    }

    return true;
  },
  hide: appStore.allowenses.isValidFor("hide", currentSection),
  see_hidden: appStore.allowenses.isValidFor("see_hidden", currentSection),
};

let updatedItem = reactive({
  id: "",
  title: "",
  amount_on_card: 0,
  amount_via_terminal: 0,
  amount_as_cash: 0,
  currency: "",
  happened_at: "",
  is_hidden: false
});

let deletedItem = reactive({
  id: "",
  title: "",
});

let tempFieldWidths = reactive({
  //px
  title: 0,
  price: 0,
});

function clearUpdatedItemId() {
  let interval = setTimeout(() => {
    sectionStore.data.updatedItemId = 0;
    clearInterval(interval);
  }, 2000);
}

function copyValue(value, paramName) {
  navigator.clipboard.writeText(value);
  $q.notify({
    position: "top",
    color: "primary",
    message: `${paramName} зкопійовано: "${value}"`,
    group: false,
  });
}

function showUpdateDialog(item) {
  updatedItem.id = item.id;
  updatedItem.title = item.title;
  updatedItem.currency = item.currency;
  updatedItem.amount_on_card = item.amount_on_card;
  updatedItem.amount_via_terminal = item.amount_via_terminal;
  updatedItem.amount_as_cash = item.amount_as_cash;
  updatedItem.happened_at = getClientTime(item.happened_at, "ua", true);
  if (allowenses.hide) {
    updatedItem.is_hidden = !!item.is_hidden;
  }
  sectionStore.dialogs.update.isShown = true;
}

function showRemoveDialog(id, name) {
  deletedItem.id = id;
  deletedItem.title = name;
  sectionStore.dialogs.delete.isShown = true;
}

/**
 *
 * @param fields could be string or array
 * @param filterType
 */
function clearFilter(fields, filterType = "all") {
  let filters = appStore.filters;
  if (!Array.isArray(fields)) {
    fields = [fields];
  }

  fields.forEach((field, index) => {
    filters.data[currentSection].selectedParams[field].value = "";
    filters.data[currentSection].selectedParams[field].filterMode =
      filters.availableParams.items[0];

    if (filterType === "number") {
      filters.data[currentSection].selectedParams[field].filterMode =
        filters.availableParams.items[2];
    }

    if (filterType === "date") {
      switch (index) {
        case 1: // happened_at_from
          filters.data[currentSection].selectedParams[field].filterMode =
            filters.availableParams.items[2];
          break;
        case 2: // happened_at_to
          filters.data[currentSection].selectedParams[field].filterMode =
            filters.availableParams.items[3];
          break;
      }
    }

    if (filters.data[currentSection].selectedParams.order.field === field) {
      filters.data[currentSection].selectedParams.order.field = "";
      filters.data[currentSection].selectedParams.order.value = "";
      filters.data[currentSection].selectedParams.order.combined = "";
    }
  })
}

function onChangedFieldFilterMode(field) {
  if (appStore.filters.data[currentSection].selectedParams[field].value !== "") {
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

const computedFilterWidth = computed(() => {
  return {
    buttons: {
      title:
        appStore.filters.data[currentSection].width.dynamic.title -
        appStore.filters.availableParams.filterButtonXPadding,
      price:
        appStore.filters.data[currentSection].width.dynamic.price -
        appStore.filters.availableParams.filterButtonXPadding,
      happened_at:
        appStore.filters.data[currentSection].width.dynamic.happened_at -
        appStore.filters.availableParams.filterButtonXPadding,
      created_at:
        appStore.filters.data[currentSection].width.dynamic.created_at -
        appStore.filters.availableParams.filterButtonXPadding,
      created_by_user:
        appStore.filters.data[currentSection].width.dynamic.created_by_user -
        appStore.filters.availableParams.filterButtonXPadding,
    },
    fields: {
      title: appStore.filters.data[currentSection].width.dynamic.title,
      price: appStore.filters.data[currentSection].width.dynamic.price,
      happened_at: appStore.filters.data[currentSection].width.dynamic.happened_at,
      created_at: appStore.filters.data[currentSection].width.dynamic.created_at,
      created_by_user: appStore.filters.data[currentSection].width.dynamic.created_by_user,
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
    if (appStore.currentPages[currentSection] !== 1) {
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
    () => appStore.filters.data[currentSection].selectedParams.title.value,
    () => appStore.filters.data[currentSection].selectedParams.price.value,
    () => appStore.filters.data[currentSection].selectedParams.currency.value,
    () => appStore.filters.data[currentSection].selectedParams.happened_at_from.value,
    () => appStore.filters.data[currentSection].selectedParams.happened_at_to.value,
    () => appStore.filters.data[currentSection].selectedParams.created_at_from.value,
    () => appStore.filters.data[currentSection].selectedParams.created_at_to.value,
    () => appStore.filters.data[currentSection].selectedParams.created_by_user.value,
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

    if (appStore.filters.availableParams.resizeMode === "straight") {
      addEventToSeparator(currentItem, fieldsSequance[i]);
    }

    if (appStore.filters.availableParams.resizeMode === "affected") {
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

onBeforeUnmount(() => {
  sectionStore.$reset();
})

onBeforeMount(() => {
  sectionStore.receive()
})
</script>

<style></style>
