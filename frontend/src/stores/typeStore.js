import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { useAppConfigStore } from "./appConfigStore";
const appConfigStore = useAppConfigStore();

export const useTypeStore = defineStore("type", {
  state: () => ({
    items: [],
    isTypesLoading: false,
    amountOfItems: 0,
    lastPage: 0,
    dialogs: {
      create: {
        isShown: false,
        isLoading: false,
      },
    },
  }),
  getters: {},
  actions: {
    create(payload) {
      this.dialogs.create.isLoading = true;
      api
        .post("/types", {
          ...payload,
        })
        .then((res) => {
          console.log(res);
          this.dialogs.create.isShown = false;
          this.receive();
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.dialogs.create.isLoading = false;
        });
    },
    edit(id, payload) {},
    remove(id) {},
    receive() {
      this.isTypesLoading = true;
      api
        .get("/types", {
          params: {
            itemsPerPage: appConfigStore.amountOfItemsPerPages.types,
            page: appConfigStore.currentPages.types,
          },
        })
        .then((res) => {
          console.log(res);
          this.items = res.data.data;
          this.amountOfItems = res.data.total;
          this.lastPage = res.data.last_page;
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.isTypesLoading = false;
        });
    },
  },
});
