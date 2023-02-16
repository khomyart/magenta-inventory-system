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
          <q-menu :offset="[0, 12]" style="min-width: 200px; text-align: left">
            <q-list>
              <q-item>
                <q-item-section style="text-align: center">
                  {{ userStore.data.name }}
                </q-item-section>
              </q-item>
              <q-separator />
              <q-item clickable v-close-popup>
                <q-item-section>Налаштування</q-item-section>
              </q-item>
              <q-item clickable v-close-popup>
                <q-item-section @click="logout">Вихід</q-item-section>
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
              @click="menuItem.onClick"
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
        <div class="page-holder">
          <router-view></router-view>
        </div>
      </q-page>
    </q-page-container>
  </q-layout>

  <q-dialog
    persistent
    v-model="appConfigStore.reauth.dialogs.renewPassword.isShown"
    transition-show="scale"
    transition-hide="scale"
  >
    <q-card style="width: 400px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon
            size="md"
            class="q-mr-sm"
            name="admin_panel_settings"
            color="black"
          ></q-icon>
          Автентифікація
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="renewSession">
        <q-card-section class="q-pt-md">
          Термін дії сесії закінчився. Введіть пароль користувача ({{
            userStore.data.email
          }}), щоб відновити роботу:
        </q-card-section>

        <q-card-section class="q-pt-sm">
          <q-input
            class="q-pt-none"
            v-model="sessionRenewPassword"
            outlined
            :type="!showSessionRenewPassword ? 'password' : 'text'"
            label="Пароль"
          >
            <template v-slot:append>
              <q-icon
                :name="
                  !showSessionRenewPassword ? 'visibility_off' : 'visibility'
                "
                class="cursor-pointer"
                @click="showSessionRenewPassword = !showSessionRenewPassword"
              />
            </template>
          </q-input>
        </q-card-section>
        <q-card-section class="q-pt-none" style="text-align: end">
          Залишилось спроб: {{ appConfigStore.attemptsLeft }}</q-card-section
        >
        <q-separator></q-separator>
        <q-card-actions align="right">
          <q-btn flat color="black" @click="logout"
            ><b>Сторікна автентифікації</b></q-btn
          >
          <q-btn
            flat
            color="primary"
            type="submit"
            :loading="appConfigStore.reauth.dialogs.renewPassword.isLoading"
            ><b>Відновити</b></q-btn
          >
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <q-dialog
    persistent
    v-model="appConfigStore.reauth.dialogs.unauthenticated.isShown"
    transition-show="scale"
    transition-hide="scale"
  >
    <q-card style="width: 400px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon
            size="md"
            class="q-mr-sm"
            name="admin_panel_settings"
            color="black"
          ></q-icon>
          Автентифікація
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="logout">
        <q-card-section class="q-pt-md">
          Сесія користувача була закрита, або користувач не ідентифікований. Ви
          будете перенаправлені на сторінку автентифікації.
        </q-card-section>

        <q-separator></q-separator>
        <q-card-actions align="right">
          <q-btn
            flat
            color="primary"
            type="submit"
            :loading="appConfigStore.reauth.dialogs.unauthenticated.isLoading"
            ><b>Гаразд ({{ appConfigStore.secondsToLogoutLeft }})</b></q-btn
          >
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import { useAppConfigStore } from "src/stores/appConfigStore";
import { useUserStore } from "src/stores/userStore";
import { useTypeStore } from "src/stores/typeStore";

const router = useRouter();
const userStore = useUserStore();
const typeStore = useTypeStore();
const appConfigStore = useAppConfigStore();

let showToolbarTitle = ref(false);
let sessionRenewPassword = ref("");
let showSessionRenewPassword = ref(false);

