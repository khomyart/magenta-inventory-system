import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { useAppConfigStore } from "./appConfigStore";
import { getServerTime } from "app/helpers/GeneralPurposeFunctions";

const appConfigStore = useAppConfigStore();
const sectionName = "orders";

export const useOrderStore = defineStore("order", {
  state: () => ({
    items: [],
    dialogs: {
      create: { isShown: false, isLoading: false },
      update: { isShown: false, isLoading: false },
      delete: { isShown: false, isLoading: false },
      cancel: { isShown: false, isLoading: false },
      confirm: { isShown: false, isLoading: false },
      startWork: { isShown: false, isLoading: false },
      complete: { isShown: false, isLoading: false },
      payment: { isShown: false, isLoading: false },
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
          this.data.updatedItemId = res.data.order.id;
          const updatedItemIndex = this.items.findIndex(i => i.id === res.data.order.id);
          if (updatedItemIndex !== -1) {
            this.items[updatedItemIndex] = res.data.order;
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

    cancel(id, returnItems) {
      this.dialogs.cancel.isLoading = true;
      api.post(`/${sectionName}/${id}/cancel`, { return_items: returnItems })
        .then((res) => {
          this.dialogs.cancel.isShown = false;
          this.data.updatedItemId = res.data.order.id;
          const updatedItemIndex = this.items.findIndex(i => i.id === res.data.order.id);
          if (updatedItemIndex !== -1) {
            this.items[updatedItemIndex] = res.data.order;
          }
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.dialogs.cancel.isLoading = false;
        });
    },

    confirm(id, advancePayments) {
      this.dialogs.confirm.isLoading = true;
      const payload = advancePayments || {};
      api.post(`/${sectionName}/${id}/confirm`, payload)
        .then((res) => {
          this.dialogs.confirm.isShown = false;
          this.data.updatedItemId = res.data.order.id;
          const updatedItemIndex = this.items.findIndex(i => i.id === res.data.order.id);
          if (updatedItemIndex !== -1) {
            this.items[updatedItemIndex] = res.data.order;
          }
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.dialogs.confirm.isLoading = false;
        });
    },

    startWork(id) {
      this.dialogs.startWork.isLoading = true;
      api.post(`/${sectionName}/${id}/start-work`)
        .then((res) => {
          this.dialogs.startWork.isShown = false;
          this.data.updatedItemId = res.data.order.id;
          const updatedItemIndex = this.items.findIndex(i => i.id === res.data.order.id);
          if (updatedItemIndex !== -1) {
            this.items[updatedItemIndex] = res.data.order;
          }
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.dialogs.startWork.isLoading = false;
        });
    },

    complete(id, involvementData = {}) {
      this.dialogs.complete.isLoading = true;
      api.post(`/${sectionName}/${id}/complete`, involvementData)
        .then((res) => {
          this.dialogs.complete.isShown = false;
          this.data.updatedItemId = res.data.order.id;
          const updatedItemIndex = this.items.findIndex(i => i.id === res.data.order.id);
          if (updatedItemIndex !== -1) {
            this.items[updatedItemIndex] = res.data.order;
          }
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.dialogs.complete.isLoading = false;
        });
    },

    payment(id, payments) {
      this.dialogs.payment.isLoading = true;
      api.post(`/${sectionName}/${id}/payment`, payments)
        .then((res) => {
          this.dialogs.payment.isShown = false;
          this.data.updatedItemId = res.data.order.id;
          const updatedItemIndex = this.items.findIndex(i => i.id === res.data.order.id);
          if (updatedItemIndex !== -1) {
            this.items[updatedItemIndex] = res.data.order;
          }
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.dialogs.payment.isLoading = false;
        });
    },

    receive() {
      appConfigStore.updateLocalStorageConfig();
      this.data.isItemsLoading = true;

      // Helper function to convert client date to server time
      const convertDateToServerTime = (dateValue) => {
        if (!dateValue || dateValue === "") {
          return dateValue;
        }
        // Convert to server time format (YYYY-MM-DD HH:mm)
        return getServerTime(dateValue, "ua", true, false);
      };

      api.get(`/${sectionName}`, {
        params: {
          itemsPerPage: appConfigStore.amountOfItemsPerPages[sectionName],
          page: appConfigStore.currentPages[sectionName],
          // Filters
          id_filter_value: appConfigStore.filters.data[sectionName].selectedParams.id?.value,
          id_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.id?.filterMode.value,
          status_filter_value: appConfigStore.filters.data[sectionName].selectedParams.status?.value,
          status_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.status?.filterMode.value,
          total_price_filter_value: appConfigStore.filters.data[sectionName].selectedParams.total_price?.value,
          total_price_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.total_price?.filterMode.value,
          remaining_to_pay_filter_value: appConfigStore.filters.data[sectionName].selectedParams.remaining_to_pay?.value,
          remaining_to_pay_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.remaining_to_pay?.filterMode.value,
          contact_filter_value: appConfigStore.filters.data[sectionName].selectedParams.contact?.value,
          contact_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.contact?.filterMode.value,
          completion_deadline_from_filter_value: convertDateToServerTime(appConfigStore.filters.data[sectionName].selectedParams.completion_deadline_from?.value),
          completion_deadline_from_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.completion_deadline_from?.filterMode.value,
          completion_deadline_to_filter_value: convertDateToServerTime(appConfigStore.filters.data[sectionName].selectedParams.completion_deadline_to?.value),
          completion_deadline_to_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.completion_deadline_to?.filterMode.value,
          completion_deadline_is_null_filter_value: appConfigStore.filters.data[sectionName].selectedParams.completion_deadline_is_null?.value === true ? true : undefined,
          created_at_from_filter_value: convertDateToServerTime(appConfigStore.filters.data[sectionName].selectedParams.created_at_from?.value),
          created_at_from_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.created_at_from?.filterMode.value,
          created_at_to_filter_value: convertDateToServerTime(appConfigStore.filters.data[sectionName].selectedParams.created_at_to?.value),
          created_at_to_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.created_at_to?.filterMode.value,
          created_at_is_null_filter_value: appConfigStore.filters.data[sectionName].selectedParams.created_at_is_null?.value === true ? true : undefined,
          completed_at_from_filter_value: convertDateToServerTime(appConfigStore.filters.data[sectionName].selectedParams.completed_at_from?.value),
          completed_at_from_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.completed_at_from?.filterMode.value,
          completed_at_to_filter_value: convertDateToServerTime(appConfigStore.filters.data[sectionName].selectedParams.completed_at_to?.value),
          completed_at_to_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.completed_at_to?.filterMode.value,
          completed_at_is_null_filter_value: appConfigStore.filters.data[sectionName].selectedParams.completed_at_is_null?.value === true ? true : undefined,
          fully_payed_at_from_filter_value: convertDateToServerTime(appConfigStore.filters.data[sectionName].selectedParams.fully_payed_at_from?.value),
          fully_payed_at_from_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.fully_payed_at_from?.filterMode.value,
          fully_payed_at_to_filter_value: convertDateToServerTime(appConfigStore.filters.data[sectionName].selectedParams.fully_payed_at_to?.value),
          fully_payed_at_to_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.fully_payed_at_to?.filterMode.value,
          fully_payed_at_is_null_filter_value: appConfigStore.filters.data[sectionName].selectedParams.fully_payed_at_is_null?.value === true ? true : undefined,
          notes_filter_value: appConfigStore.filters.data[sectionName].selectedParams.notes?.value,
          notes_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.notes?.filterMode.value,
          involved_users_filter_value: appConfigStore.filters.data[sectionName].selectedParams.involved_users?.value,
          involved_users_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.involved_users?.filterMode.value,
          advance_payment_filter_value: appConfigStore.filters.data[sectionName].selectedParams.advance_payment?.value,
          advance_payment_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.advance_payment?.filterMode.value,
          final_payment_filter_value: appConfigStore.filters.data[sectionName].selectedParams.final_payment?.value,
          final_payment_filter_mode: appConfigStore.filters.data[sectionName].selectedParams.final_payment?.filterMode.value,
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
