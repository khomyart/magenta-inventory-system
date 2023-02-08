import { defineStore } from "pinia";
import { store } from "quasar/wrappers";
import { api } from "src/boot/axios";

export const useTypeStore = defineStore("type", {
  state: () => ({
    items: [],
    isTypesLoading: false,
    amountOfItems: 0,
    lastPage: 0,
  }),
  getters: {},
  actions: {
    create(payload) {
      api
        .post("/types", {
          ...payload,
        })
        .then((res) => {
          console.log(res);
        })
        .catch((err) => {
          console.log(err);
        });
    },
    edit(id, payload) {},
    remove(id) {},
    receive(amountOfItemsPegPage, currentPage) {
      this.isTypesLoading = true;
      api
        .get(`/types?itemsPerPage=${amountOfItemsPegPage}&page=${currentPage}`)
        .then((res) => {
          console.log(res);
          this.items = res.data.data;
          this.amountOfItems = res.data.total;
          this.lastPage = res.data.last_page;
        })
        .catch((err) => {
          console.log(err);
        })
        .finally(() => {
          this.isTypesLoading = false;
        });
    },
  },
});
