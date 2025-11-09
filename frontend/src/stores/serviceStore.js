import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { useAppConfigStore } from "./appConfigStore";

const appConfigStore = useAppConfigStore();
const sectionName = "services";

export const useServiceStore = defineStore("service", {
  state: () => ({
    items: [],
    dialogs: {
      create: { isShown: false, isLoading: false },
      update: { isShown: false, isLoading: false },
      delete: { isShown: false, isLoading: false },
    },
    data: {
      isItemsLoading: false,
      amountOfItems: 0,
      lastPage: 0,
      updatedItemId: 0,
    },
  }),
  actions: {
    create(payload) {
      this.dialogs.create.isLoading = true;
      api.post(`/${sectionName}`, payload)
        .then((res) => {
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
      api.patch(`/${sectionName}/${item.id}`, item)
        .then((res) => {
          this.data.updatedItemId = res.data.service.id;
          const updatedItemIndex = this.items.findIndex(i => i.id === res.data.service.id);
          if (updatedItemIndex !== -1) {
            this.items[updatedItemIndex] = res.data.service;
          }
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
      api.delete(`/${sectionName}/${id}`)
        .then(() => {
          const perPage = appConfigStore.amountOfItemsPerPages[sectionName];
          const currentPage = appConfigStore.currentPages[sectionName];
          if (this.items.length === 1 && currentPage > 1) {
            appConfigStore.currentPages[sectionName]--;
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
      this.data.isItemsLoading = true;
      api.get(`/${sectionName}`, {
        params: {
          itemsPerPage: appConfigStore.amountOfItemsPerPages[sectionName],
          page: appConfigStore.currentPages[sectionName],
          // Filters
          title_filter_value: appConfigStore.filters.data[sectionName].selectedParams.title.value,
          title_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.title.filterMode.value,
          price_filter_value: appConfigStore.filters.data[sectionName].selectedParams.price.value,
          price_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.price.filterMode.value,
          // Order
          orderField: appConfigStore.filters.data[sectionName].selectedParams.order.field,
          orderValue: appConfigStore.filters.data[sectionName].selectedParams.order.value,
        },
      })
        .then((res) => {
          this.items = res.data.data;
          this.data.amountOfItems = res.data.meta.total;
          this.data.lastPage = res.data.meta.last_page;
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.data.isItemsLoading = false;
        });
    },
  },
});
