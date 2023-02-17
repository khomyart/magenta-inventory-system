import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { useAppConfigStore } from "./appConfigStore";
const appConfigStore = useAppConfigStore();

export const useTypeStore = defineStore("type", {
  state: () => ({
    items: [],
    dialogs: {
      create: {
        isShown: false,
        isLoading: false,
      },
      update: {
        isShown: false,
        isLoading: false,
      },
      delete: {
        isShown: false,
        isLoading: false,
      },
    },
    data: {
      isTypesLoading: false,
      amountOfItems: 0,
      lastPage: 0,
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
    delete(id) {
      this.dialogs.delete.isLoading = true;
      api
        .delete(`/types/${id}`)
        .then(() => {
          let perPage = appConfigStore.amountOfItemsPerPages.types;
          let currentPage = appConfigStore.currentPages.types;

          if (
            this.data.amountOfItems != 1 &&
            this.data.lastPage == currentPage &&
            perPage * currentPage - this.data.amountOfItems == 1
          ) {
            appConfigStore.currentPages.types -= 1;
          } else {
            this.receive();
          }
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.dialogs.delete.isLoading = false;
          this.dialogs.delete.isShown = false;
        });
    },
    receive() {
      this.data.isTypesLoading = true;
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
          this.data.amountOfItems = res.data.total;
          this.data.lastPage = res.data.last_page;
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.data.isTypesLoading = false;
        });
    },
  },
});
