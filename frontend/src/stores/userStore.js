import { defineStore } from "pinia";
import { api } from "src/boot/axios";

export const useUserStore = defineStore("user", {
  state: () => ({
    data: {
      id: "",
      name: "",
      email: "",
    },
    token: null,
    // token: null,
  }),
  getters: {},
  actions: {
    login(userData) {
      return api.post("/login", userData);
    },
    logout() {},
  },
});
