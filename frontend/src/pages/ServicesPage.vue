<template>
  <div class="page">
    <div class="toolbar row q-mt-md">
      <q-input
        v-model="
          appStore.filters.data[currentSection].selectedParams.title.value
        "
        debounce="700"
        outlined
        placeholder="Введіть назву послуги"
        dense
        class="q-mr-md"
        style="width: 300px"
        :loading="sectionStore.data.isItemsLoading"
      >
        <template v-slot:append v-if="!sectionStore.data.isItemsLoading">
          <q-icon name="search" />
        </template>
      </q-input>
      <CreateServiceComponent v-if="allowenses.create" />
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
              item === 'price'
                ? PriceButtonComponent
                : ButtonComponent
            "
            :appStore="appStore"
            :sectionName="currentSection"
            :sectionStore="sectionStore"
            :name="fieldsSequance[index]"
            :targetFields="
              fieldsDetails[index].additionalFieldsForFiltering ?? []
            "
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
          <ServiceComponent
            @show-remove-dialog="showRemoveDialog"
            @show-edit-dialog="showUpdateDialog"
            @clear-updated-item-id="clearUpdatedItemId"
            @copy-value="copyValue"
            :allowenses="allowenses"
            :itemInfo="item"
            :gap="appStore.other.visualTheme.gapsBetweenItems[currentSection]"
            :updated="item.id == sectionStore.data.updatedItemId"
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

    <q-dialog v-model="sectionStore.dialogs.update.isShown">
      <q-card>
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon
              name="miscellaneous_services"
              color="black"
              size="md"
              class="q-mr-sm"
            />
            Послуга
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
              label="Назва послуги"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть назву',
                (val) => val.length <= 255 || 'Не більше 255 символів',
              ]"
            />
            <q-input
              outlined
              class="col-6"
              v-model="updatedItem.price"
              label="Ціна"
              type="number"
              step="0.01"
              :rules="[
                (val) => (val !== null && val !== '') || 'Вкажіть ціну',
                (val) => val >= 0.01 || 'Не менше 0.01',
              ]"
            />
            <q-select
              hide-dropdown-icon
              outlined
              v-model="updatedItem.currency"
              label="Валюта"
              :options="['UAH', 'USD', 'EUR']"
              class="col-6"
            />
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
          Ви справді бажаєте видалити послугу: "{{ deletedItem.title }}"?
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
import { useRouter } from "vue-router";
import { useAppConfigStore } from "src/stores/appConfigStore";
import { useServiceStore } from "stores/serviceStore";
import { useQuasar } from "quasar";
import CreateServiceComponent from "components/service/CreateServiceComponent.vue";
import ServiceComponent from "components/service/ServiceComponent.vue";
import SortingComponent from "src/components/filter_bar/SortingComponent.vue";
import ButtonComponent from "src/components/filter_bar/ButtonComponent.vue";
import PriceButtonComponent from "components/filter_bar/PriceButtonComponent.vue";

const currentSection = "services";
const appStore = useAppConfigStore();
const sectionStore = useServiceStore();
const router = useRouter();
const $q = useQuasar();

const fieldsSequance = ["title", "price"];
const fieldsDetails = [
  { label: "Назва", searchBarLabel: "Назва послуги", type: "universal", orderButtonLabels: { up: "Від A до Я", down: "Від Я до А" }},
  { label: "Вартість", searchBarLabel: "Вартість (грн.)", type: "number", orderButtonLabels: { up: "Від дешевших", down: "Від дорожчих" }},
];

const allowenses = {
  create: appStore.allowenses.isValidFor("create", currentSection),
  update: appStore.allowenses.isValidFor("update", currentSection),
  delete: appStore.allowenses.isValidFor("delete", currentSection),
};

let updatedItem = reactive({ id: "", title: "", price: "", currency: "UAH" });
let deletedItem = reactive({ id: "", title: "" });

// <--- Додано для ініціалізації тимчасових значень ширини
let tempFieldWidths = reactive({
  title: 0,
  price: 0,
});

function clearUpdatedItemId() { setTimeout(() => { sectionStore.data.updatedItemId = 0; }, 2000); }
function copyValue(value, paramName) { navigator.clipboard.writeText(value); $q.notify({ position: "top", color: "primary", message: `${paramName} зкопійовано: "${value}"`, group: false }); }
function showUpdateDialog(item) { Object.assign(updatedItem, { id: item.id, title: item.title, price: item.unconverted_price, currency: item.currency }); sectionStore.dialogs.update.isShown = true; }
function showRemoveDialog(id, title) { deletedItem.id = id; deletedItem.title = title; sectionStore.dialogs.delete.isShown = true; }
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

    if (filters.data[currentSection].selectedParams.order.field === field) {
      filters.data[currentSection].selectedParams.order.field = "";
      filters.data[currentSection].selectedParams.order.value = "";
      filters.data[currentSection].selectedParams.order.combined = "";
    }
  })
}
function onChangedFieldFilterMode(field) { if (appStore.filters.data[currentSection].selectedParams[field].value) sectionStore.receive(); }
function setFilterOrder(field, fieldOrder) {
  let order = appStore.filters.data[currentSection].selectedParams.order;
  if (order.field === field && order.value === fieldOrder) {
    order.field = ""; order.value = "";
  } else {
    order.field = field; order.value = fieldOrder;
  }
  order.combined = `${field}${fieldOrder}`;
}

const computedFilterWidth = computed(() => {
  const dynamicWidths = appStore.filters.data[currentSection].width.dynamic;
  const padding = appStore.filters.availableParams.filterButtonXPadding;
  const separator = appStore.filters.availableParams.separatorWidth;
  return {
    buttons: {
      title: dynamicWidths.title - padding,
      price: dynamicWidths.price - padding,
    },
    fields: {
      title: dynamicWidths.title,
      price: dynamicWidths.price,
      separator: separator,
      lastSeparator: separator / 2 - 2,
    },
  };
});

