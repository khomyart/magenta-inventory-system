import { defineStore } from "pinia";

export const useAppConfigStore = defineStore("appConfig", {
  state: () => ({
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
  actions: {},
});
