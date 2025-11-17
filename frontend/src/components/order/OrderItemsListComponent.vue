<template>
  <div class="items-section">
    <div class="section-header row items-center justify-between q-mb-sm">
      <div class="text-subtitle1">
        Товари
        <q-icon name="help_outline" size="18px" color="grey-7" class="q-ml-xs cursor-pointer">
          <q-tooltip class="bg-black text-body2" max-width="300px">
            Пошук за артиклем: (артикль предмета) (артикль кольору) (розмір)
          </q-tooltip>
        </q-icon>
      </div>
      <q-btn
        flat
        color="primary"
        icon="add"
        label="Додати товар"
        @click="addItem"
        :disable="!warehouseId"
      >
        <q-tooltip v-if="!warehouseId" class="bg-black text-body2">
          Спочатку оберіть склад
        </q-tooltip>
      </q-btn>
    </div>


    <div v-if="!warehouseId" class="warning-message q-pa-md q-mb-md">
      <q-icon name="info" size="20px" class="q-mr-sm" />
      Оберіть склад, щоб додати товари до замовлення
    </div>

    <div v-if="localItems.length === 0 && warehouseId" class="empty-message q-pa-md q-mb-md">
      <q-icon name="inventory_2" size="24px" class="q-mr-sm" />
      Натисніть "Додати товар", щоб додати товари до замовлення
    </div>

    <div v-for="(item, index) in localItems" :key="item._uid || index">
      <OrderItemSelectorComponent
        v-model="localItems[index]"
        :index="index"
        :warehouse-id="warehouseId"
        :currency="currency"
        :order-status="orderStatus"
        :all-selected-item-ids="selectedItemIds"
        @remove="removeItem"
      />
    </div>
  </div>

</template>

<script setup>
import { computed } from "vue";
import OrderItemSelectorComponent from "./OrderItemSelectorComponent.vue";

const props = defineProps({
  modelValue: {
    type: Array,
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
});

const emit = defineEmits(["update:modelValue"]);

const localItems = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

const selectedItemIds = computed(() => {
  return localItems.value
    .map(item => item.item_id)
    .filter(id => id != null);
});

function addItem() {
  if (!props.warehouseId) return;

  localItems.value.push({
    _uid: Date.now() + Math.random(), // Unique ID for Vue key tracking
    item_id: null,
    price_per_one_unit: 0,
    quantity: 1,
  });
}

function removeItem(index) {
  localItems.value.splice(index, 1);
}
</script>

<style scoped>
.items-section {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 4px;
  padding: 16px 16px 5px;
  background-color: #fafafa;
}

.section-header {
  margin-bottom: 12px;
}

/* Make all inputs white */
.items-section :deep(.q-field__control) {
  background-color: white;
}

.warning-message {
  background-color: #fff3cd;
  border: 1px solid #ffc107;
  border-radius: 4px;
  color: #856404;
  display: flex;
  align-items: center;
}

.empty-message {
  background-color: #e3f2fd;
  border: 1px solid #2196f3;
  border-radius: 4px;
  color: #0d47a1;
  display: flex;
  align-items: center;
}
</style>
