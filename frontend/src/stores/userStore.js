import { defineStore } from "pinia";
import { api } from "src/boot/axios";

export const useUserStore = defineStore("user", {
  state: () => ({
    data: {
      name: "",
      email: "",
    },
    token: "",
    isLoading: false,
  }),
  getters: {},
  actions: {
    login(userData) {
      this.isLoading = true;
      api
        .post("/login", userData)
        .then((response) => {
          console.log(response);
        })
        .catch(() => {
          $q.notify({
            position: "top",
            color: "negative",
            message: "Невірні данні авторизації",
            group: false,
          });
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
});
