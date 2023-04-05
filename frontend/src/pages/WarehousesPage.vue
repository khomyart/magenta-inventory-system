<template>
  <div class="page">
    <div class="toolbar row q-px-md q-mt-md">
      <q-input
        v-model="
          appStore.filters.data[currentSection].selectedParams.name.value
        "
        debounce="700"
        outlined
        placeholder="Введіть назву складу"
        dense
        class="q-mr-md"
        style="width: 300px"
        :loading="sectionStore.data.isItemsLoading"
      >
        <template v-slot:append v-if="!sectionStore.data.isItemsLoading">
          <q-icon name="search" />
        </template>
      </q-input>
      <CreateWarehouseComponent v-if="allowenses.create" />
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
          <WarehouseButtonComponent
            :appStore="appStore"
            :sectionName="currentSection"
            :sectionStore="sectionStore"
            :name="fieldsSequance[index]"
            :label="fieldsDetails[index].label"
            :searchBarLabel="fieldsDetails[index].searchBarLabel"
            :width="computedFilterWidth.buttons[fieldsSequance[index]]"
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
            <td :width="computedFilterWidth.fields[fieldsSequance[index]]"></td>
            <td
              v-if="index != fieldsSequance.length - 1"
              :width="computedFilterWidth.fields.separator"
            ></td>
            <td v-else :width="computedFilterWidth.fields.lastSeparator"></td>
          </template>
        </tr>
        <template v-for="(item, index) in sectionStore.items" :key="index">
          <WarehouseComponent
            @show-remove-dialog="showRemoveDialog"
            @show-edit-dialog="showUpdateDialog"
            @clear-updated-item-id="clearUpdatedItemId"
            @copy-value="copyValue"
            :allowenses="{
              update: allowenses.update,
              delete: allowenses.delete,
            }"
            :itemInfo="item"
            :gap="5"
            :updated="item.id == sectionStore.data.updatedItemId"
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
          v-model="appStore.amountOfItemsPerPages[currentSection]"
          :options="appStore.availableAmaountOfItemsPerPage"
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
            <q-icon name="warehouse" color="black" size="md" class="q-mr-sm" />
            Склад
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-form @submit="sectionStore.update(updatedItem)">
          <q-card-section
            style="max-height: 50vh; width: 95vw; max-width: 450px"
            class="scroll col-12 q-pt-lg"
          >
            <div class="row q-col-gutter-lg q-mb-sm">
              <q-select
                hide-dropdown-icon
                outlined
                v-model="updatedItem.country"
                use-input
                hide-selected
                fill-input
                autofocus
                label="Країна"
                input-debounce="400"
                :options="countryStore.items"
                option-value="id"
                option-label="name"
                @filter="countryFilter"
                :loading="countryStore.data.isItemsLoading"
                class="col-6"
                :rules="[
                  () =>
                    (updatedItem.country != null &&
                      updatedItem.country.id != undefined) ||
                    'Оберіть країну',
                ]"
              >
              </q-select>
              <q-select
                hide-dropdown-icon
                outlined
                v-model="updatedItem.city"
                label="Місто"
                use-input
                hide-selected
                fill-input
                input-debounce="400"
                :options="cityStore.items"
                option-value="id"
                option-label="name"
                @filter="cityFilter"
                :loading="cityStore.data.isItemsLoading"
                class="col-6"
                :disable="
                  updatedItem.country == null ||
                  updatedItem.country.id == undefined
                "
                :rules="[
                  () =>
                    (updatedItem.city != null &&
                      updatedItem.city.id != undefined) ||
                    'Оберіть місто',
                ]"
              >
              </q-select>
            </div>
            <div class="row q-col-gutter-lg q-mb-sm">
              <q-input
                class="col-6"
                outlined
                v-model="updatedItem.address"
                label="Адреса"
                :rules="[
                  (val) => (val !== null && val !== '') || 'Введіть адресу',
                  (val) => val.length <= 250 || 'Не більше 250 символів',
                ]"
              />
              <q-input
                class="col-6"
                outlined
                v-model="updatedItem.name"
                label="Назва"
                :rules="[
                  (val) => (val !== null && val !== '') || 'Введіть назву',
                  (val) => val.length <= 100 || 'Не більше 100 символів',
                ]"
              />
            </div>

            <q-input
              class="col-12"
              outlined
              v-model="updatedItem.description"
              label="Опис"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть опис',
                (val) => val.length <= 250 || 'Не більше 250 символів',
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
            <q-icon name="warning" color="red" size="md" class="q-mr-sm" />
            Видалення
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-card-section style="width: 350px">
          Ви справді бажаєте знищити склад: "{{ deletedItem.name }}"?
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
import { reactive, onMounted, watch, computed } from "vue";
import { useRouter } from "vue-router";
import { useAppConfigStore } from "src/stores/appConfigStore";
import { useWarehouseStore } from "src/stores/warehouseStore";
import { useCountryStore } from "src/stores/helpers/countryStore";
import { useCityStore } from "src/stores/helpers/cityStore";
import { useQuasar } from "quasar";
import WarehouseComponent from "src/components/warehouse/WarehouseComponent.vue";
import CreateWarehouseComponent from "src/components/warehouse/CreateWarehouseComponent.vue";
import WarehouseButtonComponent from "src/components/warehouse/WarehouseButtonComponent.vue";
import SortingComponent from "src/components/filter_bar/SortingComponent.vue";

