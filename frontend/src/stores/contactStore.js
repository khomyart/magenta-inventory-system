import {defineStore} from "pinia";
import {api} from "src/boot/axios";
import {useAppConfigStore} from "./appConfigStore";

const appConfigStore = useAppConfigStore();

export const useContactStore = defineStore("contact", {
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
        .post("/contacts", payload)
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

      api
        .patch(`/contacts/${item.id}`, item)
        .then((res) => {
          this.data.updatedItemId = res.data.contact.id;
          let updatedItemIndex;
          this.items.every((item, index) => {
            if (item.id == this.data.updatedItemId) {
              updatedItemIndex = index;
              return false;
            }
            return true;
          });
          this.items[updatedItemIndex] = res.data.contact;
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
        .delete(`/contacts/${id}`)
        .then(() => {
          let perPage = appConfigStore.amountOfItemsPerPages.contacts;
          let currentPage = appConfigStore.currentPages.contacts;

          if (
            this.data.amountOfItems != 1 &&
            this.data.lastPage == currentPage &&
            perPage * currentPage - this.data.amountOfItems == 1
          ) {
            appConfigStore.currentPages.contacts -= 1;
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
        .get("/contacts", {
          params: {
            itemsPerPage: appConfigStore.amountOfItemsPerPages.contacts,
            page: appConfigStore.currentPages.contacts,
            name_or_phone_filter_value:
            appConfigStore.filters.data.contacts.selectedParams.name_or_phone.value,
            name_or_phone_filter_mode:
            appConfigStore.filters.data.contacts.selectedParams.name_or_phone.filterMode
              .value,
            name_filter_value:
            appConfigStore.filters.data.contacts.selectedParams.name.value,
            name_filter_mode:
            appConfigStore.filters.data.contacts.selectedParams.name.filterMode
              .value,
            phone_filter_value:
            appConfigStore.filters.data.contacts.selectedParams.phone
              .value,
            phone_filter_mode:
            appConfigStore.filters.data.contacts.selectedParams.phone
              .filterMode.value,
            email_filter_value:
            appConfigStore.filters.data.contacts.selectedParams.email
              .value,
            email_filter_mode:
            appConfigStore.filters.data.contacts.selectedParams.email
              .filterMode.value,
            address_filter_value: appConfigStore.filters.data.contacts.selectedParams.address
              .value,
            address_filter_mode:
            appConfigStore.filters.data.contacts.selectedParams.address
              .filterMode.value,
            preferred_platforms_filter_value: appConfigStore.filters.data.contacts.selectedParams.preferred_platforms
              .value,
            preferred_platforms_filter_mode:
            appConfigStore.filters.data.contacts.selectedParams.preferred_platforms
              .filterMode.value,
            additional_info_filter_value: appConfigStore.filters.data.contacts.selectedParams.additional_info
              .value,
            additional_info_filter_mode:
            appConfigStore.filters.data.contacts.selectedParams.additional_info
              .filterMode.value,

            orderField:
            appConfigStore.filters.data.contacts.selectedParams.order.field,
            orderValue:
            appConfigStore.filters.data.contacts.selectedParams.order.value,
          },
        })
        .then((res) => {
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
