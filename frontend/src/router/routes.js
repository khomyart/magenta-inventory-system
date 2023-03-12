const routes = [
  {
    path: "/login",
    name: "login",
    component: () => import("layouts/LoginLayout.vue"),
  },
  {
    path: "/",
    meta: {
      isAuthNeeded: true,
    },
    redirect: "/dashboard",
    component: () => import("layouts/MainLayout.vue"),
    children: [
      {
        path: "/dashboard",
        name: "dashboard",
        component: () => import("pages/DashboardPage.vue"),
      },
      {
        path: "/items",
        name: "items",
        component: () => import("pages/ItemsPage.vue"),
      },
      {
        path: "/logs",
        name: "logs",
        component: () => import("pages/LogsPage.vue"),
      },
      {
        path: "/types",
        name: "types",
        redirect: "/types/1",
        children: [
          {
            path: ":page",
            component: () => import("pages/TypesPage.vue"),
          },
        ],
      },
      {
        path: "/sizes",
        name: "sizes",
        redirect: "/sizes/1",
        children: [
          {
            path: ":page",
            component: () => import("pages/SizesPage.vue"),
          },
        ],
      },
      {
        path: "/genders",
        name: "genders",
        component: () => import("pages/GendersPage.vue"),
      },
      {
        path: "/colors",
        name: "colors",
        component: () => import("pages/ColorsPage.vue"),
      },
      {
        path: "/warehouses",
        name: "warehouses",
        component: () => import("src/pages/WarehousesPage.vue"),
      },
      {
        path: "/users",
        name: "users",
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
