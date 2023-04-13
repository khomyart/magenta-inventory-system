import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { useAppConfigStore } from "./appConfigStore";
const appConfigStore = useAppConfigStore();

const sectionName = "colors";

export const useColorStore = defineStore("color", {
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
        .post(`/${sectionName}`, {
          ...payload,
        })
        .then((res) => {
          console.log(sectionName);
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
        .patch(`/${sectionName}/${item.id}`, item)
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
        .delete(`/${sectionName}/${id}`)
        .then(() => {
          let perPage = appConfigStore.amountOfItemsPerPages[sectionName];
          let currentPage = appConfigStore.currentPages[sectionName];

          if (
            this.data.amountOfItems != 1 &&
            this.data.lastPage == currentPage &&
            perPage * currentPage - this.data.amountOfItems == 1
          ) {
            appConfigStore.currentPages[sectionName] -= 1;
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
        .get(`/${sectionName}`, {
          params: {
            itemsPerPage: appConfigStore.amountOfItemsPerPages[sectionName],
            page: appConfigStore.currentPages[sectionName],
            //value
            valueFilterValue:
              appConfigStore.filters.data[sectionName].selectedParams.value
                .value,
            valueFilterMode:
              appConfigStore.filters.data[sectionName].selectedParams.value
                .filterMode.value,
            //article
            articleFilterValue:
              appConfigStore.filters.data[sectionName].selectedParams.article
                .value,
            articleFilterMode:
              appConfigStore.filters.data[sectionName].selectedParams.article
                .filterMode.value,
            //description
            descriptionFilterValue:
              appConfigStore.filters.data[sectionName].selectedParams
                .description.value,
            descriptionFilterMode:
              appConfigStore.filters.data[sectionName].selectedParams
                .description.filterMode.value,
            //text_color_value
            text_color_valueFilterValue:
              appConfigStore.filters.data[sectionName].selectedParams
                .text_color_value.value,
            text_color_valueFilterMode:
              appConfigStore.filters.data[sectionName].selectedParams
                .text_color_value.filterMode.value,
            //orders
            orderField:
              appConfigStore.filters.data[sectionName].selectedParams.order
                .field,
            orderValue:
              appConfigStore.filters.data[sectionName].selectedParams.order
                .value,
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
          this.data.isItemsLoading = false;
        });
    },
  },
});
