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
          <div :style="`min-width: ${fieldWidths.name}px; text-align: start`">
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
        <q-btn flat stretch class="filter-button">
          <div :style="`min-width: ${fieldWidths.type}px; text-align: start`">
            Вид
          </div>
          <q-menu
            self="bottom middle"
            :offset="[-fieldWidths.type / 2 - 16, 102]"
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
        <div class="filter-separator" name="type">
          <div class="vertical-line"></div>
        </div>
        <q-btn flat stretch class="filter-button">
          <div :style="`min-width: ${fieldWidths.gender}px; text-align: start`">
            Стать
          </div>
          <q-menu
            self="bottom middle"
            :offset="[-fieldWidths.gender / 2 - 16, 102]"
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
        <div class="filter-separator" name="gender">
          <div class="vertical-line"></div>
        </div>
        <q-btn flat stretch class="filter-button">
          <div :style="`min-width: ${fieldWidths.size}px; text-align: start`">
            Розмір
          </div>
          <q-menu
            self="bottom middle"
            :offset="[-fieldWidths.size / 2 - 16, 102]"
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
        <div class="filter-separator" name="size">
          <div class="vertical-line"></div>
        </div>

        <q-btn flat stretch class="filter-button">
          <div :style="`min-width: ${fieldWidths.color}px; text-align: start`">
            Колір
          </div>
          <q-menu
            self="bottom middle"
            :offset="[-fieldWidths.color / 2 - 16, 102]"
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
        <div class="filter-separator" name="color">
          <div class="vertical-line"></div>
        </div>

        <q-btn flat stretch class="filter-button">
          <div :style="`min-width: ${fieldWidths.amount}px; text-align: start`">
            Кількість
          </div>
          <q-menu
            self="bottom middle"
            :offset="[-fieldWidths.amount / 2 - 16, 102]"
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
        <div class="filter-separator" name="amount">
          <div class="vertical-line"></div>
        </div>
        <q-btn flat stretch class="filter-button">
          <div
            :style="`min-width: ${
              fieldWidths.units - filterWidthSettings.options.xScrollWidth
            }px; text-align: start`"
          >
            Одиниці
          </div>
          <q-menu
            self="bottom middle"
            :offset="[-fieldWidths.units / 2 - 16, 102]"
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
        <div class="filter-separator" name="units">
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
              filterWidthSettings.options.filterButtonXPadding
            "
          ></td>
          <td :width="filterWidthSettings.options.separatorWidth"></td>
          <td
            :width="
              fieldWidths.type +
              filterWidthSettings.options.filterButtonXPadding
            "
          ></td>
          <td :width="filterWidthSettings.options.separatorWidth"></td>
          <td
            :width="
              fieldWidths.gender +
              filterWidthSettings.options.filterButtonXPadding
            "
          ></td>
          <td :width="filterWidthSettings.options.separatorWidth"></td>
          <td
            :width="
              fieldWidths.size +
              filterWidthSettings.options.filterButtonXPadding
            "
          ></td>
          <td :width="filterWidthSettings.options.separatorWidth"></td>
          <td
            :width="
              fieldWidths.color +
              filterWidthSettings.options.filterButtonXPadding
            "
          ></td>
          <td :width="filterWidthSettings.options.separatorWidth"></td>
          <td
            :width="
              fieldWidths.amount +
              filterWidthSettings.options.filterButtonXPadding
            "
          ></td>
          <td :width="filterWidthSettings.options.separatorWidth"></td>
          <td
            :width="
              fieldWidths.units +
              filterWidthSettings.options.filterButtonXPadding -
              filterWidthSettings.options.xScrollWidth
            "
          ></td>
          <td :width="filterWidthSettings.options.separatorWidth + 4"></td>
        </tr>
        <template v-for="(item, index) in newArrayOfItems" :key="index">
          <item-component :itemInfo="item" :gap="5"></item-component>
        </template>
      </table>
    </div>

    <div class="footer">
      <div class="q-pa-lg flex flex-center">
        <q-pagination v-model="currentPage" :max="5" input />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import ItemComponent from "components/ItemComponent.vue";

import { useItemStore } from "src/stores/itemStore";

const itemsSequance = [
  "article",
  "name",
  "type",
  "gender",
  "size",
  "color",
  "amount",
  "units",
];
const groupedItemsButtonTooltip = computed(() => {
  return showGroupedItems.value ? "Розділити" : "Групувати";
});
const store = useItemStore();

let searchInput = ref("");
let isSearching = ref(false);
let showGroupedItems = ref(false);
let currentPage = ref(1);
let isCreateItemButtonActivated = ref(false);
let newArrayOfItems = [];
let fieldWidths = reactive({
  //px
  article: 0,
  name: 0,
  type: 0,
  gender: 0,
  size: 0,
  color: 0,
  amount: 0,
  units: 0,
});
let tempFieldWidths = reactive({
  //px
  article: 0,
  name: 0,
  type: 0,
  gender: 0,
  size: 0,
  color: 0,
  amount: 0,
  units: 0,
});
let filterWidthSettings = {
  fieldMinWidths: {
    //px
    article: 180,
    name: 260,
    type: 180,
    gender: 180,
    size: 180,
    color: 180,
    amount: 180,
    units: 180,
  },
  fieldWidthsInPercentages: {
    //%
    article: 14,
    name: 30,
    type: 14,
    gender: 14,
    size: 14,
    color: 14,
    amount: 14,
    units: 14,
  },
  options: {
    //px
    minFilterWidth: 90,
    separatorWidth: 10,
    xScrollWidth: 20,
    filterButtonXPadding: 32,
    //affected || straight
    resizeMode: "straight",
  },
};

function switchItemsView() {
  showGroupedItems.value = !showGroupedItems.value;
}

function createItemButtonAction() {
  isCreateItemButtonActivated.value = !isCreateItemButtonActivated.value;
}

onMounted(() => {
  //just placeholder for spamming more items
  let amountOfMultiplies = 10;
  let tempItemsList = [...store.itemsList];
  let lengthOfItemsList = tempItemsList.length;
  for (let i = 0; i < amountOfMultiplies; i++) {
    tempItemsList.forEach((item, index) => {
      newArrayOfItems.push(Object.assign({}, item));
      newArrayOfItems[index + lengthOfItemsList * i].id =
        index + lengthOfItemsList * i;
    });
  }
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

        document.body.onmouseup = () => {
          onSeparatorRelease();
        };
      };
    };
    separatorObject.onmouseup = () => {
      onSeparatorRelease();
    };
  }

  for (let i = 0; i < itemsSequance.length; i++) {
    let currentItem = document.querySelector(
      `.filter-separator[name='${itemsSequance[i]}']`
    );

    if (filterWidthSettings.options.resizeMode == "straight") {
      addEventToSeparator(currentItem, itemsSequance[i]);
    }

    if (filterWidthSettings.options.resizeMode == "affected") {
      if (i > itemsSequance.length - 2) {
        continue;
      }
      addEventToSeparator(currentItem, itemsSequance[i], itemsSequance[i + 1]);
    }
  }
});
// document.body.addEventListener("click", function (e) {
//   console.log("bodya");
//   console.log(e);
// });
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
.filter-width-helper {
  width: 1px;
  position: absolute;
  z-index: 9999;
  border-left: 2px solid #a32cc7;
  top: 0;
  left: 0;
}
.filter {
  width: fit-content;
  position: sticky;
  top: 0px;
  z-index: 9999;
}
div[name] {
  cursor: col-resize;
}
.filter-separator {
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
  padding: 0px 20px 30px 10px;
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
  min-height: var(--footer-height);
  background-color: beige;
}
</style>