const menuItems = [
  {
    name: "Предмети",
    type: "header",
  },
  {
    name: "Перелік",
    icon: "apps",
    to: { name: "Items" },
    onClick: () => {},
    type: "item",
  },
  {
    name: "Лог дій",
    icon: "checklist", //edit_square, bug_report
    to: { name: "Logs" },
    onClick: () => {},
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
    onClick: () => {
      if (
        router.currentRoute.value.name == "Types" &&
        appConfigStore.currentPages.types != 1
      ) {
        appConfigStore.currentPages.types = 1;
      } else if (
        router.currentRoute.value.name != "Types" &&
        appConfigStore.currentPages.types != 1
      ) {
        // console.log(123);
        appConfigStore.currentPages.types = 1;
        typeStore.receive(
          appConfigStore.amountOfItemsPerPages.types,
          appConfigStore.currentPages.types
        );
      } else {
        typeStore.receive(
          appConfigStore.amountOfItemsPerPages.types,
          appConfigStore.currentPages.types
        );
      }
    },
    type: "item",
  },
  {
    name: "Розміри",
    icon: "straighten",
    to: { name: "Sizes" },
    onClick: () => {},
    type: "item",
  },
  {
    name: "Гендери",
    icon: "face_retouching_natural",
    to: { name: "Genders" },
    onClick: () => {},
    type: "item",
  },
  {
    name: "Кольори",
    icon: "palette",
    to: { name: "Colors" },
    onClick: () => {},
    type: "item",
  },
  {
    name: "Склади",
    icon: "warehouse",
    to: { name: "Warehouses" },
    onClick: () => {},
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
    to: { name: "Users" },
    onClick: () => {},
    type: "item",
  },
];

function logout() {
  appConfigStore.reauth.dialogs.unauthenticated.isLoading = true;
  userStore.logout().finally(() => {
    sessionStorage.removeItem("data");
    userStore.data.email = "";
    userStore.data.name = "";
    userStore.data.token.value = null;
    userStore.data.token.expiredAt = "";
    appConfigStore.reauth.data.attempt = 0;
    appConfigStore.reauth.data.changingSecondsToLogout = 0;
    appConfigStore.reauth.dialogs.renewPassword.isShown = false;
    appConfigStore.reauth.dialogs.unauthenticated.isLoading = false;
    appConfigStore.reauth.dialogs.unauthenticated.isShown = false;
    router.push("/login");
  });
}

function renewSession() {
  appConfigStore.reauth.dialogs.renewPassword.isLoading = true;
  userStore
    .renewSession(sessionRenewPassword.value)
    .then((res) => {
      let userData = JSON.parse(sessionStorage.getItem("data"));

      userData.token = res.data.auth.token;
      userData.expired_at = res.data.auth.expired_at;
      userStore.data.token.value = res.data.auth.token;
      userStore.data.token.expiredAt = res.data.auth.expired_at;

      sessionStorage.setItem("data", JSON.stringify(userData));

      appConfigStore.reauth.dialogs.renewPassword.isShown = false;
      window.location.reload();
    })
    .catch((err) => {
      appConfigStore.catchRequestError(err);
    })
    .finally(() => {
      appConfigStore.reauth.dialogs.renewPassword.isLoading = false;
      sessionRenewPassword.value = "";
    });
}

watch(
  () => appConfigStore.reauth.data.attempt,
  (attempt) => {
    if (attempt >= appConfigStore.reauth.data.attemptsAllowed) {
      logout();
    }
  }
);

let logoutInterval;
watch(
  () => appConfigStore.reauth.dialogs.unauthenticated.isShown,
  (isShown) => {
    if (isShown) {
      appConfigStore.reauth.data.secondsToLogout;
      logoutInterval = setInterval(() => {
        appConfigStore.reauth.data.changingSecondsToLogout += 1;
        if (
          appConfigStore.reauth.data.changingSecondsToLogout >=
          appConfigStore.reauth.data.secondsToLogout
        ) {
          clearInterval(logoutInterval);
          logout();
        }
      }, 1000);
    }
  }
);

onMounted(() => {
  let userData = JSON.parse(sessionStorage.getItem("data"));
  if (typeof userData === "object") {
    userStore.data.email = userData.email;
    userStore.data.name = userData.name;
    userStore.data.token.expiredAt = userData.expired_at;
    userStore.data.token.value = userData.token;
  }
});
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

.page-holder {
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
