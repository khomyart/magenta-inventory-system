import {defineStore} from "pinia";
import {api} from "src/boot/axios";
import {useAppConfigStore} from "./appConfigStore";
import {getServerTime} from "app/helpers/GeneralPurposeFunctions";

const appConfigStore = useAppConfigStore();

export const useSpendStore = defineStore("spend", {
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
      let tmpItem = JSON.parse(JSON.stringify(payload));
      tmpItem.happened_at = getServerTime(payload.happened_at, "ua", true, false);

      api
        .post("/spends", tmpItem)
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
      let tmpItem = JSON.parse(JSON.stringify(item));
      tmpItem.happened_at = getServerTime(item.happened_at, "ua", true, false);

      api
        .patch(`/spends/${item.id}`, tmpItem)
        .then((res) => {
          this.data.updatedItemId = res.data.spend.id;
          let updatedItemIndex;
          this.items.every((item, index) => {
            if (item.id == this.data.updatedItemId) {
              updatedItemIndex = index;
              return false;
            }
            return true;
          });
          this.items[updatedItemIndex] = res.data.spend;
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
        .delete(`/spends/${id}`)
        .then(() => {
          let perPage = appConfigStore.amountOfItemsPerPages.spends;
          let currentPage = appConfigStore.currentPages.spends;

          if (
            this.data.amountOfItems != 1 &&
            this.data.lastPage == currentPage &&
            perPage * currentPage - this.data.amountOfItems == 1
          ) {
            appConfigStore.currentPages.spends -= 1;
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

      let happenedAtFromFilterValue = appConfigStore.filters.data.spends.selectedParams.happened_at_from
        .value;
      if (!!happenedAtFromFilterValue) {
        happenedAtFromFilterValue = getServerTime(appConfigStore.filters.data.spends.selectedParams.happened_at_from
          .value, "ua", true, true);
      }
      let happenedAtToFilterValue = appConfigStore.filters.data.spends.selectedParams.happened_at_to
        .value;
      if (!!happenedAtToFilterValue) {
        happenedAtToFilterValue = getServerTime(appConfigStore.filters.data.spends.selectedParams.happened_at_to
          .value, "ua", true, true);
      }

      let createdAtFromFilterValue = appConfigStore.filters.data.spends.selectedParams.created_at_from
        .value;
      if (!!createdAtFromFilterValue) {
        createdAtFromFilterValue = getServerTime(appConfigStore.filters.data.spends.selectedParams.created_at_from
          .value, "ua", true, true);
      }
      let createdAtToFilterValue = appConfigStore.filters.data.spends.selectedParams.created_at_to
        .value;
      if (!!createdAtToFilterValue) {
        createdAtToFilterValue = getServerTime(appConfigStore.filters.data.spends.selectedParams.created_at_to
          .value, "ua", true, true);
      }

      api
        .get("/spends", {
          params: {
            itemsPerPage: appConfigStore.amountOfItemsPerPages.spends,
            page: appConfigStore.currentPages.spends,
            title_filter_value:
            appConfigStore.filters.data.spends.selectedParams.title.value,
            title_filter_mode:
            appConfigStore.filters.data.spends.selectedParams.title.filterMode
              .value,
            price_filter_value:
            appConfigStore.filters.data.spends.selectedParams.price
              .value,
            price_filter_mode:
            appConfigStore.filters.data.spends.selectedParams.price
              .filterMode.value,
            currency_filter_value:
            appConfigStore.filters.data.spends.selectedParams.currency
              .value,
            currency_filter_mode:
            appConfigStore.filters.data.spends.selectedParams.currency
              .filterMode.value,
            happened_at_from_filter_value: happenedAtFromFilterValue,
            happened_at_from_filter_mode:
            appConfigStore.filters.data.spends.selectedParams.happened_at_from
              .filterMode.value,
            happened_at_to_filter_value: happenedAtToFilterValue,
            happened_at_to_filter_mode:
            appConfigStore.filters.data.spends.selectedParams.happened_at_to
              .filterMode.value,
            created_at_from_filter_value: createdAtFromFilterValue,
            created_at_from_filter_mode:
            appConfigStore.filters.data.spends.selectedParams.created_at_from
              .filterMode.value,
            created_at_to_filter_value: createdAtToFilterValue,
            created_at_to_filter_mode:
            appConfigStore.filters.data.spends.selectedParams.created_at_to
              .filterMode.value,
            created_by_user_filter_value: appConfigStore.filters.data.spends.selectedParams.created_by_user
              .value,
            created_by_user_filter_mode:
            appConfigStore.filters.data.spends.selectedParams.created_by_user
              .filterMode.value,

            orderField:
            appConfigStore.filters.data.spends.selectedParams.order.field,
            orderValue:
            appConfigStore.filters.data.spends.selectedParams.order.value,
          },
        })
        .then((res) => {
          console.log("spends");
          console.log(res);
          this.data.firstItemNumberInRow = res.data.first_item_number_in_row;
          this.data.lastItemNumberInRow = res.data.last_item_number_in_row;

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
