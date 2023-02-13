import { defineStore } from "pinia";

export const useAppConfigStore = defineStore("appConfig", {
  state: () => ({
    dialogs: {
      reauth: {
        isShown: false,
        isLoading: false,
      },
    },
    allowenses: {},
    filtersWidths: {
      //px
      types: {},
    },
    amountOfItemsPerPages: {
      items: 20,
      types: 4,
    },
    currentPages: { items: 1, types: 0 },
    availableAmaountOfItemsPerPage: [4, 20, 50],
  }),
  getters: {},
  actions: {
    catchRequestError(err) {
      if (err.response.data === "tokenexpired" && err.response.status === 422) {
        this.dialogs.reauth.isShown = true;
      }
    },
  },
});
