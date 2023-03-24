import { defineStore } from "pinia";
import { api } from "src/boot/axios";

export const useCityStore = defineStore("city", {
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
    receive(countryId, nameFilterValue) {
      api
        .get(`/countries/${countryId}/cities`, {
          params: {
            nameFilterValue: nameFilterValue,
          },
        })
        .then((res) => {
          this.items = res.data;
        })
        .catch()
        .finally(() => {
          this.data.isItemsLoading = false;
        });
    },
  },
});
