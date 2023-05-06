import { defineStore } from "pinia";
import { api } from "src/boot/axios";

import { useAppConfigStore } from "./appConfigStore";

const appConfigStore = useAppConfigStore();

export const useUserStore = defineStore("user", {
  state: () => ({
    data: {
      name: "",
      email: "",
      token: {
        value: null,
        expiredAt: "",
      },
      isLoading: false,
      isLoginSuccesed: false,
    },
  }),
  getters: {},
  actions: {
    login(userData) {
      api
        .post("/login", userData)
        .then((res) => {
          userData = {
            email: res.data.user.email,
            name: res.data.user.name,
            token: res.data.auth.token,
            expired_at: res.data.auth.expired_at,
            allowenses: res.data.allowenses,
          };
          sessionStorage.setItem("data", JSON.stringify(userData));
          this.data.isLoginSuccesed = true;
        })
        .catch((err) => {
          this.data.isLoading = false;
          appConfigStore.catchRequestError(err);
        });
    },
    logout() {
      return api.post("/logout");
    },
    renewSession(password) {
      let userData = {
        email: this.data.email,
        password: password,
      };

      return api.post("/login", userData);
    },
  },
});
