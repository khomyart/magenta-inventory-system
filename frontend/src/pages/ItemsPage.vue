<template>
  <div class="page">
    <div class="toolbar row q-px-md q-my-md">
      <q-input
        v-model="searchInput"
        debounce="500"
        outlined
        placeholder="Введіть назву предмету"
        rounded
        dense
        class="col-4"
        :loading="isSearching"
        @keydown.enter="isSearching = isSearching == false ? true : false"
      >
        <template v-slot:append v-if="!isSearching">
          <q-icon name="search" />
        </template>
      </q-input>
      <q-space></q-space>
      <q-btn flat round color="white" text-color="black" icon="warehouse">
        <q-badge color="red" floating rounded> </q-badge>
        <q-tooltip
          class="bg-black text-body2"
          anchor="center left"
          self="center right"
          :offset="[10, 10]"
        >
          Пошук за складами
        </q-tooltip>
        <q-menu self="bottom middle" :offset="[-20, -50]">
          <q-list style="min-width: 150px">
            <q-item clickable v-close-popup>
              <q-item-section>New tab</q-item-section>
            </q-item>
            <q-item clickable v-close-popup>
              <q-item-section>New incognito tab</q-item-section>
            </q-item>
            <q-separator />
            <q-item clickable v-close-popup>
              <q-item-section>Recent tabs</q-item-section>
            </q-item>
            <q-item clickable v-close-popup>
              <q-item-section>History</q-item-section>
            </q-item>
            <q-item clickable v-close-popup>
              <q-item-section>Downloads</q-item-section>
            </q-item>
            <q-separator />
            <q-item clickable v-close-popup>
              <q-item-section>Settings</q-item-section>
            </q-item>
            <q-separator />
            <q-item clickable v-close-popup>
              <q-item-section>Help &amp; Feedback</q-item-section>
            </q-item>
          </q-list>
        </q-menu>
      </q-btn>
      <q-separator vertical class="q-mx-sm"></q-separator>
      <q-btn flat round color="black" icon="arrow_downward">
        <q-tooltip
          class="bg-black text-body2"
          anchor="bottom left"
          :offset="[-18, 7]"
        >
          Зарахувати надходження
        </q-tooltip>
      </q-btn>
      <q-btn flat round color="black" icon="arrow_upward">
        <q-tooltip
          class="bg-black text-body2"
          anchor="bottom left"
          :offset="[-20, 7]"
        >
          Зарахувати списання
        </q-tooltip>
      </q-btn>
      <q-btn
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
      </q-btn>
      <q-btn
        flat
        round
        color="black"
        icon="add"
        @click="createItemButtonAction"
      >
        <q-tooltip
          v-model="isCreateItemButtonActivated"
          anchor="bottom left"
          :offset="[-20, 7]"
          class="bg-black text-body2"
        >
          Створити
        </q-tooltip>
        <q-menu self="bottom middle" :offset="[0, -50]">
          <q-list style="min-width: 150px">
            <q-item clickable v-close-popup>
              <q-item-section>Одиночний предмет</q-item-section>
            </q-item>
            <q-item clickable v-close-popup>
              <q-item-section>Групу предметів</q-item-section>
            </q-item>
          </q-list>
        </q-menu>
      </q-btn>
      <q-btn flat round color="black" icon="sync_alt">
        <q-tooltip
          class="bg-black text-body2"
          anchor="bottom left"
          :offset="[0, 7]"
        >
          Перемістити
        </q-tooltip>
      </q-btn>
    </div>

    <div class="content">
      <q-toolbar class="text-black filter q-px-none bg-white">
        <q-btn flat stretch class="filter-button">
          <div
            :style="`min-width: ${filterSettings.fieldWidths.name}px; text-align: start`"
          >
            Назва
          </div>
          <q-menu
            self="bottom middle"
            :offset="[-filterSettings.fieldWidths.name / 2 - 16, 102]"
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
        <div class="filter-separator name-separator">
          <div class="vertical-line"></div>
        </div>
        <q-btn flat stretch class="filter-button">
          <div
            :style="`min-width: ${filterSettings.fieldWidths.type}px; text-align: start`"
          >
            Вид
          </div>
          <q-menu
            self="bottom middle"
            :offset="[-filterSettings.fieldWidths.type / 2 - 16, 102]"
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
        <div class="filter-separator type-separator">
          <div class="vertical-line"></div>
        </div>
        <q-btn flat stretch class="filter-button">
          <div
            :style="`min-width: ${filterSettings.fieldWidths.gender}px; text-align: start`"
          >
            Стать
          </div>
          <q-menu
            self="bottom middle"
            :offset="[-filterSettings.fieldWidths.gender / 2 - 16, 102]"
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
        <div class="filter-separator gender-separator">
          <div class="vertical-line"></div>
        </div>
        <q-btn flat stretch class="filter-button">
          <div
            :style="`min-width: ${filterSettings.fieldWidths.size}px; text-align: start`"
          >
            Розмір
          </div>
          <q-menu
            self="bottom middle"
            :offset="[-filterSettings.fieldWidths.size / 2 - 16, 102]"
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
        <div class="filter-separator size-separator">
          <div class="vertical-line"></div>
        </div>

        <q-btn flat stretch class="filter-button">
          <div
            :style="`min-width: ${filterSettings.fieldWidths.color}px; text-align: start`"
          >
            Колір
          </div>
          <q-menu
            self="bottom middle"
            :offset="[-filterSettings.fieldWidths.color / 2 - 16, 102]"
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
        <div class="filter-separator color-separator">
          <div class="vertical-line"></div>
        </div>

        <q-btn flat stretch class="filter-button">
          <div
            :style="`min-width: ${
              filterSettings.fieldWidths.amount -
              filterSettings.params.xScrollWidth
            }px; text-align: start`"
          >
            Кількість
          </div>
          <q-menu
            self="bottom middle"
            :offset="[-filterSettings.fieldWidths.amount / 2 - 16, 102]"
          >
            <q-list style="min-width: 250px">
              <q-item clickable v-close-popup>
                <q-item-section>New tab 1</q-item-section>
              </q-item>
              <q-item clickable v-close-popup>
                <q-item-section>New incognito tab</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </q-toolbar>
      <table class="items">
        <tr>
          <td :width="filterSettings.fieldWidths.name + 32"></td>
          <td :width="filterSettings.params.separatorWidth"></td>
          <td :width="filterSettings.fieldWidths.type + 32"></td>
          <td :width="filterSettings.params.separatorWidth"></td>
          <td :width="filterSettings.fieldWidths.gender + 32"></td>
          <td :width="filterSettings.params.separatorWidth"></td>
          <td :width="filterSettings.fieldWidths.size + 32"></td>
          <td :width="filterSettings.params.separatorWidth"></td>
          <td :width="filterSettings.fieldWidths.color + 32"></td>
          <td :width="filterSettings.params.separatorWidth"></td>
          <td
            :width="
              filterSettings.fieldWidths.amount +
              30 -
              filterSettings.params.xScrollWidth
            "
          ></td>
        </tr>
        <template v-for="(item, index) in store.itemsList" :key="index">
          <item-component
            :itemInfo="item"
            :cellsWidth="filterSettings.fieldWidths"
          ></item-component>
        </template>
      </table>
    </div>
    <div class="row footer q-mt-md"></div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import ItemComponent from "components/ItemComponent.vue";

import { useItemStore } from "src/stores/itemStore";

let searchInput = ref("");
let isSearching = ref(false);
let showGroupedItems = ref(false);
let isCreateItemButtonActivated = ref(false);

let filterSettings = reactive({
  fieldWidths: {
    //px
    name: 0,
    type: 0,
    gender: 0,
    size: 0,
    color: 0,
    amount: 0,
  },
  fieldMinWidths: {
    //px
    name: 260,
    type: 150,
    gender: 150,
    size: 150,
    color: 150,
    amount: 150,
  },
  fieldWidthsInPercentages: {
    //%
    name: 30,
    type: 14,
    gender: 14,
    size: 14,
    color: 14,
    amount: 14,
  },
  params: {
    //px
    minFilterButtonWidth: 140,
    separatorWidth: 11,
    xScrollWidth: 10,
  },
});

const groupedItemsButtonTooltip = computed(() => {
  return showGroupedItems.value ? "Розділити" : "Групувати";
});
const store = useItemStore();

function switchItemsView() {
  showGroupedItems.value = !showGroupedItems.value;
}

function createItemButtonAction() {
  isCreateItemButtonActivated.value = !isCreateItemButtonActivated.value;
}

onMounted(() => {
  //set up default values for filter fields width according to config
  let contentElement = document.querySelector(".content");
  //get .content div padding
  let contentWidth = contentElement.offsetWidth;
  let contentPaddingX =
    parseFloat(getComputedStyle(contentElement).paddingLeft) +
    parseFloat(getComputedStyle(contentElement).paddingRight);
  //get width of separator
  let separatorWidth = document.querySelector(".filter-separator").offsetWidth;

  let fieldNumber = 1;
  for (const fieldName in filterSettings.fieldWidths) {
    filterSettings.fieldWidths[fieldName] =
      contentWidth *
        (filterSettings.fieldWidthsInPercentages[fieldName] / 100) -
      32;
    //substracting value according to container padding devided by amount of filter parameters
    filterSettings.fieldWidths[fieldName] -=
      contentPaddingX / Object.keys(filterSettings.fieldWidths).length;
    //if filter item is not last -> substract width of separator
    if (Object.keys(filterSettings.fieldWidths).length != fieldNumber) {
      filterSettings.fieldWidths[fieldName] -= separatorWidth;
    }
    //set minimum width of fields in case, if calculated is less than set minimum
    if (
      filterSettings.fieldWidths[fieldName] <
      filterSettings.fieldMinWidths[fieldName]
    ) {
      filterSettings.fieldWidths[fieldName] =
        filterSettings.fieldMinWidths[fieldName];
    }

    fieldNumber += 1;
  }

  let nameSeparator = document.querySelector(".name-separator");
  let typeSeparator = document.querySelector(".type-separator");
  let genderSeparator = document.querySelector(".gender-separator");
  let sizeSeparator = document.querySelector(".size-separator");
  let colorSeparator = document.querySelector(".color-separator");
  // let amountSeparator = document.querySelector(".amount-separator");
  let filterButtons = document.querySelectorAll(".filter-button");

  let qApp = document.querySelector("#q-app");

  function addEventToSeparator(separatorObject, fieldName, affectedFieldName) {
    separatorObject.onmousedown = (mouseDownEvent) => {
      filterButtons.forEach((button) => {
        button.style.cursor = "col-resize";
      });

      qApp.classList.add("disable-interaction");
      // items.classList.add("disable-interaction");

      let initCursorCoord = mouseDownEvent.screenX;
      let initFieldWidth = filterSettings.fieldWidths[fieldName];
      let initAffectedFieldWidth =
        filterSettings.fieldWidths[affectedFieldName];

      document.body.onmousemove = (mouseMoveEvent) => {
        if (
          initFieldWidth + mouseMoveEvent.screenX - initCursorCoord >
            filterSettings.params.minFilterButtonWidth &&
          initAffectedFieldWidth - mouseMoveEvent.screenX + initCursorCoord >
            filterSettings.params.minFilterButtonWidth
        ) {
          filterSettings.fieldWidths[fieldName] =
            initFieldWidth + mouseMoveEvent.screenX - initCursorCoord;
          filterSettings.fieldWidths[affectedFieldName] =
            initAffectedFieldWidth - mouseMoveEvent.screenX + initCursorCoord;
        }

        document.body.onmouseup = () => {
          document.body.onmousemove = null;
          document.body.onmouseup = null;
          qApp.classList.remove("disable-interaction");
          filterButtons.forEach((button) => {
            button.style.cursor = "pointer";
          });
        };
      };
    };
    separatorObject.onmouseup = () => {
      document.body.onmousemove = null;
      document.body.onmouseup = null;
      qApp.classList.remove("disable-interaction");
      filterButtons.forEach((button) => {
        button.style.cursor = "pointer";
      });
    };
  }

  addEventToSeparator(nameSeparator, "name", "type");
  addEventToSeparator(typeSeparator, "type", "gender");
  addEventToSeparator(genderSeparator, "gender", "size");
  addEventToSeparator(sizeSeparator, "size", "color");
  addEventToSeparator(colorSeparator, "color", "amount");
  // addEventToSeparator(amountSeparator, "amount");
});
</script>

<style>
:root {
  --footer-height: 50px;
}
.disable-interaction {
  pointer-events: none;
  user-select: none;
}
.page {
  display: flex;
  flex-direction: column;
  height: 100%;
}
.content {
  width: fit-content;
  overflow: auto;
  height: 100%;
  width: 100%;
  padding: 0px 5px 30px 10px;
}
.toolbar {
  overflow: visible;
  height: auto;
}
.filter {
  width: fit-content;
  position: sticky;
  top: 0px;
}
.filter-separator {
  cursor: col-resize;
  padding: 0 5px;
  height: 100%;
}
.vertical-line {
  height: 40px;
  border-left: 1px solid rgba(0, 0, 0, 0.12);
}
.content {
  width: fit-content;
  overflow: auto;
  height: 100%;
  width: 100%;
  padding: 0px 3px 30px 10px;
}
.items-container {
  width: fit-content;
}
.items {
  border-collapse: collapse;
  table-layout: fixed;
  width: 100px;
}
.footer {
  min-height: 50px;
  background-color: beige;
}
</style>
