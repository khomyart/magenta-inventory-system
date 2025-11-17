<template>
  <div class="row q-col-gutter-sm q-mb-sm order-item-row">
    <!-- Item Preview Placeholder -->
    <div class="col-12 q-mb-sm">
      <div class="item-preview-placeholder" :class="{'has-item': selectedItemDetails}">
        <template v-if="selectedItemDetails">
          <div class="list-item-body">
            <div class="list-item-description-image-holder">
              <img
                v-if="selectedItemDetails.image != null"
                class="list-item-description-image"
                :src="`${appConfigStore.imagesStoreUrl}/${selectedItemDetails.image}`"
              />
            </div>
            <div class="list-item-description-text-holder q-ml-sm">
              <div class="title">
                {{ selectedItemDetails.title }}
              </div>
              <div class="article q-mt-xs">
                <div class="article-text">
                  {{ selectedItemDetails.article }}
                </div>
                <div
                  v-if="selectedItemDetails.color_value"
                  class="article-color"
                  :style="{ backgroundColor: selectedItemDetails.color_value }"
                >
                  <span :style="{ color: selectedItemDetails.text_color_value }">{{
                    selectedItemDetails.color_article
                  }}</span>
                </div>
                <div v-if="selectedItemDetails.size" class="size">
                  {{ selectedItemDetails.size }}
                </div>
                <div v-if="selectedItemDetails.amount !== undefined" class="stock-badge">
                  Залишок: {{ getItemStock(selectedItemDetails) }}
                </div>
              </div>
            </div>
          </div>
        </template>
        <template v-else>
          <div class="empty-preview">
            <q-icon name="inventory_2" size="32px" color="grey-6" />
            <span class="empty-text">Оберіть товар за артиклем</span>
          </div>
        </template>
      </div>
    </div>

    <!-- Item Selection with Extended Article Search -->
    <q-select
      ref="itemSelectRef"
      autocomplete="false"
      outlined
      use-input
      hide-selected
      v-model="tempItemHolder"
      label="Артикль предмета"
      input-debounce="400"
      :options="filteredItems"
      @filter="itemArticleFilter"
      @update:model-value="onItemSelected"
      class="col-5"
      :loading="loadingState"
      :rules="[() => !!localItem.item_id || 'Оберіть товар']"
      lazy-rules
      hide-dropdown-icon
      :disable="!warehouseId"
    >
      <template v-slot:append v-if="!loadingState">
        <q-avatar>
          <q-icon size="23px" name="search"></q-icon>
        </q-avatar>
      </template>
      <template v-slot:option="scope">
        <q-item v-bind="scope.itemProps">
          <div class="list-item-body">
            <div class="list-item-description-image-holder">
              <img
                v-if="scope.opt.image != null"
                class="list-item-description-image"
                :src="`${appConfigStore.imagesStoreUrl}/${scope.opt.image}`"
              />
            </div>
            <div class="list-item-description-text-holder q-ml-md">
              <div class="title">
                {{ scope.opt.title }}
              </div>
              <div class="article">
                <div class="article-text q-mr-xs">
                  {{ scope.opt.article }}
                </div>
                <div
                  v-if="scope.opt.color_value"
                  class="article-color q-mr-xs"
                  :style="{ backgroundColor: scope.opt.color_value }"
                >
                  <span :style="{ color: scope.opt.text_color_value }">{{
                    scope.opt.color_article
                  }}</span>
                </div>
                <div v-if="scope.opt.size" class="size q-mr-xs">
                  {{ scope.opt.size }}
                </div>
                <div class="stock-badge">
                  Залишок: {{ getItemStock(scope.opt) }}
                </div>
              </div>
            </div>
          </div>
        </q-item>
      </template>
    </q-select>

    <!-- Price -->
    <q-input
      outlined
      v-model.number="localItem.price_per_one_unit"
      label="Ціна за одиницю"
      type="number"
      step="0.01"
      class="col-3"
      :rules="[(val) => val >= 0 || 'Не менше 0']"
    />

    <!-- Quantity -->
    <q-input
      outlined
      v-model.number="localItem.quantity"
      label="Кількість"
      type="number"
      class="col-3"
      :rules="[
        (val) => val >= 1 || 'Не менше 1',
        (val) => validateStock(val) || 'Перевищує залишок на складі'
      ]"
    />

    <!-- Remove Button -->
    <div class="col-1 remove-button-wrapper">
      <q-btn
        flat
        color="negative"
        icon="delete"
        class="remove-button"
        @click="$emit('remove', index)"
      >
        <q-tooltip>Видалити товар</q-tooltip>
      </q-btn>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from "vue";
import { useAppConfigStore } from "src/stores/appConfigStore";
import { api } from "src/boot/axios";

const appConfigStore = useAppConfigStore();

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
  warehouseId: {
    type: Number,
    default: null,
  },
  currency: {
    type: String,
    default: "UAH",
  },
  orderStatus: {
    type: String,
    default: "pending",
  },
  allSelectedItemIds: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["update:modelValue", "remove"]);

const localItem = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

const itemSelectRef = ref(null);
const tempItemHolder = ref(null);
const itemsFoundByArticle = ref([]);
const loadingState = ref(false);
const selectedItemDetails = ref(null);

