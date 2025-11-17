<template>
  <div class="row q-col-gutter-sm q-mb-sm order-service-row">
    <!-- Service Preview Placeholder -->
    <div class="col-12 q-mb-sm">
      <div class="service-preview-placeholder" :class="{'has-service': selectedServiceDetails}">
        <template v-if="selectedServiceDetails">
          <div class="service-info">
            <div class="service-title-large">{{ selectedServiceDetails.title }}</div>
            <div class="service-price">
              {{ formatPrice(selectedServiceDetails.unconverted_price) }} {{ selectedServiceDetails.currency }}
            </div>
          </div>
        </template>
        <template v-else>
          <div class="empty-preview">
            <q-icon name="room_service" size="32px" color="grey-6" />
            <span class="empty-text">Оберіть послугу</span>
          </div>
        </template>
      </div>
    </div>

    <!-- Service Selection -->
    <q-select
      ref="serviceSelectRef"
      autocomplete="false"
      outlined
      use-input
      hide-selected
      v-model="tempServiceHolder"
      label="Послуга"
      input-debounce="400"
      :options="filteredServices"
      option-label="title"
      option-value="id"
      @filter="serviceFilter"
      @update:model-value="onServiceSelected"
      class="col-5"
      :loading="loadingState"
      :rules="[() => !!localService.service_id || 'Оберіть послугу']"
      hide-dropdown-icon
    >
      <template v-slot:append v-if="!loadingState">
        <q-avatar>
          <q-icon size="23px" name="search"></q-icon>
        </q-avatar>
      </template>
      <template v-slot:option="scope">
        <q-item v-bind="scope.itemProps">
          <q-item-section>
            <q-item-label class="service-title">{{ scope.opt.title }}</q-item-label>
            <q-item-label caption>
              Ціна: {{ formatPrice(scope.opt.unconverted_price) }} {{ scope.opt.currency }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-select>

    <!-- Price -->
    <q-input
      outlined
      v-model.number="localService.price_per_one_unit"
      label="Ціна за одиницю"
      type="number"
      step="0.01"
      class="col-3"
      :rules="[(val) => val >= 0 || 'Не менше 0']"
    />

    <!-- Quantity -->
    <q-input
      outlined
      v-model.number="localService.quantity"
      label="Кількість"
      type="number"
      class="col-3"
      :rules="[(val) => val >= 1 || 'Не менше 1']"
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
        <q-tooltip>Видалити послугу</q-tooltip>
      </q-btn>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
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
  currency: {
    type: String,
    default: "UAH",
  },
  allSelectedServiceIds: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["update:modelValue", "remove"]);

const localService = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

const serviceSelectRef = ref(null);
const tempServiceHolder = ref(null);
const servicesOptions = ref([]);
const loadingState = ref(false);
const selectedServiceDetails = ref(null);

// Filter services to exclude already selected ones
const filteredServices = computed(() => {
  return servicesOptions.value.filter(service => {
    const isAlreadySelected = (props.allSelectedServiceIds || []).includes(service.id);
    return !isAlreadySelected;
  });
});

function formatPrice(price) {
  return Math.round((parseFloat(price) || 0) * 100) / 100;
}

function serviceFilter(val, update, abort) {
  if (!val || val.length === 0) {
    update(() => {
      servicesOptions.value = [];
    });
    return;
  }

  update(() => {
    loadingState.value = true;
    servicesOptions.value = [];

    api.get('/services/search', {
      params: {
        title: val,
        limit: 50,
      },
    })
    .then((res) => {
      servicesOptions.value = res.data.data || [];
    })
    .catch((err) => {
      appConfigStore.catchRequestError(err);
    })
    .finally(() => {
      loadingState.value = false;
    });
  });
}

function onServiceSelected(service) {
  if (service && service.id) {
    selectedServiceDetails.value = service;
    localService.value.service_id = service.id;
    localService.value.price_per_one_unit = Math.round((parseFloat(service.unconverted_price) || 0) * 100) / 100;
  }

  tempServiceHolder.value = null;

  // Trigger validation to update field status
  if (serviceSelectRef.value) {
    serviceSelectRef.value.validate();
  }
}

// Load service details from props if already available (from eager loading)
watch(() => props.modelValue, (newValue) => {
  if (newValue && newValue.service_id && newValue.service && !selectedServiceDetails.value) {
    // Use the service data that was already loaded via eager loading
    // Transform to match the format from API search
    const service = newValue.service;
    selectedServiceDetails.value = {
      id: service.id,
      title: service.title,
      unconverted_price: service.price, // Map 'price' to 'unconverted_price'
      currency: service.currency,
    };
  }
}, { immediate: true, deep: true });
</script>

<style scoped>
.order-service-row {
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

.service-title {
  font-weight: 500;
  font-size: 0.95em;
}

.selected-service-preview {
  font-size: 0.85em;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 150px;
}

.service-preview-placeholder {
  min-height: 76px;
  border: 2px dashed #bbb;
  border-radius: 6px;
  background-color: #f8f8f8;
  padding: 12px;
  display: flex;
  align-items: center;
  transition: all 0.3s ease;
}

.service-preview-placeholder.has-service {
  border: 1px solid #d0d0d0;
  background-color: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.service-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
}

.service-title-large {
  font-size: 1.3em;
  font-weight: 400;
  color: #333;
}

.service-price {
  font-size: 1em;
  font-weight: 500;
  color: #666;
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