const currentSection = "warehouses";
const appStore = useAppConfigStore();
const sectionStore = useWarehouseStore();
const countryStore = useCountryStore();
const cityStore = useCityStore();
const router = useRouter();
const $q = useQuasar();

const fieldsSequance = [
  "country_name",
  "city_name",
  "address",
  "name",
  "description",
];
const fieldsDetails = [
  {
    label: "Країна",
    searchBarLabel: "Назва країни",
  },
  {
    label: "Місто",
    searchBarLabel: "Назва міста",
  },
  {
    label: "Адреса",
    searchBarLabel: "Адреса складу",
  },
  {
    label: "Назва",
    searchBarLabel: "Назва складу",
  },
  {
    label: "Опис",
    searchBarLabel: "Опис складу",
  },
];

const allowenses = {
  create: appStore.allowenses.isValidFor("create", currentSection),
  update: appStore.allowenses.isValidFor("update", currentSection),
  delete: appStore.allowenses.isValidFor("delete", currentSection),
};

let updatedItem = reactive({
  id: "",
  country: "",
  city: "",
  address: "",
  name: "",
  description: "",
});

/**
 * this object, sectionStore.dialogs.update.isShown watcher and
 * updatedItem.country watcher exists for total clearing and proper
 * working of updating dialog
 * Without those items, we will have a problem, when we can`t set init value of
 * "country select item" and "city select item"
 */
let tempUpdatedItem = reactive({
  id: "",
  country: "",
  city: "",
  address: "",
  name: "",
  description: "",
});

let deletedItem = reactive({
  id: "",
  name: "",
});

let tempFieldWidths = reactive({
  //px
  country_name: 0,
  city_name: 0,
  address: 0,
  name: 0,
  description: 0,
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
  tempUpdatedItem.id = item.id;
  tempUpdatedItem.country = { id: item.country_id, name: item.country_name };
  tempUpdatedItem.city = { id: item.city_id, name: item.city_name };
  tempUpdatedItem.address = item.address;
  tempUpdatedItem.name = item.name;
  tempUpdatedItem.description = item.description;
  sectionStore.dialogs.update.isShown = true;
}

function countryFilter(val, update, abort) {
  update(() => {
    countryStore.data.isItemsLoading = true;
    countryStore.items = [];
    countryStore.receive(val);
  });
}

function cityFilter(val, update, abort) {
  update(() => {
    cityStore.data.isItemsLoading = true;
    cityStore.items = [];
    cityStore.receive(updatedItem.country.id, val);
  });
}

watch(
  () => updatedItem.country,
  (newValue, oldValue) => {
    if (oldValue != "") {
      updatedItem.city = "";
    }
  }
);

watch(
  () => sectionStore.dialogs.update.isShown,
  (newValue, oldValue) => {
    if (oldValue == false && newValue == true) {
      updatedItem.id = tempUpdatedItem.id;
      updatedItem.country = tempUpdatedItem.country;
      updatedItem.city = tempUpdatedItem.city;
      updatedItem.address = tempUpdatedItem.address;
      updatedItem.name = tempUpdatedItem.name;
      updatedItem.description = tempUpdatedItem.description;
    }

    if (oldValue == true && newValue == false) {
      updatedItem.id = "";
      updatedItem.country = "";
      updatedItem.city = "";
      updatedItem.address = "";
      updatedItem.name = "";
      updatedItem.description = "";
    }
  }
);

function showRemoveDialog(id, name) {
  deletedItem.id = id;
  deletedItem.name = name;
  sectionStore.dialogs.delete.isShown = true;
}

/**
 * button events
 */
function clearFilter(field) {
  let filters = appStore.filters;

  filters.data[currentSection].selectedParams[field].value = "";
  filters.data[currentSection].selectedParams[field].filterMode =
    filters.availableParams.items[0];

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

const computedFilterWidth = computed(() => {
  return {
    buttons: {
      country_name:
        appStore.filters.data[currentSection].width.dynamic.country_name -
        appStore.filters.availableParams.filterButtonXPadding,
      city_name:
        appStore.filters.data[currentSection].width.dynamic.city_name -
        appStore.filters.availableParams.filterButtonXPadding,
      address:
        appStore.filters.data[currentSection].width.dynamic.address -
        appStore.filters.availableParams.filterButtonXPadding,
      name:
        appStore.filters.data[currentSection].width.dynamic.name -
        appStore.filters.availableParams.filterButtonXPadding,
      description:
        appStore.filters.data[currentSection].width.dynamic.description -
        appStore.filters.availableParams.filterButtonXPadding,
    },
    fields: {
      country_name:
        appStore.filters.data[currentSection].width.dynamic.country_name,
      city_name: appStore.filters.data[currentSection].width.dynamic.city_name,
      address: appStore.filters.data[currentSection].width.dynamic.address,
      name: appStore.filters.data[currentSection].width.dynamic.name,
      description:
        appStore.filters.data[currentSection].width.dynamic.description,
      separator: appStore.filters.availableParams.separatorWidth,
      lastSeparator: appStore.filters.availableParams.separatorWidth / 2 - 1,
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
    () =>
      appStore.filters.data[currentSection].selectedParams.country_name.value,
    () => appStore.filters.data[currentSection].selectedParams.city_name.value,
    () => appStore.filters.data[currentSection].selectedParams.address.value,
    () => appStore.filters.data[currentSection].selectedParams.name.value,
    () =>
      appStore.filters.data[currentSection].selectedParams.description.value,
  ],
  () => {
    sectionStore.receive();
  }
);

onMounted(() => {
  sectionStore.items = [];
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

<style></style>