// Filter items to only show those with stock in the selected warehouse
// and exclude items already selected in other rows
const filteredItems = computed(() => {
  if (!props.warehouseId) return [];

  return itemsFoundByArticle.value.filter(item => {
    const stock = getItemStock(item);
    if (stock <= 0) return false;

    // Exclude ALL selected items (including current row)
    const isAlreadySelected = (props.allSelectedItemIds || []).includes(item.id);

    return !isAlreadySelected;
  });
});

function getItemStock(item) {
  if (!props.warehouseId) return 0;

  // When warehouse_id is provided, API returns amount directly
  return item.amount || 0;
}

function validateStock(quantity) {
  if (!selectedItemDetails.value) return true;
  // Don't validate stock for 'pending' and 'confirmed' statuses
  // Stock is only checked when transitioning to 'in_progress'
  if (props.orderStatus === 'pending' || props.orderStatus === 'confirmed') return true;
  // Only validate stock if amount data is available (when searching for new items)
  // Don't validate for items already in the order (loaded via eager loading)
  if (selectedItemDetails.value.amount === undefined) return true;
  const availableStock = getItemStock(selectedItemDetails.value);
  return quantity <= availableStock;
}

function itemArticleFilter(val, update, abort) {
  if (!val || val.length === 0) {
    update(() => {
      itemsFoundByArticle.value = [];
    });
    return;
  }

  update(() => {
    loadingState.value = true;
    itemsFoundByArticle.value = [];

    api.get('/items/prepared', {
      params: {
        mode: 'article',
        value: val,
        warehouse_id: props.warehouseId || 0,
      },
    })
    .then((res) => {
      itemsFoundByArticle.value = res.data.data || [];
    })
    .catch((err) => {
      appConfigStore.catchRequestError(err);
    })
    .finally(() => {
      loadingState.value = false;
    });
  });
}

function onItemSelected(item) {
  if (item && item.id) {
    selectedItemDetails.value = item;
    localItem.value.item_id = item.id;
    localItem.value.price_per_one_unit = Math.round((parseFloat(item.price) || 0) * 100) / 100;

    // Set max quantity to available stock
    const availableStock = getItemStock(item);
    if (localItem.value.quantity > availableStock) {
      localItem.value.quantity = Math.max(1, availableStock);
    }
  }

  tempItemHolder.value = null;

  // Trigger validation to update field status
  if (itemSelectRef.value) {
    itemSelectRef.value.validate();
  }
}

// Load item details from props if already available (from eager loading)
watch(() => props.modelValue, (newValue) => {
  if (newValue && newValue.item_id && newValue.item && !selectedItemDetails.value) {
    // Use the item data that was already loaded via eager loading
    // Transform the relationship structure to flat structure expected by the component
    const item = newValue.item;
    selectedItemDetails.value = {
      id: item.id,
      article: item.article,
      title: item.title,
      price: item.price,
      currency: item.currency,
      // Handle image - get first image if available
      image: item.images && item.images.length > 0 ? item.images[0].src : null,
      // Flatten relationships
      size: item.size?.value || null,
      color_article: item.color?.article || null,
      color_value: item.color?.value || null,
      text_color_value: item.color?.text_color_value || null,
      // amount is not available from eager loading, will be undefined
    };
  }
}, { immediate: true, deep: true });
</script>

<style scoped>
.order-item-row {
  align-items: flex-start;
}

.remove-button-wrapper {
  display: flex;
  align-items: stretch;
}

.remove-button {
  width: 100%;
  min-height: 56px;
  height: 100%;
}

.list-item-body {
  display: flex;
  flex-direction: row;
  flex: 1 1 auto;
  min-width: 0;
}

.list-item-description-image-holder {
  min-width: 56px;
  min-height: 56px;
  width: 56px;
  height: 56px;
  overflow: hidden;
  display: flex;
  justify-content: center;
  border-radius: 5px;
  background-color: rgb(217, 217, 205);
  flex: 0 0 60px;
}

.list-item-description-image {
  height: 100%;
  width: auto;
}

.list-item-description-text-holder {
  display: flex;
  flex: 1 1 auto;
  min-width: 0px;
  flex-direction: column;
  justify-content: space-between;
}

.title {
  width: 100%;
  height: fit-content;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  font-weight: 400;
  font-size: 1.3em;
}

.article {
  display: flex;
  flex-direction: row;
  align-items: center;
  font-size: 0.90em;
  gap: 6px;
}

.article-text {
  font-weight: 600;
}

.article-color {
  width: fit-content;
  padding: 0 10px;
  height: 20px;
  border-radius: 3px;
  font-size: 0.95em;
  display: flex;
  align-items: center;
  font-weight: 600;
}

.size {
  font-weight: 600;
}

.stock-badge {
  color: #555;
  font-size: 0.95em;
  font-weight: 500;
}

.selected-item-preview {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.85em;
}

.article-color-mini {
  height: 12px;
  width: 12px;
  border-radius: 2px;
}

.size-mini {
  font-size: 0.9em;
  font-weight: 500;
}

.item-preview-placeholder {
  min-height: 76px;
  border: 2px dashed #bbb;
  border-radius: 6px;
  background-color: #f8f8f8;
  padding: 12px;
  display: flex;
  align-items: center;
  transition: all 0.3s ease;
}

.item-preview-placeholder.has-item {
  border: 1px solid #d0d0d0;
  background-color: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.empty-preview {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 46px;
  gap: 10px;
  color: #777;
}

.empty-text {
  font-size: 1em;
  font-weight: 500;
}
</style>
