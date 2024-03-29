import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { useAppConfigStore } from "./appConfigStore";
const appConfigStore = useAppConfigStore();

export const useUnitStore = defineStore("unit", {
  state: () => ({
    items: [],
    simpleItems: [],
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
      isItemsLoading: false,
      amountOfItems: 0,
      lastPage: 0,
      updatedItemId: 0,
    },
  }),
  getters: {},
  actions: {
    create(payload) {
      this.dialogs.create.isLoading = true;
      api
        .post("/units", {
          ...payload,
        })
        .then((res) => {
          console.log("units");
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
    update(item) {
      this.dialogs.update.isLoading = true;
      api
        .patch(`/units/${item.id}`, item)
        .then((res) => {
          this.data.updatedItemId = res.data.id;
          let updatedItemIndex;
          this.items.every((item, index) => {
            if (item.id == res.data.id) {
              updatedItemIndex = index;
              return false;
            }
            return true;
          });

          this.items[updatedItemIndex] = res.data;
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.dialogs.update.isLoading = false;
          this.dialogs.update.isShown = false;
        });
    },
    delete(id) {
      this.dialogs.delete.isLoading = true;
      api
        .delete(`/units/${id}`)
        .then(() => {
          let perPage = appConfigStore.amountOfItemsPerPages.sizes;
          let currentPage = appConfigStore.currentPages.sizes;

          if (
            this.data.amountOfItems != 1 &&
            this.data.lastPage == currentPage &&
            perPage * currentPage - this.data.amountOfItems == 1
          ) {
            appConfigStore.currentPages.sizes -= 1;
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
      appConfigStore.updateLocalStorageConfig();
      this.items = [];
      this.data.isItemsLoading = true;
      api
        .get("/units", {
          params: {
            itemsPerPage: appConfigStore.amountOfItemsPerPages.units,
            page: appConfigStore.currentPages.units,
            nameFilterValue:
              appConfigStore.filters.data.units.selectedParams.name.value,
            nameFilterMode:
              appConfigStore.filters.data.units.selectedParams.name.filterMode
                .value,
            descriptionFilterValue:
              appConfigStore.filters.data.units.selectedParams.description
                .value,
            descriptionFilterMode:
              appConfigStore.filters.data.units.selectedParams.description
                .filterMode.value,
            orderField:
              appConfigStore.filters.data.units.selectedParams.order.field,
            orderValue:
              appConfigStore.filters.data.units.selectedParams.order.value,
          },
        })
        .then((res) => {
          console.log("units");
          console.log(res);
          this.items = res.data.data;
          this.data.amountOfItems = res.data.total;
          this.data.lastPage = res.data.last_page;
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.data.isItemsLoading = false;
        });
    },
    simpleReceive(nameFilterValue) {
      api
        .get(`/units/simple`, {
          params: {
            nameFilterValue: nameFilterValue,
          },
        })
        .then((res) => {
          this.simpleItems = res.data;
        })
        .catch()
        .finally(() => {
          this.data.isItemsLoading = false;
        });
    },
  },
});
