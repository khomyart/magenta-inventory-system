import { defineStore } from "pinia";
import { api } from "src/boot/axios";

export const useUserStore = defineStore("user", {
  state: () => ({
    data: {
      name: "",
      email: "",
      token: {
        value: null,
        expiredAt: "",
      },
    },
  }),
  getters: {},
  actions: {
    login(userData) {
      return api.post("/login", userData);
    },
    logout() {},
  },
});
