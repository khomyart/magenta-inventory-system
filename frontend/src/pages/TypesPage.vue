<template>
  <div class="page">
    <div class="toolbar row q-px-md q-my-md">
      <q-input
        v-model="searchInput"
        debounce="500"
        outlined
        placeholder="Введіть назву виду"
        rounded
        dense
        class="col-4 q-mr-md"
        :loading="isSearching"
        @keydown.enter="isSearching = isSearching == false ? true : false"
      >
        <template v-slot:append v-if="!isSearching">
          <q-icon name="search" />
        </template>
      </q-input>
      <q-separator class="q-mx-sm" vertical></q-separator>
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
      <q-toolbar class="text-black filter q-px-none bg-white">
        <q-btn icon="filter_list" round flat style="margin: 0px 5px 0px 11px">
          <q-menu :offset="[11, 9]">
            <q-list style="min-width: 100px">
              <q-item clickable v-close-popup>
                <q-item-section>Застосувати фільтр</q-item-section>
              </q-item>
              <q-separator />
              <q-item clickable v-close-popup>
                <q-item-section>Скинути значення</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
        <div class="filter-separator">
          <div class="vertical-line"></div>
        </div>
        <q-btn flat stretch class="filter-button">
          <div
            :style="`min-width: ${fieldWidths.article}px; text-align: start`"
          >
            Артикль
          </div>

          <q-menu
            self="bottom middle"
            :offset="[-fieldWidths.article / 2 - 16, 102]"
          >
            <q-list style="min-width: 250px">
              <q-item clickable v-close-popup>
                <q-item-section>New tab</q-item-section>
              </q-item>
              <q-item clickable v-close-popup>
                <q-item-section>New incognito tab</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
        <div class="filter-separator" name="article">
          <div class="vertical-line"></div>
        </div>
        <q-btn flat stretch class="filter-button">
          <div
            :style="`min-width: ${
              fieldWidths.name - filterWidthSettings.options.xScrollWidth
            }px; text-align: start`"
          >
            Назва
          </div>
          <q-menu
            self="bottom middle"
            :offset="[-fieldWidths.name / 2 - 16, 102]"
          >
            <q-list style="min-width: 250px">
              <q-item clickable v-close-popup>
                <q-item-section>New tab</q-item-section>
              </q-item>
              <q-item clickable v-close-popup>
                <q-item-section>New incognito tab</q-item-section>
              </q-item>
            </q-list>
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
              filterWidthSettings.options.filterButtonXPadding -
              filterWidthSettings.options.separatorWidth -
              filterWidthSettings.options.xScrollWidth
            "
          ></td>
          <td :width="filterWidthSettings.options.separatorWidth + 4"></td>
        </tr>
        <template v-for="(item, index) in typeStore.items" :key="index">
          <type-component
            @show-remove-dialog="showRemoveDialog"
            @show-edit-dialog="alert(`edit ${n}`)"
            :itemInfo="item"
            :gap="5"
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
                (val) => val.length <= 8 || 'Не більше 128 символів',
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

    <!--EDITING DIALOG-->
    <q-dialog v-model="typeStore.dialogs.update.isShown">
      <q-card>
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6 flex items-center">
            <q-icon name="edit" color="black" size="md" /><b class="q-ml-sm"
              >Редагування</b
            >
          </div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section class="q-pa-md flex justify-center">
          <div class="q-pa-md" style="width: 400px">
            <form
              @submit.prevent.stop="onSubmit"
              @reset.prevent.stop="onReset"
              class="q-gutter-md"
            >
              <q-input
                square
                ref="editItemNameRef"
                filled
                label="Назва предмету"
              />

              <div>
                <q-btn label="Submit" type="submit" color="primary" />
                <q-btn
                  label="Reset"
                  type="reset"
                  color="primary"
                  flat
                  class="q-ml-sm"
                />
              </div>
            </form>
          </div>
        </q-card-section>
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
          <q-form @submit="typeStore.delete(deletedType.id)">
            <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
            <q-btn
              flat
              type="submit"
              color="negative"
              :loading="typeStore.dialogs.delete.isLoading"
              ><b>Так</b></q-btn
            >
          </q-form>
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from "vue";
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

let editedType = reactive({
  id: "",
  article: "",
  name: "",
});

let deletedType = reactive({
  id: "",
  name: "",
});

let searchInput = ref("");
let isSearching = ref(false);
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

function showCreateDialog() {
  newType.article = "";
  newType.name = "";
  typeStore.dialogs.create.isShown = !typeStore.dialogs.create.isShown;
}

function showRemoveDialog(id, name) {
  deletedType.id = id;
  deletedType.name = name;
  typeStore.dialogs.delete.isShown = true;
}

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
      devider.style.top = `${filter.offsetTop}px`;
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
