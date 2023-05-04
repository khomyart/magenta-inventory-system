import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { useAppConfigStore } from "./appConfigStore";
const appConfigStore = useAppConfigStore();

const sectionName = "items";

export const useItemStore = defineStore("item", {
  state: () => ({
    newItem: {
      article: "",
      title: "",
      price: "",
      lack: 0,
      currency: "UAH",
      type: null,
      gender: null,
      size: null,
      color: null,
      unit: null,
      availableIn: [],
      images: [],
    },
    selectedItemForUpdating: {},
    items: [],
    dialogs: {
      create: {
        isShown: false,
        isLoading: false,
      },
      createFew: {
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
      warehouse: {
        isShown: false,
      },
    },
    data: {
      isItemsLoading: false,
      amountOfItems: 0,
      lastPage: 0,
      updatedItemId: 0,
      isItemDataLoading: false,
      updatedItemIndexInArray: -1,
    },
  }),
  getters: {},
  actions: {
    create() {
      console.log(this.newItem);

      let preparedItem = {
        article: this.newItem.article,
        title: this.newItem.title,
        price: this.newItem.price,
        currency: this.newItem.currency,
        lack: this.newItem.lack,
        type_id: this.newItem.type.id,
        unit_id: this.newItem.unit.id,
      };

      if (this.newItem.gender != null)
        preparedItem.gender_id = this.newItem.gender.id;
      if (this.newItem.size != null)
        preparedItem.size_id = this.newItem.size.id;
      if (this.newItem.color != null)
        preparedItem.color_id = this.newItem.color.id;

      //warehouses preparation
      this.newItem.availableIn.forEach((availableIn, index) => {
        if (index == 0) preparedItem.warehouses = [];
        preparedItem.warehouses.push({
          id: availableIn.warehouse.id,
          batches: availableIn.batches,
        });
      });

      //images preparation
      this.newItem.images.forEach((image, index) => {
        if (index == 0) preparedItem.images = [];
        preparedItem.images.push(image.file);
      });

      console.log("prepared item:", preparedItem);

      let form = new FormData();
      Object.keys(preparedItem).forEach((key) => {
        switch (key) {
          case "images":
            if (preparedItem[key].length > 0) {
              preparedItem[key].forEach((image, index) => {
                form.append(`images[${index}]`, image, image.name);
              });
            }
            break;

          case "warehouses":
            if (preparedItem[key].length > 0) {
              preparedItem[key].forEach((warehouse, index) => {
                form.append(`warehouses[${index}][id]`, warehouse.id);
                warehouse.batches.forEach((batch, batchIndex) => {
                  form.append(
                    `warehouses[${index}][batches][${batchIndex}][amount]`,
                    batch.amount
                  );
                  form.append(
                    `warehouses[${index}][batches][${batchIndex}][price]`,
                    batch.price
                  );
                  form.append(
                    `warehouses[${index}][batches][${batchIndex}][currency]`,
                    batch.currency
                  );
                });
              });
            }
            break;

          default:
            form.append(key, preparedItem[key]);
            break;
        }
      });

      this.dialogs.create.isLoading = true;
      api
        .post(`/${sectionName}`, form)
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
    update() {
      let preparedItem = {
        article: this.selectedItemForUpdating.article,
        title: this.selectedItemForUpdating.title,
        price: this.selectedItemForUpdating.price,
        currency: this.selectedItemForUpdating.currency,
        lack: this.selectedItemForUpdating.lack,
        type_id: this.selectedItemForUpdating.type.id,
        unit_id: this.selectedItemForUpdating.unit.id,
      };

      if (this.selectedItemForUpdating.gender != null)
        preparedItem.gender_id = this.selectedItemForUpdating.gender.id;
      if (this.selectedItemForUpdating.size != null)
        preparedItem.size_id = this.selectedItemForUpdating.size.id;
      if (this.selectedItemForUpdating.color != null)
        preparedItem.color_id = this.selectedItemForUpdating.color.id;

      //images preparation
      this.selectedItemForUpdating.images.forEach((image, index) => {
        if (index == 0) preparedItem.images = [];
        preparedItem.images.push(image.file);
      });

      let form = new FormData();
      Object.keys(preparedItem).forEach((key) => {
        switch (key) {
          case "images":
            if (preparedItem[key].length > 0) {
              preparedItem[key].forEach((image, index) => {
                form.append(`images[${index}]`, image, image.name);
              });
            }
            break;

          default:
            form.append(key, preparedItem[key]);
            break;
        }
      });

      this.dialogs.update.isLoading = true;

      api
        .post(`/${sectionName}/${this.selectedItemForUpdating.id}`, form)
        .then((res) => {
          this.dialogs.update.isShown = false;

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
      console.log("received items");
      let preparedParams = {
        itemsPerPage: appConfigStore.amountOfItemsPerPages[sectionName],
        page: appConfigStore.currentPages[sectionName],
        //article
        articleFilterValue:
          appConfigStore.filters.data[sectionName].selectedParams.article.value,
        articleFilterMode:
          appConfigStore.filters.data[sectionName].selectedParams.article
            .filterMode.value,
        //title
        titleFilterValue:
          appConfigStore.filters.data[sectionName].selectedParams.title.value,
        titleFilterMode:
          appConfigStore.filters.data[sectionName].selectedParams.title
            .filterMode.value,
        //type
        typeFilterValue:
          appConfigStore.filters.data[sectionName].selectedParams.type.value,
        typeFilterMode:
          appConfigStore.filters.data[sectionName].selectedParams.type
            .filterMode.value,
        //type
        priceFilterValue:
          appConfigStore.filters.data[sectionName].selectedParams.price.value,
        priceFilterMode:
          appConfigStore.filters.data[sectionName].selectedParams.price
            .filterMode.value,
        //gender
        genderFilterValue:
          appConfigStore.filters.data[sectionName].selectedParams.gender.value,
        genderFilterMode:
          appConfigStore.filters.data[sectionName].selectedParams.gender
            .filterMode.value,
        //size
        sizeFilterValue:
          appConfigStore.filters.data[sectionName].selectedParams.size.value,
        sizeFilterMode:
          appConfigStore.filters.data[sectionName].selectedParams.size
            .filterMode.value,
        //color
        colorFilterValue:
          appConfigStore.filters.data[sectionName].selectedParams.color.value,
        colorFilterMode:
          appConfigStore.filters.data[sectionName].selectedParams.color
            .filterMode.value,
        //amount
        amountFilterValue:
          appConfigStore.filters.data[sectionName].selectedParams.amount.value,
        amountFilterMode:
          appConfigStore.filters.data[sectionName].selectedParams.amount
            .filterMode.value,
        //units
        unitsFilterValue:
          appConfigStore.filters.data[sectionName].selectedParams.units.value,
        unitsFilterMode:
          appConfigStore.filters.data[sectionName].selectedParams.units
            .filterMode.value,
        //order field info
        orderField:
          appConfigStore.filters.data[sectionName].selectedParams.order.field,
        orderValue:
          appConfigStore.filters.data[sectionName].selectedParams.order.value,
      };

      if (
        appConfigStore.filters.data[sectionName].selectedParams.warehouse !=
        null
      ) {
        preparedParams.warehouseId =
          appConfigStore.filters.data[sectionName].selectedParams.warehouse.id;
      }

      api
        .get(`/${sectionName}`, {
          params: { ...preparedParams },
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
    receiveItem(id, arrayIndex) {
      this.data.isItemDataLoading = true;
      this.data.updatedItemIndexInArray = arrayIndex;
      api
        .get(`/${sectionName}/${id}`)
        .then((res) => {
          console.log(res);
          this.selectedItemForUpdating = { ...res.data };
          this.selectedItemForUpdating.images = [];

          res.data.images.forEach((image) => {
            this.selectedItemForUpdating.images.push({
              url: image.base64,
              mimeType: image.mimeType,
              name: image.src,
            });
          });

          this.dialogs.update.isShown = true;
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.data.isItemDataLoading = false;
          this.data.updatedItemIndexInArray = -1;
        });
    },
  },
});
