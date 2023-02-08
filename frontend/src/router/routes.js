const routes = [
  {
    path: "/login",
    name: "Login",
    component: () => import("layouts/LoginLayout.vue"),
  },
  {
    path: "/",
    meta: {
      isAuthNeeded: true,
    },
    redirect: "/items",
    component: () => import("layouts/MainLayout.vue"),
    children: [
      {
        path: "/items",
        name: "Items",
        component: () => import("pages/ItemsPage.vue"),
      },
      {
        path: "/logs",
        name: "Logs",
        component: () => import("pages/LogsPage.vue"),
      },
      {
        path: "/types/:page",
        name: "Types",
        component: () => import("pages/TypesPage.vue"),
      },
      {
        path: "/sizes",
        name: "Sizes",
        component: () => import("pages/SizesPage.vue"),
      },
      {
        path: "/genders",
        name: "Genders",
        component: () => import("pages/GendersPage.vue"),
      },
      {
        path: "/colors",
        name: "Colors",
        component: () => import("pages/ColorsPage.vue"),
      },
      {
        path: "/warehouses",
        name: "Warehouses",
        component: () => import("src/pages/WarehousesPage.vue"),
      },
      {
        path: "/users",
        name: "Users",
        component: () => import("pages/UsersPage.vue"),
      },
    ],
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: "/:catchAll(.*)*",
    component: () => import("pages/ErrorNotFound.vue"),
  },
];

export default routes;
