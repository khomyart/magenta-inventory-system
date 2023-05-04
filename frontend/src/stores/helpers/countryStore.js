import { defineStore } from "pinia";
import { api } from "src/boot/axios";

export const useCountryStore = defineStore("country", {
  state: () => ({
    items: [],
    data: {
      isItemsLoading: false,
      amountOfItems: 0,
      lastPage: 0,
      updatedItemId: 0,
    },
  }),
  getters: {},
  actions: {
    receive(nameFilterValue, loadingStates = null) {
      api
        .get("/countries", {
          params: {
            nameFilterValue: nameFilterValue,
          },
        })
        .then((res) => {
          this.items = res.data;
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          if (loadingStates != null) {
            loadingStates.country = false;
          }
          this.data.isItemsLoading = false;
        });
    },
  },
});
