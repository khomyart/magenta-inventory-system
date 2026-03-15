<template>
  <div class="page" v-if="appStore.filters.data[currentSection]">
    <div class="toolbar row q-mt-md">
      <q-input
        v-model="
          appStore.filters.data[currentSection].selectedParams.title.value
        "
        debounce="700"
        outlined
        placeholder="Введіть назву транзакції"
        dense
        class="q-mr-md"
        style="width: 300px"
        :loading="sectionStore.data.isItemsLoading"
      >
        <template v-slot:append v-if="!sectionStore.data.isItemsLoading">
          <q-icon name="search"/>
        </template>
      </q-input>
      <CreateBusinessAccountTransactionComponent v-if="allowenses.create" />
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
            :is="getFilterComponent(item)"
            v-bind="getFilterComponentProps(index)"
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
          <BusinessAccountTransactionComponent
            @show-remove-dialog="showRemoveDialog"
            @show-edit-dialog="showUpdateDialog"
            @clear-updated-item-id="clearUpdatedItemId"
            @copy-value="copyValue"
            :allowenses="{
              update: allowenses.update(userStore.data.id, item.created_by_user?.id),
              delete: allowenses.delete(userStore.data.id, item.created_by_user?.id),
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

    <!-- Update Dialog -->
    <q-dialog v-model="sectionStore.dialogs.update.isShown">
      <q-card>
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon name="swap_horiz" color="black" size="md" class="q-mr-sm"/>
            Транзакція
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

            <div class="col-12 row q-col-gutter-sm">
              <q-select
                outlined
                v-model="updatedItem.type"
                label="Тип"
                :options="[
                  { label: 'Дохід', value: 'income' },
                  { label: 'Витрата', value: 'outcome' }
                ]"
                emit-value
                map-options
                class="col-6"
              />
              <q-select
                outlined
                v-model="updatedItem.currency"
                label="Валюта"
                :options="['UAH', 'USD', 'EUR']"
                class="col-6"
              />
            </div>

            <div class="col-12 row q-col-gutter-sm q-pt-md">
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
                label="Термінал"
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
            </div>

            <DateTimeInputComponent label="Дата транзакції" class="full-width" v-model="updatedItem.happened_at" use-rules>
            </DateTimeInputComponent>
          </q-card-section>

          <q-separator />

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

    <!-- Delete Dialog -->
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
          Ви справді бажаєте видалити транзакцію: "{{ deletedItem.title }}"?
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
import {ref, reactive, onMounted, watch, computed, onBeforeMount, onBeforeUnmount} from "vue";
import {useRouter} from "vue-router";
import {useAppConfigStore} from "src/stores/appConfigStore";
import {useBusinessAccountTransactionStore} from "stores/businessAccountTransactionStore";
import {useUserStore} from "stores/userStore";
import {useQuasar} from "quasar";
import CreateBusinessAccountTransactionComponent from "components/business_account_transactions/CreateBusinessAccountTransactionComponent.vue";
import SortingComponent from "src/components/filter_bar/SortingComponent.vue";
import ButtonComponent from "src/components/filter_bar/ButtonComponent.vue";
import SelectFilterButtonComponent from "src/components/filter_bar/SelectFilterButtonComponent.vue";
import BusinessAccountTransactionComponent from "components/business_account_transactions/BusinessAccountTransactionComponent.vue";
import PriceButtonComponent from "components/filter_bar/PriceButtonComponent.vue";
import DateButtonComponent from "components/filter_bar/DateButtonComponent.vue";
import DateTimeInputComponent from "components/input/DateTimeInputComponent.vue";
import {getClientTime} from "app/helpers/GeneralPurposeFunctions";

const currentSection = "business_account_transactions";
const appStore = useAppConfigStore();
const sectionStore = useBusinessAccountTransactionStore();
const userStore = useUserStore();
const router = useRouter();
const $q = useQuasar();

