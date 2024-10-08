import { boot } from "quasar/wrappers";
import axios from "axios";
// Be careful when using SSR for cross-request state pollution
// due to creating a Singleton instance here;
// If any client changes this (global) instance, it might be a
// good idea to move this instance creation inside of the
// "export default () => {}" function below (which runs individually
// for each client)
const api = axios.create({
  baseURL: import.meta.env.VITE_BACKEND_ADDRESS + '/',
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

api.interceptors.request.use((config) => {
  // config.headers.Authorization = `Bearer ${userStore.data.token.value}`;
  // return config;

  let userData = JSON.parse(sessionStorage.getItem("data"));
  let token;

  if (sessionStorage.getItem("data") != undefined) {
    token = userData.token;
  } else {
    token = "";
  }

  config.headers.Authorization = `Bearer ${token}`;
  return config;
});

export default boot(({ app }) => {
  // for use inside Vue files (Options API) through this.$axios and this.$api

  app.config.globalProperties.$axios = axios;
  // ^ ^ ^ this will allow you to use this.$axios (for Vue Options API form)
  //       so you won't necessarily have to import axios in each vue file

  app.config.globalProperties.$api = api;
  // ^ ^ ^ this will allow you to use this.$api (for Vue Options API form)
  //       so you can easily perform requests against your app's API
});

export { api };
