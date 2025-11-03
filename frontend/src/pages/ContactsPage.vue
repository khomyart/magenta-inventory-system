<template>
  <div class="page">
    <div class="toolbar row q-px-md q-mt-md">
      <q-input
        v-model="
          appStore.filters.data[currentSection].selectedParams.name_or_phone.value
        "
        debounce="700"
        outlined
        placeholder="Введіть ім'я, або номер контакту"
        dense
        class="q-mr-md"
        style="width: 300px"
        :loading="sectionStore.data.isItemsLoading"
      >
        <template v-slot:append v-if="!sectionStore.data.isItemsLoading">
          <q-icon name="search"/>
        </template>
      </q-input>
      <CreateContactComponent v-if="allowenses.create" />
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
          <ButtonComponent
            :appStore="appStore"
            :just-order="item === 'preferred_platforms'"
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
          <ContactComponent
            @show-remove-dialog="showRemoveDialog"
            @show-edit-dialog="showUpdateDialog"
            @clear-updated-item-id="clearUpdatedItemId"
            @copy-value="copyValue"
            :allowenses="{
              update: allowenses.update,
              delete: allowenses.delete,
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
            <q-icon name="contact_page" color="black" size="md" class="q-mr-sm"/>
            Контакти
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-form @submit="sectionStore.update(updatedItem)">
          <q-card-section
            style="max-height: 70vh; width: 450px"
            class="scroll q-mt-xs row q-col-gutter-sm"
          >
            <q-input
              class="col-12"
              outlined
              v-model="updatedItem.name"
              autofocus
              label="Ім'я"
              lazy-rules
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть ім\'я',
                (val) => val.length <= 255 || 'Не більше 255 символів',
              ]"
            />

            <q-input
              outlined
              class="col-6 q-mb-sm"
              v-model="updatedItem.phone"
              label="Телефон: 380..."
              mask="+###############"
              unmasked-value
              type="tel"
              lazy-rules
              :rules="[
                (val) => (val !== null && val !== '') || 'Вкажіть телефон',
                (val) => phoneValidationRule(val)
              ]"
            />

            <q-input
              outlined
              class="col-6 q-mb-sm"
              v-model="updatedItem.email"
              label="Email"
              type="email"
              lazy-rules
              :rules="[
                (val) => !val || val.length <= 150 || 'Не більше 150 символів',
                (val) => !val || /.+@.+\..+/.test(val) || 'Введіть коректний email',
              ]"
            />

            <q-input
              outlined
              class="col-12"
              v-model="updatedItem.address"
              label="Адреса"
              type="text"
              lazy-rules
              :rules="[
                (val) => !val || val.length <= 255 || 'Не більше 255 символів',
              ]"
            />

            <q-input
              outlined
              class="col-12"
              v-model="updatedItem.additional_info"
              label="Додаткова інформація"
              type="textarea"
              autogrow
              lazy-rules
              :rules="[
                (val) => !val || val.length <= 255 || 'Не більше 255 символів',
              ]"
            />

            <div class="text-h6">Бажані платформи зв'язку</div>
            <q-field
              stack-label
              :rules="[
              (val) => (Array.isArray(val) && val.length > 0) || 'Виберіть хоча б одну платформу',
            ]"
              :model-value="updatedItem.preferred_platforms"
              borderless
            >
              <template v-slot:control>
                <div class="row q-gutter-sm">
                  <q-checkbox v-model="updatedItem.preferred_platforms" val="call" label="Дзвінок" color="primary" />
                  <q-checkbox v-model="updatedItem.preferred_platforms" val="sms" label="SMS" color="primary" />
                  <q-checkbox v-model="updatedItem.preferred_platforms" val="email" label="Email" color="primary" />
                  <q-checkbox v-model="updatedItem.preferred_platforms" val="telegram" label="Telegram" color="blue" />
                  <q-checkbox v-model="updatedItem.preferred_platforms" val="viber" label="Viber" color="purple" />
                  <q-checkbox v-model="updatedItem.preferred_platforms" val="whatsapp" label="Whatsapp" color="green" />
                  <q-checkbox v-model="updatedItem.preferred_platforms" val="other" label="Інша" color="cyan" />
                </div>
              </template>
            </q-field>

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
          Ви справді бажаєте видалити контакт: "{{ deletedItem.name }}"?
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
import {reactive, onMounted, watch, computed} from "vue";
import {useRouter} from "vue-router";
import {useAppConfigStore} from "src/stores/appConfigStore";
import {useUserStore} from "stores/userStore";
import {useQuasar} from "quasar";
import SortingComponent from "src/components/filter_bar/SortingComponent.vue";
import ButtonComponent from "src/components/filter_bar/ButtonComponent.vue";
import {useContactStore} from "stores/contactStore";
import ContactComponent from "components/contact/ContactComponent.vue";
import CreateContactComponent from "components/contact/CreateContactComponent.vue";
import parsePhoneNumberFromString from "libphonenumber-js";

