<template>
  <div class="container">
    <div class="row toolbar q-px-md q-mt-md">
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
        <q-tooltip anchor="center left" self="center right" :offset="[10, 10]">
          <span style="font-size: 1.3em">Пошук за складами</span>
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
        <q-tooltip anchor="bottom left" :offset="[-18, 7]">
          <span style="font-size: 1.3em">Зарахувати надходження</span>
        </q-tooltip>
      </q-btn>
      <q-btn flat round color="black" icon="arrow_upward">
        <q-tooltip anchor="bottom left" :offset="[-20, 7]">
          <span style="font-size: 1.3em">Зарахувати списання</span>
        </q-tooltip>
      </q-btn>
      <q-btn
        flat
        round
        color="black"
        @click="switchItemsView"
        :icon="showGroupedItems ? 'unfold_more' : 'unfold_less'"
      >
        <q-tooltip anchor="bottom left" :offset="[-20, 7]">
          <span style="font-size: 1.3em">{{ groupedItemsButtonTooltip }}</span>
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
        >
          <span style="font-size: 1.3em">Створити</span>
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
        <q-tooltip anchor="bottom left" :offset="[0, 7]">
          <span style="font-size: 1.3em">Перемістити</span>
        </q-tooltip>
      </q-btn>
    </div>

    <div class="content">
      <q-toolbar class="text-black q-mt-md filter q-px-none">
        <q-btn flat stretch class="filter-button">
          <div
            :style="`width: ${filterSettings.fieldWidths.name}px; text-align: start`"
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
            :style="`width: ${filterSettings.fieldWidths.type}px; text-align: start`"
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
            :style="`width: ${filterSettings.fieldWidths.gender}px; text-align: start`"
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
            :style="`width: ${filterSettings.fieldWidths.size}px; text-align: start`"
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
            :style="`width: ${filterSettings.fieldWidths.color}px; text-align: start`"
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
            :style="`width: ${filterSettings.fieldWidths.amount}px; text-align: start`"
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

      <template v-for="item in itemsList" :key="item.id">
        <item-component
          :itemInfo="item"
          :cellsWidth="filterSettings.fieldWidths"
        ></item-component>
      </template>
    </div>
    <div class="row footer q-mt-md"></div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import ItemComponent from "components/ItemComponent.vue";

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
  fieldWidthsInPercentages: {
    //%
    name: 45,
    type: 11,
    gender: 11,
    size: 11,
    color: 11,
    amount: 11,
  },
  params: {
    //px
    minFilterButtonWidth: 100,
  },
});

let itemsList = reactive([
  {
    id: 1,
    name: "Sweet Hoody Test of Rookola",
    type: {
      name: "Худі",
      icon: "/src/assets/magenta-menu-logo.png",
    },
    gender: {
      name: "Чоловіча",
      icon: "/src/assets/magenta-menu-logo.png",
    },
    size: {
      name: "XXL",
      description: "chest 150 sm, etc",
    },
    color: {
      name: "Червоний",
      value: "#eb4034",
      textColor: "#ffffff",
    },
    amount: 3568,
  },
  {
    id: 2,
    name: "Sweet Hoody 2 Test of Rookola",
    type: {
      name: "Пуді",
      icon: "/src/assets/magenta-menu-logo.png",
    },
    gender: {
      name: "Жіноча",
      icon: "/src/assets/magenta-menu-logo.png",
    },
    size: {
      name: "L",
      description: "chest 5 sm, etc",
    },
    color: {
      name: "Зелений",
      value: "#27db21",
      textColor: "#000000",
    },
    amount: 60999,
  },
]);

function switchItemsView() {
  showGroupedItems.value = !showGroupedItems.value;
}

function createItemButtonAction() {
  isCreateItemButtonActivated.value = !isCreateItemButtonActivated.value;
}

const groupedItemsButtonTooltip = computed(() => {
  return showGroupedItems.value ? "Розділити" : "Групувати";
});

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

    fieldNumber += 1;
  }

  let nameSeparator = document.querySelector(".name-separator");
  let typeSeparator = document.querySelector(".type-separator");
  let genderSeparator = document.querySelector(".gender-separator");
  let sizeSeparator = document.querySelector(".size-separator");
  let colorSeparator = document.querySelector(".color-separator");
  // let amountSeparator = document.querySelector(".amount-separator");
  let filterButtons = document.querySelectorAll(".filter-button");

  function addEventToSeparator(separatorObject, fieldName, affectedFieldName) {
    separatorObject.onmousedown = (mouseDownEvent) => {
      filterButtons.forEach((button) => {
        button.style.cursor = "col-resize";
      });

      let initCursorCoord = mouseDownEvent.screenX;
      let initFieldWidth = filterSettings.fieldWidths[fieldName];
      let initAffectedFieldWidth =
        filterSettings.fieldWidths[affectedFieldName];

      document.body.onmousemove = (mouseMoveEvent) => {
        // if (
        //   filterSettings.fieldWidths[fieldName] >
        //   filterSettings.params.minFilterButtonWidth
        // ) {
        //   filterSettings.fieldWidths[fieldName] =
        //     initFieldWidth + mouseMoveEvent.screenX - initCursorCoord;
        // }

        // if (
        //   filterSettings.fieldWidths[affectedFieldName] >
        //   filterSettings.params.minFilterButtonWidth
        // ) {
        //   filterSettings.fieldWidths[affectedFieldName] =
        //     initAffectedFieldWidth - mouseMoveEvent.screenX + initCursorCoord;
        // }
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
          filterButtons.forEach((button) => {
            button.style.cursor = "pointer";
          });
        };
      };
    };
    separatorObject.onmouseup = () => {
      document.body.onmousemove = null;
      document.body.onmouseup = null;
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
.container {
  display: flex;
  flex-direction: column;
  height: 100%;
}
.toolbar {
  overflow: visible;
  height: auto;
}
.filter {
  width: fit-content;
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
  overflow: auto;
  height: 100%;
  width: 100%;
  padding: 0px 10px;
}
.footer {
  min-height: 50px;
  background-color: beige;
}
</style>
