<template>
  <div class="page">
    <div class="toolbar row q-mt-md">
      <q-input
        v-model="
          appStore.filters.data[currentSection].selectedParams.description.value
        "
        debounce="700"
        outlined
        placeholder="Введіть опис кольору"
        dense
        class="q-mr-md"
        style="width: 300px"
        :loading="sectionStore.data.isItemsLoading"
      >
        <template v-slot:append v-if="!sectionStore.data.isItemsLoading">
          <q-icon name="search" />
        </template>
      </q-input>
      <CreateColorComponent v-if="allowenses.create" />
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
          <ButtonComponent
            :appStore="appStore"
            :sectionName="currentSection"
            :sectionStore="sectionStore"
            :name="fieldsSequance[index]"
            :label="fieldsDetails[index].label"
            :searchBarLabel="fieldsDetails[index].searchBarLabel"
            :width="computedFilterWidth.buttons[fieldsSequance[index]]"
            :justOrder="
              fieldsSequance[index] == 'value' ||
              fieldsSequance[index] == 'text_color_value'
            "
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
          <ColorComponent
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
            <q-icon name="palette" color="black" size="md" class="q-mr-sm" />
            Колір
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-form @submit="sectionStore.update(updatedItem)">
          <q-card-section
            style="max-height: 50vh; width: 95vw; max-width: 450px"
            class="scroll q-pt-md"
          >
            <div class="row q-mb-lg justify-between">
              <div
                class="flex-center"
                :style="{
                  width: '47%',
                  height: '50px',
                  fontSize: '20px',
                  padding: '5px',
                  fontWeight: 'bold',
                  borderRadius: '4px',
                  border: '1px solid rgba(0, 0, 0, 0.18)',
                  backgroundColor: updatedItem.value,
                  color: updatedItem.text_color_value,
                }"
              >
                <q-tooltip class="bg-black text-body2" :offset="[0, 5]">
                  {{ colorTooltip }}
                </q-tooltip>
                {{ updatedItem.article }}
              </div>
              <div class="row justify-between" style="width: 47%">
                <q-btn
                  :color="`${
                    updatedItem.text_color_value == '#000000'
                      ? 'purple'
                      : 'white'
                  }`"
                  :text-color="`${
                    updatedItem.text_color_value == '#000000'
                      ? 'white'
                      : 'black'
                  }`"
                  style="width: 44%"
                  @click="updatedItem.text_color_value = '#000000'"
                  >Чорний</q-btn
                >
                <q-btn
                  :color="`${
                    updatedItem.text_color_value == '#ffffff'
                      ? 'purple'
                      : 'white'
                  }`"
                  :text-color="`${
                    updatedItem.text_color_value == '#ffffff'
                      ? 'white'
                      : 'black'
                  }`"
                  style="width: 44%"
                  @click="updatedItem.text_color_value = '#ffffff'"
                  >Білий</q-btn
                >
              </div>
            </div>
            <div class="row justify-between q-mb-sm">
              <q-input
                outlined
                v-model="updatedItem.value"
                :rules="['anyColor']"
                style="width: 47%"
                label="Головний"
              >
                <template v-slot:append>
                  <q-icon name="colorize" class="cursor-pointer">
                    <q-popup-proxy
                      cover
                      transition-show="scale"
                      transition-hide="scale"
                    >
                      <q-color v-model="updatedItem.value" />
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>

              <q-input
                outlined
                v-model="updatedItem.text_color_value"
                :rules="['anyColor']"
                label="Артикль"
                style="width: 47%"
              >
                <template v-slot:append>
                  <q-icon name="colorize" class="cursor-pointer">
                    <q-popup-proxy
                      cover
                      transition-show="scale"
                      transition-hide="scale"
                    >
                      <q-color v-model="updatedItem.text_color_value" />
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
            </div>
            <q-input
              class="q-mb-sm"
              outlined
              v-model="updatedItem.article"
              label="Артикль"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть артикль',
                (val) => val.length <= 10 || 'Не більше 10 символів',
              ]"
            />
            <q-input
              outlined
              v-model="updatedItem.description"
              label="Опис"
              :rules="[
                (val) => (val !== null && val !== '') || 'Введіть опис',
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
              :loading="sectionStore.dialogs.create.isLoading"
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
          Ви справді бажаєте знищити колір: "{{ deletedItem.description }}"?
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
import { useColorStore } from "src/stores/colorStore";
import { useAppConfigStore } from "src/stores/appConfigStore";
import { useQuasar } from "quasar";
import ColorComponent from "src/components/color/ColorComponent.vue";
import CreateColorComponent from "src/components/color/CreateColorComponent.vue";
import SortingComponent from "src/components/filter_bar/SortingComponent.vue";
import ButtonComponent from "src/components/filter_bar/ButtonComponent.vue";

const currentSection = "colors";
const appStore = useAppConfigStore();
const sectionStore = useColorStore();
const router = useRouter();
const $q = useQuasar();

const fieldsSequance = ["value", "article", "description", "text_color_value"];
const fieldsDetails = [
  {
    label: "Колір",
    searchBarLabel: "Значення кольору",
  },
  {
    label: "Артикль",
    searchBarLabel: "Значення артиклю",
  },
  {
    label: "Опис",
    searchBarLabel: "Значення опису",
  },
  {
    label: "Колір тексту",
    searchBarLabel: "Значення кольору тексту",
  },
];

const allowenses = {
  create: appStore.allowenses.isValidFor("create", currentSection),
  update: appStore.allowenses.isValidFor("update", currentSection),
  delete: appStore.allowenses.isValidFor("delete", currentSection),
};

let updatedItem = reactive({
  id: "",
  value: "",
  article: "",
  description: "",
  text_color_value: "",
});

let deletedItem = reactive({
  id: "",
  description: "",
});

let tempFieldWidths = reactive({
  //px
  value: 0,
  article: 0,
  description: 0,
  text_color_value: 0,
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
  updatedItem.value = item.value;
  updatedItem.article = item.article;
  updatedItem.description = item.description;
  updatedItem.text_color_value = item.text_color_value;
  sectionStore.dialogs.update.isShown = true;
}

function showRemoveDialog(id, description) {
  deletedItem.id = id;
  deletedItem.description = description;
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
      value:
        appStore.filters.data[currentSection].width.dynamic.value -
        appStore.filters.availableParams.filterButtonXPadding,
      article:
        appStore.filters.data[currentSection].width.dynamic.article -
        appStore.filters.availableParams.filterButtonXPadding,
      description:
        appStore.filters.data[currentSection].width.dynamic.description -
        appStore.filters.availableParams.filterButtonXPadding,
      text_color_value:
        appStore.filters.data[currentSection].width.dynamic.text_color_value -
        appStore.filters.availableParams.filterButtonXPadding,
    },
    fields: {
      value: appStore.filters.data[currentSection].width.dynamic.value,
      article: appStore.filters.data[currentSection].width.dynamic.article,
      description:
        appStore.filters.data[currentSection].width.dynamic.description,
      text_color_value:
        appStore.filters.data[currentSection].width.dynamic.text_color_value,
      separator: appStore.filters.availableParams.separatorWidth,
      lastSeparator: appStore.filters.availableParams.separatorWidth / 2 - 1,
    },
  };
});

const colorTooltip = computed(() => {
  return updatedItem.description === ""
    ? "Введіть опис кольору"
    : updatedItem.description;
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
    () => appStore.filters.data[currentSection].selectedParams.value.value,
    () => appStore.filters.data[currentSection].selectedParams.article.value,
    () =>
      appStore.filters.data[currentSection].selectedParams.description.value,
    () =>
      appStore.filters.data[currentSection].selectedParams.text_color_value
        .value,
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
        appStore.filters.data[currentSection].width.default[fieldName] -
        contentPaddingX;
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
