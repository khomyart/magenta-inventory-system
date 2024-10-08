import { defineStore } from "pinia";
import { api } from "src/boot/axios";
import { useAppConfigStore } from "./appConfigStore";
const appConfigStore = useAppConfigStore();

const sectionName = "items";

export const useItemStore = defineStore("item", {
  state: () => ({
    newItem: {
      group_id: "",
      article: "",
      title: "",
      description: "",
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
    newMultipleItems: {
      main: {
        detail: {
          lack: 0,
        },
      },
    },
    income: [],
    outcome: {},
    itemMove: {},
    selectedItemForUpdating: {},
    bufferedItems: [],
    //includes items themself, amount of found items, show limitation info
    itemsFoundByArticle: {
      data: [],
    },
    items: [],
    dialogs: {
      create: {
        isShown: false,
        isLoading: false,
      },
      createMultiple: {
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
      warehouseDetail: {
        isShown: false,
      },
      replaceDataInCreateItemWindow: {
        isShown: false,
      },
      incomeCreator: {
        isShown: false,
        isLoading: false,
      },
      outcomeCreator: {
        isShown: false,
        isLoading: false,
      },
      itemMove: {
        isShown: false,
        isLoading: false,
      },
      amountsWarehouses: {
        isShown: false,
        content: [],
        itemTitle: "",
      },
      itemDescription: {
        isShown: false,
        content: "",
        title: "",
      },
    },
    data: {
      isItemsLoading: false,
      amountOfItems: 0,
      lastPage: 0,
      updatedItemId: 0,
      isItemDataLoading: false,
      updatingItemIndexInArray: -1,
      creatingItemIndexInArray: -1,
    },
  }),
  getters: {},
  actions: {
    create() {
      let preparedItem = {
        article: this.newItem.article,
        group_id: this.newItem.group_id,
        title: this.newItem.title,
        description: this.newItem.description,
        price: this.newItem.price,
        currency: this.newItem.currency,
        lack: this.newItem.lack,
        type_id: this.newItem.type.id,
        unit_id: this.newItem.unit.id,

        // gender_id: null,
        // size_id: null,
        // color_id: null,
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
    createMultiple(preparedItems) {
      let form = new FormData();
      for (let index = 0; index < preparedItems.length; index++) {
        let preparedItem = preparedItems[index];

        Object.keys(preparedItem).forEach((key) => {
          switch (key) {
            case "images":
              if (preparedItem[key].length > 0) {
                preparedItem[key].forEach((image, imageIndex) => {
                  form.append(
                    `items[${index}][images][${imageIndex}]`,
                    image,
                    image.name
                  );
                });
              }
              break;
            case "warehouses":
              if (preparedItem[key].length > 0) {
                preparedItem[key].forEach((warehouse, warehouseIndex) => {
                  form.append(
                    `items[${index}][warehouses][${warehouseIndex}][id]`,
                    warehouse.id
                  );
                  warehouse.batches.forEach((batch, batchIndex) => {
                    form.append(
                      `items[${index}][warehouses][${warehouseIndex}][batches][${batchIndex}][amount]`,
                      batch.amount
                    );
                    form.append(
                      `items[${index}][warehouses][${warehouseIndex}][batches][${batchIndex}][price]`,
                      batch.price
                    );
                    form.append(
                      `items[${index}][warehouses][${warehouseIndex}][batches][${batchIndex}][currency]`,
                      batch.currency
                    );
                  });
                });
              }
              break;
            default:
              form.append(`items[${index}][${key}]`, preparedItem[key]);
              break;
          }
        });
      }

      this.dialogs.createMultiple.isLoading = true;

      api
        .post(`/${sectionName}/createMultiple`, form)
        .then((res) => {
          this.dialogs.createMultiple.isShown = false;
          this.receive();
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.dialogs.createMultiple.isLoading = false;
        });
    },
    update() {
      let preparedItem = {
        article: this.selectedItemForUpdating.article,
        title: this.selectedItemForUpdating.title,
        description: this.selectedItemForUpdating.description,
        price: this.selectedItemForUpdating.price,
        currency: this.selectedItemForUpdating.currency,
        lack: this.selectedItemForUpdating.lack,

        // gender_id: null,
        // size_id: null,
        // color_id: null,
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
      this.data.isReceiveSended = true;
      appConfigStore.updateLocalStorageConfig();
      this.items = [];
      this.data.isItemsLoading = true;
      console.log("received items");
      let preparedParams = {
        extendedArticle: appConfigStore.filters.data[sectionName].selectedParams.extended_article
          .value,
        itemsPerPage: appConfigStore.amountOfItemsPerPages[sectionName],
        page: appConfigStore.currentPages[sectionName],
        //group_id
        group_idFilterValue:
          appConfigStore.filters.data[sectionName].selectedParams.group_id
            .value,
        group_idFilterMode:
          appConfigStore.filters.data[sectionName].selectedParams.group_id
            .filterMode.value,
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
        //description
        descriptionFilterValue:
          appConfigStore.filters.data[sectionName].selectedParams.description
            .value,
        descriptionFilterMode:
          appConfigStore.filters.data[sectionName].selectedParams.description
            .filterMode.value,
        //type
        typeFilterValue:
          appConfigStore.filters.data[sectionName].selectedParams.type.value,
        typeFilterMode:
          appConfigStore.filters.data[sectionName].selectedParams.type
            .filterMode.value,
        //price
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
    receiveItemsByGroupID(groupID) {
      this.data.isItemDataLoading = true;
      api
        .get(`/${sectionName}/prepared`, {
          params: {
            mode: "group_id",
            value: groupID,
          },
        })
        .then((res) => {
          if (res.data == 0) {
            this.bufferedItems = [];
            return;
          }

          this.bufferedItems = JSON.parse(JSON.stringify(res.data));

          this.bufferedItems.forEach((bufferedItem, index) => {
            this.bufferedItems[index].images = [];

            res.data[index].images.forEach((image) => {
              this.bufferedItems[index].images.push({
                url: image.base64,
                mimeType: image.mimeType,
                name: image.src,
              });
            });
          });
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.data.isItemDataLoading = false;
        });
    },
    receiveItemToFillUpdateWindow(id, arrayIndex) {
      this.data.isItemDataLoading = true;
      this.data.updatingItemIndexInArray = arrayIndex;
      api
        .get(`/${sectionName}/prepared`, {
          params: {
            mode: "id",
            value: id,
          },
        })
        .then((res) => {
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
          this.data.updatingItemIndexInArray = -1;
        });
    },
    receiveItemToFillCreateWindow(id, arrayIndex) {
      this.data.isItemDataLoading = true;
      this.data.creatingItemIndexInArray = arrayIndex;
      api
        .get(`/${sectionName}/prepared`, {
          params: {
            mode: "id",
            value: id,
          },
        })
        .then((res) => {
          this.dialogs.create.isShown = true;
          this.newItem = { ...res.data };
          this.newItem.images = [];
          this.newItem.availableIn = [];

          res.data.images.forEach((image) => {
            this.newItem.images.push({
              url: image.base64,
              mimeType: image.mimeType,
              name: image.src,
            });
          });
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.data.isItemDataLoading = false;
          this.data.creatingItemIndexInArray = -1;
        });
    },
    receiveItemsByArticle(article, loadingState, warehouseId = 0) {
      this.data.isItemDataLoading = true;
      loadingState.items = true;
      api
        .get(`/${sectionName}/prepared`, {
          params: {
            mode: "article",
            value: article,
            warehouse_id: warehouseId,
          },
        })
        .then((res) => {
          this.itemsFoundByArticle = { ...res.data };
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.data.isItemDataLoading = false;
          loadingState.items = false;
        });
    },
    sendIncomeData() {
      let preparedIncome = {
        warehouses: [],
      };
      preparedIncome.warehouses = this.income.map((income, index) => ({
        id: income.warehouse.id,
        batches: income.batches,
      }));

      this.dialogs.incomeCreator.isLoading = true;
      api
        .post(`/${sectionName}/income`, preparedIncome)
        .then((res) => {
          this.dialogs.incomeCreator.isShown = false;
          this.receive();
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.dialogs.incomeCreator.isLoading = false;
        });
    },
    sendOutcomeData() {
      let preparedOutcome = {
        warehouseId: this.outcome.warehouse.id,
        items: this.outcome.items.map((item) => ({
          id: item.id,
          reason: item.reasonName,
          additionalReason: item.additionalReasonName,
          reasonDetail: item.reasonDetail,
          amount: item.outcomeAmount,
        })),
      };

      this.dialogs.outcomeCreator.isLoading = true;
      api
        .post(`/${sectionName}/outcome`, preparedOutcome)
        .then((res) => {
          this.dialogs.outcomeCreator.isShown = false;
          this.receive();
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.dialogs.outcomeCreator.isLoading = false;
        });
    },
    sendItemMoveData() {
      let preparedItemMove = {
        fromWarehouseId: this.itemMove.from.warehouse.id,
        toWarehouseId: this.itemMove.to.warehouse.id,
        items: this.itemMove.items.map((item) => ({
          id: item.id,
          reason: item.reasonName,
          additionalReason: item.additionalReasonName,
          reasonDetail: item.reasonDetail,
          amount: item.itemMoveAmount,
        })),
      };

      this.dialogs.itemMove.isLoading = true;
      api
        .post(`/${sectionName}/move`, preparedItemMove)
        .then((res) => {
          this.dialogs.itemMove.isShown = false;
          this.receive();
        })
        .catch((err) => {
          appConfigStore.catchRequestError(err);
        })
        .finally(() => {
          this.dialogs.itemMove.isLoading = false;
        });
    },
  },
});