const fieldsSequance = ["title", "type", "total_price", "happened_at", "created_at", "created_by_user"];
const fieldsDetails = [
  {
    label: "Назва",
    searchBarLabel: "Назва транзакції",
    type: "universal",
    orderButtonLabels: {
      up: "Від 0 до 9, від A до Z, від А до Я",
      down: "Від Я до А, від Z до A, від 9 до 0",
    },
  },
  {
    label: "Тип",
    searchBarLabel: "Тип",
    type: "select",
    options: [
      { label: "Дохід", value: "income" },
      { label: "Витрата", value: "outcome" },
    ],
    orderButtonLabels: {
      up: "Дохід спочатку",
      down: "Витрата спочатку",
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
    label: "Дата транзакції",
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
  update: (currentUserId, ownerId) => {
    let isAllowedToUpdate = appStore.allowenses.isValidFor("update", currentSection);
    if (!isAllowedToUpdate) return false;
    return true;
  },
  delete: (currentUserId, ownerId) => {
    let isAllowedToDelete = appStore.allowenses.isValidFor("delete", currentSection);
    if (!isAllowedToDelete) return false;
    return true;
  },
};

let updatedItem = reactive({
  id: "",
  title: "",
  type: "income",
  amount_on_card: 0,
  amount_via_terminal: 0,
  amount_as_cash: 0,
  currency: "UAH",
  happened_at: "",
});

let deletedItem = reactive({
  id: "",
  title: "",
});

let tempFieldWidths = reactive({
  title: 0,
  type: 0,
  total_price: 0,
  happened_at: 0,
  created_at: 0,
  created_by_user: 0,
});

function getFilterComponent(fieldName) {
  if (fieldName === 'total_price') return PriceButtonComponent;
  if (fieldName === 'happened_at' || fieldName === 'created_at') return DateButtonComponent;
  if (fieldName === 'type') return SelectFilterButtonComponent;
  return ButtonComponent;
}

function getFilterComponentProps(index) {
  const fieldName = fieldsSequance[index];
  const fieldDetails = fieldsDetails[index];

  const commonProps = {
    appStore: appStore,
    sectionName: currentSection,
    sectionStore: sectionStore,
    name: fieldName,
    label: fieldDetails.label,
    searchBarLabel: fieldDetails.searchBarLabel,
    orderButtonLabels: fieldDetails.orderButtonLabels,
    width: computedFilterWidth.value.buttons[fieldName],
    mode: fieldDetails.type,
  };

  if (fieldName === 'total_price' || fieldName === 'happened_at' || fieldName === 'created_at') {
    return {
      ...commonProps,
      targetFields: fieldDetails.additionalFieldsForFiltering ?? []
    };
  }

  if (fieldName === 'type') {
    return {
      ...commonProps,
      options: fieldDetails.options,
      optionLabel: 'label',
      optionValue: 'value'
    };
  }

  return commonProps;
}

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
  updatedItem.type = item.type;
  updatedItem.currency = item.currency;
  updatedItem.amount_on_card = item.amount_on_card;
  updatedItem.amount_via_terminal = item.amount_via_terminal;
  updatedItem.amount_as_cash = item.amount_as_cash;
  updatedItem.happened_at = getClientTime(item.happened_at, "ua", true);
  sectionStore.dialogs.update.isShown = true;
}

function showRemoveDialog(id, name) {
  deletedItem.id = id;
  deletedItem.title = name;
  sectionStore.dialogs.delete.isShown = true;
}

function clearFilter(fields, filterType = "all") {
  let filters = appStore.filters;
  if (!Array.isArray(fields)) {
    fields = [fields];
  }

  fields.forEach((field, index) => {
    if (!filters.data[currentSection].selectedParams[field]) return;

    filters.data[currentSection].selectedParams[field].value = "";
    filters.data[currentSection].selectedParams[field].filterMode =
      filters.availableParams.items[0];

    if (filterType === "number") {
      filters.data[currentSection].selectedParams[field].filterMode =
        filters.availableParams.items[4]; // index 4 is 'equal' for type 'number'
    }

    if (filterType === "select") {
      filters.data[currentSection].selectedParams[field].filterMode =
        filters.availableParams.items[6]; // index 6 is 'equal' for type 'select'
    }

    if (filterType === "date") {
      // Index 2 is 'more' (>=), Index 3 is 'less' (<=)
      if (field.includes('_from')) {
        filters.data[currentSection].selectedParams[field].filterMode = filters.availableParams.items[2];
      } else if (field.includes('_to')) {
        filters.data[currentSection].selectedParams[field].filterMode = filters.availableParams.items[3];
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
  if (appStore.filters.data[currentSection].selectedParams[field]?.value !== "") {
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
  const result = {
    buttons: {},
    fields: {
      separator: appStore.filters.availableParams.separatorWidth,
      lastSeparator: appStore.filters.availableParams.separatorWidth / 2 - 2,
    }
  };

  fieldsSequance.forEach(field => {
    const dynamicWidth = appStore.filters.data[currentSection].width.dynamic[field] || 150;
    result.buttons[field] = dynamicWidth - appStore.filters.availableParams.filterButtonXPadding;
    result.fields[field] = dynamicWidth;
  });

  return result;
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

// Targeted filter watchers
watch(
  [
    () => appStore.filters.data[currentSection].selectedParams.order.combined,
    () => appStore.filters.data[currentSection].selectedParams.title.value,
    () => appStore.filters.data[currentSection].selectedParams.type.value,
    () => appStore.filters.data[currentSection].selectedParams.total_price.value,
    () => appStore.filters.data[currentSection].selectedParams.happened_at_from?.value,
    () => appStore.filters.data[currentSection].selectedParams.happened_at_to?.value,
    () => appStore.filters.data[currentSection].selectedParams.created_at_from?.value,
    () => appStore.filters.data[currentSection].selectedParams.created_at_to?.value,
    () => appStore.filters.data[currentSection].selectedParams.created_by_user.value,
  ],
  () => {
    sectionStore.receive();
  }
);

onMounted(() => {
  appStore.currentPages[currentSection] = Number(
    router.currentRoute.value.params.page || 1
  );

  for (const fieldName in appStore.filters.data[currentSection].width.dynamic) {
    if (
      appStore.filters.data[currentSection].width.dynamic[fieldName] <
      appStore.filters.availableParams.minFilterWidth
    ) {
      appStore.filters.data[currentSection].width.dynamic[fieldName] =
        appStore.filters.data[currentSection].width.default[fieldName];
    }
  }

  let qApp = document.querySelector("#q-app");
  let pageContainer = document.querySelector(".content");

  function addEventToSeparator(
    separatorObject,
    fieldName,
    affectedFieldName = null
  ) {
    if (!separatorObject) return;

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
      const onSeparatorRelease = () => {
        if (devider) devider.remove();
        document.body.onmousemove = null;
        document.body.onmouseup = null;
        qApp.classList.remove("disable-interaction");

        if (affectedFieldName != null) {
          appStore.filters.data[currentSection].width.dynamic[fieldName] = tempFieldWidths[fieldName];
          appStore.filters.data[currentSection].width.dynamic[affectedFieldName] = tempFieldWidths[affectedFieldName];
        } else {
          appStore.filters.data[currentSection].width.dynamic[fieldName] = tempFieldWidths[fieldName];
        }
        appStore.updateLocalStorageConfig();
      };

      qApp.classList.add("disable-interaction");
      Object.keys(appStore.filters.data[currentSection].width.dynamic).forEach(
        (field) => {
          tempFieldWidths[field] = appStore.filters.data[currentSection].width.dynamic[field];
        }
      );

      let initCursorCoord = mouseDownEvent.pageX;
      let initFieldWidth = appStore.filters.data[currentSection].width.dynamic[fieldName];
      let initAffectedFieldWidth = affectedFieldName ? appStore.filters.data[currentSection].width.dynamic[affectedFieldName] : null;

      let devider = separatorMovementVisualisation();
      let initDeviderOffsetLeft = devider.offsetLeft;

      document.body.onmousemove = (mouseMoveEvent) => {
        const diff = mouseMoveEvent.pageX - initCursorCoord;
        devider.style.left = `${initDeviderOffsetLeft + diff}px`;
        tempFieldWidths[fieldName] = Math.max(appStore.filters.availableParams.minFilterWidth, initFieldWidth + diff);
        if (affectedFieldName) {
          tempFieldWidths[affectedFieldName] = Math.max(appStore.filters.availableParams.minFilterWidth, initAffectedFieldWidth - diff);
        }
      };

      document.body.onmouseup = onSeparatorRelease;
    };
  }

  setTimeout(() => {
    for (let i = 0; i < fieldsSequance.length; i++) {
      let separator = document.querySelector(
        `.filter-separator[name='${fieldsSequance[i]}']`
      );
      if (appStore.filters.availableParams.resizeMode === "straight") {
        addEventToSeparator(separator, fieldsSequance[i]);
      }
    }
  }, 500);
});

onBeforeUnmount(() => {
  // sectionStore.$reset();
})

onBeforeMount(() => {
  sectionStore.receive()
})
</script>

<style scoped>
.footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  background: white;
  border-top: 1px solid #e0e0e0;
}
</style>
