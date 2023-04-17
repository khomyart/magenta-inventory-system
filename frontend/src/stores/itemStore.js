import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { useAppConfigStore } from "./appConfigStore";
const appConfigStore = useAppConfigStore();

const sectionName = "items";

export const useItemStore = defineStore("item", {
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
      // this.dialogs.create.isLoading = true;
      // api
      //   .post(`/${sectionName}`, {
      //     country_id: payload.country.id,
      //     city_id: payload.city.id,
      //     address: payload.address,
      //     name: payload.name,
      //     description: payload.description,
      //   })
      //   .then((res) => {
      //     console.log(sectionName);
      //     console.log(res);
      //     this.dialogs.create.isShown = false;
      //     this.receive();
      //   })
      //   .catch((err) => {
      //     appConfigStore.catchRequestError(err);
      //   })
      //   .finally(() => {
      //     this.dialogs.create.isLoading = false;
      //   });
    },
    update(item) {
      // let itemEditedCopy = { ...item };
      // itemEditedCopy.country_id = item.country.id;
      // itemEditedCopy.city_id = item.city.id;
      // delete itemEditedCopy.country;
      // delete itemEditedCopy.city;
      // item = itemEditedCopy;
      // this.dialogs.update.isLoading = true;
      // api
      //   .patch(`/${sectionName}/${item.id}`, item)
      //   .then((res) => {
      //     this.data.updatedItemId = res.data.id;
      //     let updatedItemIndex;
      //     this.items.every((item, index) => {
      //       if (item.id == res.data.id) {
      //         updatedItemIndex = index;
      //         return false;
      //       }
      //       return true;
      //     });
      //     this.items[updatedItemIndex] = res.data;
      //     this.items[updatedItemIndex].country_name = res.data.country.name;
      //     this.items[updatedItemIndex].city_name = res.data.city.name;
      //   })
      //   .catch((err) => {
      //     appConfigStore.catchRequestError(err);
      //   })
      //   .finally(() => {
      //     this.dialogs.update.isLoading = false;
      //     this.dialogs.update.isShown = false;
      //   });
    },
    delete(id) {
      // this.dialogs.delete.isLoading = true;
      // api
      //   .delete(`/${sectionName}/${id}`)
      //   .then(() => {
      //     let perPage = appConfigStore.amountOfItemsPerPages[sectionName];
      //     let currentPage = appConfigStore.currentPages[sectionName];
      //     if (
      //       this.data.amountOfItems != 1 &&
      //       this.data.lastPage == currentPage &&
      //       perPage * currentPage - this.data.amountOfItems == 1
      //     ) {
      //       appConfigStore.currentPages[sectionName] -= 1;
      //     } else {
      //       this.receive();
      //     }
      //   })
      //   .catch((err) => {
      //     appConfigStore.catchRequestError(err);
      //   })
      //   .finally(() => {
      //     this.dialogs.delete.isLoading = false;
      //     this.dialogs.delete.isShown = false;
      //   });
    },
    receive() {
      appConfigStore.updateLocalStorageConfig();
      this.items = [];
      this.data.isItemsLoading = true;
      console.log("received items");
      api
        .get(`/${sectionName}`, {
          params: {
            itemsPerPage: appConfigStore.amountOfItemsPerPages[sectionName],
            // page: 1,
            page: appConfigStore.currentPages[sectionName],
            //article
            articleFilterValue:
              appConfigStore.filters.data[sectionName].selectedParams.article
                .value,
            articleFilterMode:
              appConfigStore.filters.data[sectionName].selectedParams.article
                .filterMode.value,
            //title
            titleFilterValue:
              appConfigStore.filters.data[sectionName].selectedParams.title
                .value,
            titleFilterMode:
              appConfigStore.filters.data[sectionName].selectedParams.title
                .filterMode.value,
            //type
            typeFilterValue:
              appConfigStore.filters.data[sectionName].selectedParams.type
                .value,
            typeFilterMode:
              appConfigStore.filters.data[sectionName].selectedParams.type
                .filterMode.value,
            //type
            priceFilterValue:
              appConfigStore.filters.data[sectionName].selectedParams.price
                .value,
            priceFilterMode:
              appConfigStore.filters.data[sectionName].selectedParams.price
                .filterMode.value,
            //gender
            genderFilterValue:
              appConfigStore.filters.data[sectionName].selectedParams.gender
                .value,
            genderFilterMode:
              appConfigStore.filters.data[sectionName].selectedParams.gender
                .filterMode.value,
            //size
            sizeFilterValue:
              appConfigStore.filters.data[sectionName].selectedParams.size
                .value,
            sizeFilterMode:
              appConfigStore.filters.data[sectionName].selectedParams.size
                .filterMode.value,
            //color
            colorFilterValue:
              appConfigStore.filters.data[sectionName].selectedParams.color
                .value,
            colorFilterMode:
              appConfigStore.filters.data[sectionName].selectedParams.color
                .filterMode.value,
            //amount
            amountFilterValue:
              appConfigStore.filters.data[sectionName].selectedParams.amount
                .value,
            amountFilterMode:
              appConfigStore.filters.data[sectionName].selectedParams.amount
                .filterMode.value,
            //units
            unitsFilterValue:
              appConfigStore.filters.data[sectionName].selectedParams.units
                .value,
            unitsFilterMode:
              appConfigStore.filters.data[sectionName].selectedParams.units
                .filterMode.value,
            //order field info
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
