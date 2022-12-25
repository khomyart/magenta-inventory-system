<template>
  <q-layout view="hHh Lpr lff" container style="height: 100vh">
    <q-header class="bg-white">
      <q-toolbar class="text-primary q-ml-sm">
        <q-btn
          stretch
          flat
          style="margin-right: -5px"
          @click="showToolbarTitle = !showToolbarTitle"
        >
          <img src="../assets/magenta-menu-logo.png" style="height: 35px" />
        </q-btn>

        <q-toolbar-title>
          <transition appear name="title-appearing">
            <span v-if="showToolbarTitle">Magenta print</span>
          </transition>
        </q-toolbar-title>

        <q-separator vertical />
        <q-btn
          color="positive"
          flat
          icon-right="arrow_downward"
          size="13px"
          square
          stretch
        >
          <span class="toolbar-icon-number"> 350 </span>
          <q-tooltip
            :offset="[10, 10]"
            class="bg-white"
            style="padding: 0; border: 1px solid rgb(192, 192, 192)"
          >
            <div class="toolbar-tooltip-content">
              <div class="toolbar-tooltip-content-header">Надходження</div>
              <q-separator></q-separator>
              <div class="toolbar-tooltip-content-body"></div>
            </div>
          </q-tooltip>
        </q-btn>
        <q-separator vertical />
        <q-btn
          color="negative"
          flat
          icon-right="arrow_upward"
          size="13px"
          square
          stretch
        >
          <span class="toolbar-icon-number"> 210 </span>
          <q-tooltip
            :offset="[10, 10]"
            class="bg-white"
            style="padding: 0; border: 1px solid rgb(192, 192, 192)"
          >
            <div class="toolbar-tooltip-content">
              <div class="toolbar-tooltip-content-header">Витрати</div>
              <q-separator></q-separator>
              <div class="toolbar-tooltip-content-body"></div>
            </div>
          </q-tooltip>
        </q-btn>
        <q-btn
          color="negative"
          flat
          icon-right="priority_high"
          size="13px"
          square
          stretch
        >
          <span class="toolbar-icon-number"> 3 </span>
          <q-tooltip
            :offset="[10, 10]"
            class="bg-white"
            style="padding: 0; border: 1px solid rgb(192, 192, 192)"
          >
            <div class="toolbar-tooltip-content">
              <div class="toolbar-tooltip-content-header">Нестача</div>
              <q-separator></q-separator>
              <div class="toolbar-tooltip-content-body"></div>
            </div>
          </q-tooltip>
        </q-btn>
        <q-separator vertical />
        <q-space></q-space>
        <q-btn color="primary" icon="settings" flat round class="q-mr-sm">
          <q-menu :offset="[0, 12]">
            <q-list style="min-width: 100px">
              <q-item clickable v-close-popup>
                <q-item-section>Налаштування</q-item-section>
              </q-item>
              <q-item clickable v-close-popup>
                <q-item-section>Вихід</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </q-toolbar>
      <q-separator inset />
    </q-header>

    <q-drawer show-if-above :width="200" :breakpoint="0">
      <q-scroll-area class="fit">
        <q-list padding class="menu-list">
          <template v-for="(menuItem, index) in menuItems" :key="index">
            <q-separator
              inset
              v-if="menuItem.type == 'separator'"
              class="q-my-md"
            ></q-separator>
            <q-item
              exact
              :to="menuItem.to"
              clickable
              v-ripple
              v-if="menuItem.type == 'item'"
            >
              <q-item-section avatar>
                <q-icon :name="menuItem.icon" />
              </q-item-section>

              <q-item-section> {{ menuItem.name }} </q-item-section>
            </q-item>
            <div
              class="menu-header q-mt-md q-ml-md"
              v-if="menuItem.type == 'header'"
            >
              {{ menuItem.name }}
            </div>
          </template>
        </q-list>
      </q-scroll-area>
    </q-drawer>

    <q-page-container>
      <q-page>
        <div class="content-container">
          <router-view></router-view>
        </div>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref } from "vue";

let showToolbarTitle = ref(false);
const menuItems = [
  {
    name: "Предмети",
    type: "header",
  },
  {
    name: "Перелік",
    icon: "apps",
    to: "/items",
    type: "item",
  },
  {
    name: "Лог дій",
    icon: "checklist", //edit_square, bug_report
    to: "/logs",
    type: "item",
  },
  {
    type: "separator",
  },
  {
    name: "Характеристики",
    type: "header",
  },
  {
    name: "Види",
    icon: "interests",
    to: "/types",
    type: "item",
  },
  {
    name: "Розміри",
    icon: "straighten",
    to: "/sizes",
    type: "item",
  },
  {
    name: "Гендери",
    icon: "face_retouching_natural",
    to: "/genders",
    type: "item",
  },
  {
    name: "Кольори",
    icon: "palette",
    to: "/colors",
    type: "item",
  },
  {
    name: "Склади",
    icon: "warehouse",
    to: "/warehouses",
    type: "item",
  },
  {
    type: "separator",
  },
  {
    name: "Налаштування",
    type: "header",
  },
  {
    name: "Користувачі",
    icon: "manage_accounts",
    to: "/users",
    type: "item",
  },
];
</script>

<style scoped>
.scroll {
  /* overflow: hidden !important; */
}
.menu-list .q-item {
  border-radius: 0 10px 10px 0;
}
.menu-header {
  font-size: 17px;
}
.toolbar-icon-number {
  font-size: 17px;
  margin-right: 5px;
}

.toolbar-tooltip-content {
  background: white;
  color: black;
  padding: 2px;
  font-size: 14px;
  min-width: 250px;
}
.toolbar-tooltip-content-header {
  font-size: 1.2em;
  padding: 5px 0px 0px 10px;
}

.content-container {
  /* min-height: calc(100vh - 51px); */
  height: calc(100vh - 51px);
  /* max-height: calc(100vh - 51px); 50px - height of toolbar */
}

/* animations */
.title-appearing-enter-from,
.title-appearing-leave-to {
  opacity: 0;
}
.title-appearing-enter-to,
.title-appearing-leave-from {
  opacity: 1;
}
.title-appearing-enter-active,
.title-appearing-leave-active {
  transition: all 0.5s ease-in-out;
}
</style>
