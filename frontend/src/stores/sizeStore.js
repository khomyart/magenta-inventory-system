import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { useAppConfigStore } from "./appConfigStore";
const appConfigStore = useAppConfigStore();

export const useSizeStore = defineStore("size", {
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
      firstItemNumberInRow: 0,
      lastItemNumberInRow: 0,
    },
  }),
  getters: {},
  actions: {
    create(payload) {
      this.dialogs.create.isLoading = true;
      api
        .post("/sizes", {
          ...payload,
        })
        .then((res) => {
          console.log("sizes");
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
        .patch(`/sizes/${item.id}`, item)
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
        .delete(`/sizes/${id}`)
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
        .get("/sizes", {
          params: {
            itemsPerPage: appConfigStore.amountOfItemsPerPages.sizes,
            page: appConfigStore.currentPages.sizes,
            valueFilterValue:
              appConfigStore.filters.data.sizes.selectedParams.value.value,
            valueFilterMode:
              appConfigStore.filters.data.sizes.selectedParams.value.filterMode
                .value,
            descriptionFilterValue:
              appConfigStore.filters.data.sizes.selectedParams.description
                .value,
            descriptionFilterMode:
              appConfigStore.filters.data.sizes.selectedParams.description
                .filterMode.value,
            orderField:
              appConfigStore.filters.data.sizes.selectedParams.order.field,
            orderValue:
              appConfigStore.filters.data.sizes.selectedParams.order.value,
          },
        })
        .then((res) => {
          console.log("sizes");
          console.log(res);
          this.data.firstItemNumberInRow = res.data.first_item_number_in_row;
          this.data.lastItemNumberInRow = res.data.last_item_number_in_row;

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
        .get(`/sizes/simple`, {
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
    move(id, direction) {
      this.data.isItemsLoading = true;
      api
        .patch(`/sizes/move/${id}`, {
          direction: direction,
        })
        .then((res) => {
          this.receive();
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {});
    },
  },
});
