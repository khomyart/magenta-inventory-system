<template>
  <div class="services-section">
    <div class="section-header row items-center justify-between q-mb-sm">
      <div class="text-subtitle1">Послуги</div>
      <q-btn
        flat
        color="primary"
        icon="add"
        label="Додати послугу"
        @click="addService"
      />
    </div>

    <div v-if="localServices.length === 0" class="empty-message q-pa-md q-mb-md">
      <q-icon name="room_service" size="24px" class="q-mr-sm" />
      Натисніть "Додати послугу", щоб додати послуги до замовлення
    </div>

    <div v-for="(service, index) in localServices" :key="service._uid">
      <OrderServiceSelectorComponent
        v-model="localServices[index]"
        :index="index"
        :currency="currency"
        :all-selected-service-ids="selectedServiceIds"
        @remove="removeService"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import OrderServiceSelectorComponent from "./OrderServiceSelectorComponent.vue";

const props = defineProps({
  modelValue: {
    type: Array,
    required: true,
  },
  currency: {
    type: String,
    default: "UAH",
  },
});

const emit = defineEmits(["update:modelValue"]);

const localServices = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

const selectedServiceIds = computed(() => {
  return localServices.value
    .map(service => service.service_id)
    .filter(id => id != null);
});

function addService() {
  localServices.value.push({
    _uid: Date.now() + Math.random(),
    service_id: null,
    price_per_one_unit: 0,
    quantity: 1,
  });
}

function removeService(index) {
  localServices.value.splice(index, 1);
}
</script>

<style scoped>
.services-section {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 4px;
  padding: 16px 16px 5px;
  background-color: #fafafa;
}

.section-header {
  margin-bottom: 12px;
}

/* Make all inputs white */
.services-section :deep(.q-field__control) {
  background-color: white;
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