watch(() => appStore.currentPages[currentSection], (page) => { router.push(`/${currentSection}/${page}`); sectionStore.receive(); });
watch(() => appStore.amountOfItemsPerPages[currentSection], () => { if (appStore.currentPages[currentSection] !== 1) appStore.currentPages[currentSection] = 1; else sectionStore.receive(); });
watch(() => [
  appStore.filters.data[currentSection].selectedParams.order.combined,
  appStore.filters.data[currentSection].selectedParams.title.value,
  appStore.filters.data[currentSection].selectedParams.price.value,
], () => {
  sectionStore.receive();
});

onMounted(() => {
  if (appStore.errors.reauth.data.isLogoutThroughtLogoutMethod) {
    appStore.errors.reauth.data.isLogoutThroughtLogoutMethod = false;
    sectionStore.receive();
  }
  appStore.currentPages[currentSection] = Number(router.currentRoute.value.params.page);

  // <--- ДОДАНО БЛОК ДЛЯ РЕАЛІЗАЦІЇ ЗМІНИ РОЗМІРУ КОЛОНОК
  let contentElement = document.querySelector(".content");
  let contentPaddingX = 2 + parseFloat(getComputedStyle(contentElement).paddingLeft) + parseFloat(getComputedStyle(contentElement).paddingRight);

  for (const fieldName in appStore.filters.data[currentSection].width.dynamic) {
    if (appStore.filters.data[currentSection].width.dynamic[fieldName] < appStore.filters.availableParams.minFilterWidth) {
      appStore.filters.data[currentSection].width.dynamic[fieldName] = appStore.filters.data[currentSection].width.default[fieldName];
    }
  }

  let qApp = document.querySelector("#q-app");
  let pageContainer = document.querySelector(".content");

  function addEventToSeparator(separatorObject, fieldName, affectedFieldName = null) {
    function separatorMovementVisualisation() {
      let devider = document.createElement("div");
      devider.classList.add("filter-width-helper");
      devider.style.height = `${pageContainer.clientHeight}px`;
      pageContainer.appendChild(devider);
      devider.style.top = `${pageContainer.offsetTop}px`;
      devider.style.left = `${separatorObject.getBoundingClientRect().x - pageContainer.getBoundingClientRect().x + appStore.filters.availableParams.separatorWidth / 2}px`;
      return devider;
    }

    separatorObject.onmousedown = (mouseDownEvent) => {
      separatorObject.onmouseup = () => {
        onSeparatorRelease();
      };
      document.body.onmouseup = () => {
        onSeparatorRelease();
      };
      qApp.classList.add("disable-interaction");
      Object.keys(appStore.filters.data[currentSection].width.dynamic).forEach((field) => {
        tempFieldWidths[field] = appStore.filters.data[currentSection].width.dynamic[field];
      });

      let initCursorCoord = mouseDownEvent.pageX;
      let initFieldWidth = appStore.filters.data[currentSection].width.dynamic[fieldName];
      let initAffectedFieldWidth = affectedFieldName == null ? null : appStore.filters.data[currentSection].width.dynamic[affectedFieldName];
      let minFilterWidth = appStore.filters.availableParams.minFilterWidth;
      let devider = separatorMovementVisualisation();
      let initDeviderOffsetLeft = devider.offsetLeft;

      function onSeparatorRelease() {
        devider.remove();
        document.body.onmousemove = null;
        document.body.onmouseup = null;
        qApp.classList.remove("disable-interaction");

        if (affectedFieldName != null) {
          if (tempFieldWidths[fieldName] < minFilterWidth) {
            tempFieldWidths[fieldName] = minFilterWidth;
            tempFieldWidths[affectedFieldName] = initAffectedFieldWidth + (initFieldWidth - minFilterWidth);
          } else if (tempFieldWidths[affectedFieldName] < minFilterWidth) {
            tempFieldWidths[affectedFieldName] = minFilterWidth;
            tempFieldWidths[fieldName] = initFieldWidth + (initAffectedFieldWidth - minFilterWidth);
          }
          appStore.filters.data[currentSection].width.dynamic[fieldName] = tempFieldWidths[fieldName];
          appStore.filters.data[currentSection].width.dynamic[affectedFieldName] = tempFieldWidths[affectedFieldName];
        } else {
          if (tempFieldWidths[fieldName] < minFilterWidth) {
            tempFieldWidths[fieldName] = minFilterWidth;
          }
          appStore.filters.data[currentSection].width.dynamic[fieldName] = tempFieldWidths[fieldName];
        }
        appStore.updateLocalStorageConfig();
      }

      document.body.onmousemove = (mouseMoveEvent) => {
        devider.style.left = `${initDeviderOffsetLeft + mouseMoveEvent.pageX - initCursorCoord}px`;
        tempFieldWidths[fieldName] = initFieldWidth + mouseMoveEvent.pageX - initCursorCoord;
        if (affectedFieldName != null) {
          tempFieldWidths[affectedFieldName] = initAffectedFieldWidth - mouseMoveEvent.pageX + initCursorCoord;
        }
      };
    };
  }

  for (let i = 0; i < fieldsSequance.length; i++) {
    let currentItem = document.querySelector(`.filter-separator[name='${fieldsSequance[i]}']`);
    if (appStore.filters.availableParams.resizeMode == "straight") {
      addEventToSeparator(currentItem, fieldsSequance[i]);
    }
    if (appStore.filters.availableParams.resizeMode == "affected") {
      if (i > fieldsSequance.length - 2) continue;
      addEventToSeparator(currentItem, fieldsSequance[i], fieldsSequance[i + 1]);
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