const currentSection = "contacts";
const appStore = useAppConfigStore();
const sectionStore = useContactStore();
const userStore = useUserStore();
const router = useRouter();
const $q = useQuasar();

const fieldsSequance = ["name", "phone", "email", "address", "additional_info", "preferred_platforms"];
const fieldsDetails = [
  {
    label: "Ім'я",
    searchBarLabel: "Ім'я",
    type: "universal",
    orderButtonLabels: {
      up: "Від 0 до 9, від A до Z, від А до Я",
      down: "Від Я до А, від Z до A, від 9 до 0",
    },
  },
  {
    label: "Телефон",
    searchBarLabel: "Будь-які цифри з номера",
    type: "universal",
    orderButtonLabels: {
      up: "Від 0 до 9, від A до Z, від А до Я",
      down: "Від Я до А, від Z до A, від 9 до 0",
    },
  },
  {
    label: "Електронна пошта",
    searchBarLabel: "Електронна пошта",
    type: "universal",
    orderButtonLabels: {
      up: "Від 0 до 9, від A до Z, від А до Я",
      down: "Від Я до А, від Z до A, від 9 до 0",
    },
  },
  {
    label: "Адреса",
    searchBarLabel: "Адреса",
    type: "universal",
    orderButtonLabels: {
      up: "Від 0 до 9, від A до Z, від А до Я",
      down: "Від Я до А, від Z до A, від 9 до 0",
    },
  },
  {
    label: "Додаткова інформація",
    searchBarLabel: "Додаткова інформація",
    type: "universal",
    orderButtonLabels: {
      up: "Від 0 до 9, від A до Z, від А до Я",
      down: "Від Я до А, від Z до A, від 9 до 0",
    },
  },
  {
    label: "Платформи зв'язку",
    searchBarLabel: "Платформи зв'язку",
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
};

let updatedItem = reactive({
  id: "",
  name: "",
  phone: "",
  additional_info: "",
  email: "",
  address: "",
  preferred_platforms: []
});

const phoneValidationRule = (val) => {
  let message = "Введіть коректний номер телефону";

  let parsedPhone = parsePhoneNumberFromString("+" + val);
  if (parsedPhone) {
    return parsedPhone.isValid() || message;
  }

  return message;
}

let deletedItem = reactive({
  id: "",
  name: "",
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
  if (value !== null) {
    navigator.clipboard.writeText(value);
    $q.notify({
      position: "top",
      color: "primary",
      message: `${paramName} зкопійовано: "${value}"`,
      group: false,
    });
  } else {
    $q.notify({
      position: "top",
      color: "primary",
      message: "Значення відсутнє",
      group: false,
    });
  }
}

function showUpdateDialog(item) {
  updatedItem.id = item.id;
  updatedItem.name = item.name;
  updatedItem.phone = item.phone;
  updatedItem.additional_info = item.additional_info;
  updatedItem.email = item.email;
  updatedItem.address = item.address;
  updatedItem.preferred_platforms = item.preferred_platforms;
  sectionStore.dialogs.update.isShown = true;
}

function showRemoveDialog(id, name) {
  deletedItem.id = id;
  deletedItem.name = name;
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
      name:
        appStore.filters.data[currentSection].width.dynamic.name -
        appStore.filters.availableParams.filterButtonXPadding,
      phone:
        appStore.filters.data[currentSection].width.dynamic.phone -
        appStore.filters.availableParams.filterButtonXPadding,
      email:
        appStore.filters.data[currentSection].width.dynamic.email -
        appStore.filters.availableParams.filterButtonXPadding,
      address:
        appStore.filters.data[currentSection].width.dynamic.address -
        appStore.filters.availableParams.filterButtonXPadding,
      preferred_platforms:
        appStore.filters.data[currentSection].width.dynamic.preferred_platforms -
        appStore.filters.availableParams.filterButtonXPadding,
      additional_info:
        appStore.filters.data[currentSection].width.dynamic.additional_info -
        appStore.filters.availableParams.filterButtonXPadding,
    },
    fields: {
      name: appStore.filters.data[currentSection].width.dynamic.name,
      phone: appStore.filters.data[currentSection].width.dynamic.phone,
      additional_info: appStore.filters.data[currentSection].width.dynamic.additional_info,
      email: appStore.filters.data[currentSection].width.dynamic.email,
      address: appStore.filters.data[currentSection].width.dynamic.address,
      preferred_platforms: appStore.filters.data[currentSection].width.dynamic.preferred_platforms,
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
    () => appStore.filters.data[currentSection].selectedParams.name_or_phone.value,
    () => appStore.filters.data[currentSection].selectedParams.name.value,
    () => appStore.filters.data[currentSection].selectedParams.phone.value,
    () => appStore.filters.data[currentSection].selectedParams.additional_info.value,
    () => appStore.filters.data[currentSection].selectedParams.email.value,
    () => appStore.filters.data[currentSection].selectedParams.address.value,
    () => appStore.filters.data[currentSection].selectedParams.preferred_platforms.value,
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
</script>

<style></style>
