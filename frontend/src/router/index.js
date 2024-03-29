import { route } from "quasar/wrappers";
import {
  createRouter,
  createMemoryHistory,
  createWebHistory,
  createWebHashHistory,
} from "vue-router";
import routes from "./routes";

//is user authorized for needed route
function isValidFor(action, section) {
  let userData = JSON.parse(sessionStorage.getItem("data"));
  let allowenses = userData.allowenses;
  let localAllowenses = {};

  allowenses.forEach((allowense) => {
    localAllowenses[allowense.section] = {};
  });

  if (localAllowenses[section] === undefined) {
    return false;
  }

  allowenses.forEach((allowense) => {
    localAllowenses[allowense.section][allowense.action] = "";
  });

  return localAllowenses[section][action] != undefined;
}

/*
 * If not building with SSR mode, you can
 * directly export the Router instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Router instance.
 */
export default route(function (/* { store, ssrContext } */) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : process.env.VUE_ROUTER_MODE === "history"
    ? createWebHistory
    : createWebHashHistory;

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,

    // Leave this as is and make changes in quasar.conf.js instead!
    // quasar.conf.js -> build -> vueRouterMode
    // quasar.conf.js -> build -> publicPath
    history: createHistory(process.env.VUE_ROUTER_BASE),
  });

  const enableRoleValidation = false;
  Router.beforeEach((to, from, next) => {
    if (
      to.meta.isAuthNeeded === true &&
      sessionStorage.getItem("data") === null
    ) {
      next({ name: "login" });
      return;
    }

    if (sessionStorage.getItem("data") != null && to.name == "login") {
      console.log("to DASHBOARD!");
      next({ name: "dashboard" });
      return;
    }

    //if not allowed to read page, but is authenticated -> redirect to dashboard
    if (
      enableRoleValidation === true &&
      to.meta.isAuthNeeded === true &&
      !isValidFor("read", to.name) &&
      to.name != "dashboard"
    ) {
      console.log("myamma");
      next({ name: "dashboard" });
      return;
    }

    next();
  });

  return Router;
});
