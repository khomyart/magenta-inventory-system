import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { useAppConfigStore } from "./appConfigStore";
import { getServerTime } from "app/helpers/GeneralPurposeFunctions";

const appConfigStore = useAppConfigStore();
const sectionName = "business_account_transactions";

export const useBusinessAccountTransactionStore = defineStore("businessAccountTransaction", {
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
      let tmpItem = JSON.parse(JSON.stringify(payload));
      tmpItem.happened_at = getServerTime(payload.happened_at, "ua", true, false);

      api.post(`/api/${sectionName}/create`, tmpItem)
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

      api.post(`/api/${sectionName}/update/${item.id}`, tmpItem)
        .then((res) => {
          this.data.updatedItemId = res.data.transaction.id;
          this.receive();
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
      api.post(`/api/${sectionName}/delete/${id}`)
        .then(() => {
          this.receive();
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

      const filters = appConfigStore.filters.data[sectionName]?.selectedParams;
      if (!filters) {
        this.data.isItemsLoading = false;
        return;
      }

      let happenedAtFrom = filters.happened_at_from?.value;
      if (happenedAtFrom) {
        happenedAtFrom = getServerTime(happenedAtFrom, "ua", true, true);
      }

      let happenedAtTo = filters.happened_at_to?.value;
      if (happenedAtTo) {
        happenedAtTo = getServerTime(happenedAtTo, "ua", true, true);
      }

      let createdAtFrom = filters.created_at_from?.value;
      if (createdAtFrom) {
        createdAtFrom = getServerTime(createdAtFrom, "ua", true, true);
      }

      let createdAtTo = filters.created_at_to?.value;
      if (createdAtTo) {
        createdAtTo = getServerTime(createdAtTo, "ua", true, true);
      }

      api.post(`/api/${sectionName}/read`, {
        itemsPerPage: appConfigStore.amountOfItemsPerPages[sectionName],
        page: appConfigStore.currentPages[sectionName],
        title_filter_value: filters.title?.value,
        title_filter_mode: filters.title?.filterMode?.value,
        type_filter_value: filters.type?.value,
        type_filter_mode: filters.type?.filterMode?.value,
        total_price_filter_value: filters.total_price?.value,
        total_price_filter_mode: filters.total_price?.filterMode?.value,
        happened_at_from_filter_value: happenedAtFrom,
        happened_at_from_filter_mode: filters.happened_at_from?.filterMode?.value,
        happened_at_to_filter_value: happenedAtTo,
        happened_at_to_filter_mode: filters.happened_at_to?.filterMode?.value,
        created_at_from_filter_value: createdAtFrom,
        created_at_from_filter_mode: filters.created_at_from?.filterMode?.value,
        created_at_to_filter_value: createdAtTo,
        created_at_to_filter_mode: filters.created_at_to?.filterMode?.value,
        created_by_user_filter_value: filters.created_by_user?.value,
        created_by_user_filter_mode: filters.created_by_user?.filterMode?.value,
        orderField: filters.order?.field,
        orderValue: filters.order?.value,
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
