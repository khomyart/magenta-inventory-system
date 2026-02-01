<script setup>
import {reactive, computed, watch, ref, nextTick} from "vue";
import {useOrderStore} from "stores/orderStore";
import {useContactStore} from "stores/contactStore";
import {useWarehouseStore} from "stores/warehouseStore";
import {useUserStore} from "stores/userStore";
import {useCityStore} from "src/stores/helpers/cityStore";
import {useCountryStore} from "src/stores/helpers/countryStore";
import {useAppConfigStore} from "src/stores/appConfigStore";
import {api} from "src/boot/axios";
import parsePhoneNumberFromString from "libphonenumber-js";
import DateTimeInputComponent from "components/input/DateTimeInputComponent.vue";
import ContactDetailsComponent from "../contact/ContactDetailsComponent.vue";
import OrderItemsListComponent from "./OrderItemsListComponent.vue";
import OrderServicesListComponent from "./OrderServicesListComponent.vue";
import {getServerTime, getClientTime} from "app/helpers/GeneralPurposeFunctions";

const props = defineProps(['orderData']);

const sectionStore = useOrderStore();
const contactStore = useContactStore();
const warehouseStore = useWarehouseStore();
const userStore = useUserStore();
const countryStore = useCountryStore();
const cityStore = useCityStore();
const appConfigStore = useAppConfigStore();

const showContactDetailsDialog = ref(false);
const showCreateContactDialog = ref(false);
const contactSelectRef = ref(null);
const contactsOptions = ref([]);
const tempContactHolder = ref(null);
const selectedContactDetails = ref(null);
const loadingContactState = ref(false);
const itemsServicesValidationError = ref('');
const validationErrorBannerRef = ref(null);

// Warehouse selection states
const warehouseSelection = reactive({
  country: null,
  city: null,
  warehouse: null,
});

const loadingStates = reactive({
  country: false,
  city: false,
  warehouse: false,
});

const userLoadingStates = reactive({
  level1: false,
  level2: false,
  level3: false,
});

let editedItem = reactive({
  id: null,
  status: null,
  currency: "UAH",
  discount: 0,
  warehouse_id: null,
  warehouse: null,
  contact_id: null,
  contact: null,
  order_items: [],
  order_services: [],
  amount_of_advance_payment_on_card: 0,
  amount_of_advance_payment_via_terminal: 0,
  amount_of_advance_payment_as_cash: 0,
  amount_of_final_payment_on_card: 0,
  amount_of_final_payment_via_terminal: 0,
  amount_of_final_payment_as_cash: 0,
  completion_deadline: null,
  completed_at: null,
  fully_payed_at: null,
  notes: "",
  involvement_level_1_user_id: null,
  involvement_level_2_user_id: null,
  involvement_level_3_user_id: null,
});

// Populate editedItem when orderData changes
watch(() => props.orderData, (newOrderData) => {
  if (newOrderData && sectionStore.dialogs.update.isShown) {
    editedItem.id = newOrderData.id;
    editedItem.status = newOrderData.status;
    editedItem.currency = newOrderData.currency;
    editedItem.discount = newOrderData.discount || 0;
    editedItem.warehouse_id = newOrderData.warehouse_id;
    editedItem.warehouse = newOrderData.warehouse ? JSON.parse(JSON.stringify(newOrderData.warehouse)) : null;
    editedItem.contact_id = newOrderData.contact_id;
    editedItem.contact = newOrderData.contact ? JSON.parse(JSON.stringify(newOrderData.contact)) : null;
    editedItem.order_items = newOrderData.order_items ? JSON.parse(JSON.stringify(newOrderData.order_items)) : [];
    editedItem.order_services = newOrderData.order_services ? JSON.parse(JSON.stringify(newOrderData.order_services)) : [];
    editedItem.amount_of_advance_payment_on_card = newOrderData.amount_of_advance_payment_on_card || 0;
    editedItem.amount_of_advance_payment_via_terminal = newOrderData.amount_of_advance_payment_via_terminal || 0;
    editedItem.amount_of_advance_payment_as_cash = newOrderData.amount_of_advance_payment_as_cash || 0;
    editedItem.amount_of_final_payment_on_card = newOrderData.amount_of_final_payment_on_card || 0;
    editedItem.amount_of_final_payment_via_terminal = newOrderData.amount_of_final_payment_via_terminal || 0;
    editedItem.amount_of_final_payment_as_cash = newOrderData.amount_of_final_payment_as_cash || 0;
    // Convert dates from server format to client format
    editedItem.completion_deadline = newOrderData.completion_deadline
      ? getClientTime(newOrderData.completion_deadline, 'ua', true, false)
      : null;
    editedItem.completed_at = newOrderData.completed_at
      ? getClientTime(newOrderData.completed_at, 'ua', true, false)
      : null;
    editedItem.fully_payed_at = newOrderData.fully_payed_at
      ? getClientTime(newOrderData.fully_payed_at, 'ua', true, false)
      : null;
    editedItem.notes = newOrderData.notes || "";
    editedItem.involvement_level_1_user_id = newOrderData.involvement_level_1_user_id || null;
    editedItem.involvement_level_2_user_id = newOrderData.involvement_level_2_user_id || null;
    editedItem.involvement_level_3_user_id = newOrderData.involvement_level_3_user_id || null;

    // Initialize warehouse selection
    if (newOrderData.warehouse) {
      warehouseSelection.country = newOrderData.warehouse.city?.country || null;
      warehouseSelection.city = newOrderData.warehouse.city || null;
      warehouseSelection.warehouse = newOrderData.warehouse;
    } else {
      warehouseSelection.country = null;
      warehouseSelection.city = null;
      warehouseSelection.warehouse = null;
    }

    // Initialize contact selection
    if (newOrderData.contact) {
      selectedContactDetails.value = JSON.parse(JSON.stringify(newOrderData.contact));
    } else {
      selectedContactDetails.value = null;
    }

    // Reset states
    tempContactHolder.value = null;
    contactsOptions.value = [];
    itemsServicesValidationError.value = '';

    warehouseStore.getFavoriteWarehouses();

    // Load users if status is completed
    if (newOrderData.status === 'completed') {
      userStore.fetchUsers();
    }
  }
});

// Auto-clear validation error when items/services are added
watch([() => editedItem.order_items.length, () => editedItem.order_services.length], () => {
  if (itemsServicesValidationError.value && (editedItem.order_items.length > 0 || editedItem.order_services.length > 0)) {
    itemsServicesValidationError.value = '';
  }
});

const totalPrice = computed(() => {
  let total = 0;

  // Calculate items total
  editedItem.order_items.forEach(item => {
    if (item.price_per_one_unit && item.quantity) {
      total += item.price_per_one_unit * item.quantity;
    }
  });

  // Calculate services total
  editedItem.order_services.forEach(service => {
    if (service.price_per_one_unit && service.quantity) {
      total += service.price_per_one_unit * service.quantity;
    }
  });

  // Apply discount
  total -= editedItem.discount || 0;

  return Math.max(0, total).toFixed(2);
});

function fillWarehouseFromFavorite(index) {
  warehouseSelection.country = warehouseStore.favoriteWarehouses[index].country;
  warehouseSelection.city = warehouseStore.favoriteWarehouses[index].city;
  warehouseSelection.warehouse = warehouseStore.favoriteWarehouses[index].warehouse;
  editedItem.warehouse_id = warehouseStore.favoriteWarehouses[index].warehouse.id;
}

function countryFilter(val, update, abort) {
  update(() => {
    loadingStates.country = true;
    countryStore.items = [];
    countryStore.receive(val, loadingStates);
  });
}

function cityFilter(val, update, abort) {
  update(() => {
    loadingStates.city = true;
    cityStore.items = [];
    cityStore.receive(
      warehouseSelection.country.id,
      val,
      loadingStates
    );
  });
}

function warehouseFilter(val, update, abort) {
  update(() => {
    loadingStates.warehouse = true;
    warehouseStore.simpleItems = [];
    warehouseStore.simpleReceive(
      val,
      warehouseSelection.city.id,
      loadingStates
    );
  });
}

function countryUpdate() {
  warehouseSelection.city = null;
  warehouseSelection.warehouse = null;
  editedItem.warehouse_id = null;
}

function cityUpdate() {
  warehouseSelection.warehouse = null;
  editedItem.warehouse_id = null;
}

function warehouseUpdate() {
  if (warehouseSelection.warehouse && warehouseSelection.warehouse.id) {
    editedItem.warehouse_id = warehouseSelection.warehouse.id;
  } else {
    editedItem.warehouse_id = null;
  }
}

const newContact = reactive({
  name: "",
  phone: "",
  email: "",
  address: "",
  preferred_platforms: [],
  additional_info: "",
});

function contactFilter(val, update, abort) {
  if (!val || val.length === 0) {
    update(() => {
      contactsOptions.value = [];
    });
    return;
  }

  update(() => {
    loadingContactState.value = true;
    contactsOptions.value = [];

    api.get('/contacts/simple', {
      params: {
        search_filter_value: val,
      },
    })
      .then((res) => {
        contactsOptions.value = res.data.data || [];
      })
      .catch((err) => {
        appConfigStore.catchRequestError(err);
      })
      .finally(() => {
        loadingContactState.value = false;
      });
  });
}

function onContactSelected(contact) {
  if (contact && contact.id) {
    selectedContactDetails.value = contact;
    editedItem.contact_id = contact.id;
    editedItem.contact = contact;
  }
  tempContactHolder.value = null;

  if (contactSelectRef.value) {
    contactSelectRef.value.validate();
  }
}

function clearContact() {
  selectedContactDetails.value = null;
  editedItem.contact_id = null;
  editedItem.contact = null;

  if (contactSelectRef.value) {
    contactSelectRef.value.validate();
  }
}

const phoneValidationRule = (val) => {
  let message = "Введіть коректний номер телефону";

  let parsedPhone = parsePhoneNumberFromString("+" + val);
  if (parsedPhone) {
    return parsedPhone.isValid() || message;
  }

  return message;
};

function createContact() {
  api.post('/contacts', newContact)
    .then((res) => {
      showCreateContactDialog.value = false;
      selectedContactDetails.value = res.data.contact;
      editedItem.contact_id = res.data.contact.id;
      editedItem.contact = res.data.contact;

      if (contactSelectRef.value) {
        contactSelectRef.value.validate();
      }

      newContact.name = "";
      newContact.phone = "";
      newContact.email = "";
      newContact.address = "";
      newContact.preferred_platforms = [];
      newContact.additional_info = "";
    })
    .catch((err) => {
      appConfigStore.catchRequestError(err);
    });
}

function submitOrder() {
  // Validate that at least one item or service is present
  if (editedItem.order_items.length === 0 && editedItem.order_services.length === 0) {
    itemsServicesValidationError.value = "Додайте хоча б один товар або послугу до замовлення";

    nextTick(() => {
      if (validationErrorBannerRef.value) {
        validationErrorBannerRef.value.$el.scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
      }
    });

    return;
  }

  itemsServicesValidationError.value = '';

  const {completed_at, ...itemWithoutCompletedAt} = editedItem;
  const payload = {
    ...itemWithoutCompletedAt,
    // Use editedItem warehouse_id (can be changed for pending orders)
    warehouse_id: editedItem.warehouse_id,
    // Remove _uid from items and services before sending to backend
    order_items: editedItem.order_items.map(({_uid, ...item}) => item),
    order_services: editedItem.order_services.map(({_uid, ...service}) => service),
    // Include involvement levels
    involvement_level_1_user_id: editedItem.involvement_level_1_user_id,
    involvement_level_2_user_id: editedItem.involvement_level_2_user_id,
    involvement_level_3_user_id: editedItem.involvement_level_3_user_id,
    // Convert dates to proper format for backend (with timezone conversion)
    completion_deadline: editedItem.completion_deadline
      ? getServerTime(editedItem.completion_deadline, 'ua', true, true)
      : null,
    fully_payed_at: editedItem.fully_payed_at
      ? getServerTime(editedItem.fully_payed_at, 'ua', true, true)
      : null,
  };
  sectionStore.update(payload);
}

function userFilterLevel1(val, update, abort) {
  update(async () => {
    userLoadingStates.level1 = true;
    await userStore.fetchUsers(val);
    userLoadingStates.level1 = false;
  });
}

function userFilterLevel2(val, update, abort) {
  update(async () => {
    userLoadingStates.level2 = true;
    await userStore.fetchUsers(val);
    userLoadingStates.level2 = false;
  });
}

function userFilterLevel3(val, update, abort) {
  update(async () => {
    userLoadingStates.level3 = true;
    await userStore.fetchUsers(val);
    userLoadingStates.level3 = false;
  });
}
</script>

<template>
  <q-dialog v-model="sectionStore.dialogs.update.isShown" persistent>
    <q-card style="min-width: 700px; max-width: 1100px;">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="edit" color="black" size="md" class="q-mr-sm"/>
          Редагування замовлення #{{ editedItem.id }}
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="submitOrder">
        <div class="q-mb-md"></div>
        <q-card-section
          style="max-height: 70vh"
          class="scroll q-pt-none row q-col-gutter-md"
        >
          <!-- Contact Section -->
          <div class="col-6">
            <!-- Editable Contact (для pending та confirmed) -->
            <div v-if="['pending', 'confirmed'].includes(editedItem.status)" class="contact-wrapper">
              <div class="section-header q-mb-sm">
                <div class="text-subtitle1">Контакт</div>
              </div>
              <div class="row q-col-gutter-sm">
                <div class="col-10 q-mb-md">
                  <q-select
                    ref="contactSelectRef"
                    autocomplete="false"
                    outlined
                    use-input
                    hide-selected
                    v-model="tempContactHolder"
                    label="Пошук контакту (ім'я або телефон)"
                    input-debounce="400"
                    :options="contactsOptions"
                    option-label="name"
                    option-value="id"
                    @filter="contactFilter"
                    @update:model-value="onContactSelected"
                    :loading="loadingContactState"
                    hide-dropdown-icon
                  >
                    <template v-slot:append v-if="!loadingContactState">
                      <q-avatar>
                        <q-icon size="23px" name="search"></q-icon>
                      </q-avatar>
                    </template>
                    <template v-slot:option="scope">
                      <q-item v-bind="scope.itemProps">
                        <q-item-section>
                          <q-item-label class="contact-option-name">{{ scope.opt.name }}</q-item-label>
                          <q-item-label caption>
                            {{ scope.opt.phone || '' }}
                            <span v-if="scope.opt.phone && scope.opt.email"> • </span>
                            {{ scope.opt.email || '' }}
                          </q-item-label>
                        </q-item-section>
                      </q-item>
                    </template>
                  </q-select>
                </div>
                <div class="col-2">
                  <q-btn
                    outline
                    color="primary"
                    icon="person_add"
                    style="height: 56px;"
                    @click="showCreateContactDialog = true"
                  >
                    <q-tooltip class="bg-black text-body2">Створити контакт</q-tooltip>
                  </q-btn>
                </div>
              </div>

              <!-- Contact Preview -->
              <div v-if="selectedContactDetails" class="contact-preview q-mb-md">
                <div class="contact-preview-header">
                  <q-icon name="person" size="24px" class="q-mr-sm"/>
                  <div class="contact-preview-info">
                    <div class="contact-name">{{ selectedContactDetails.name }}</div>
                    <div class="contact-details">
                      <span v-if="selectedContactDetails.phone">{{ selectedContactDetails.phone }}</span>
                      <span v-if="selectedContactDetails.phone && selectedContactDetails.email"> • </span>
                      <span v-if="selectedContactDetails.email">{{ selectedContactDetails.email }}</span>
                    </div>
                  </div>
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="info"
                    @click="showContactDetailsDialog = true"
                  >
                    <q-tooltip class="bg-black text-body2">Деталі контакту</q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    round
                    color="negative"
                    icon="close"
                    @click="clearContact"
                  >
                    <q-tooltip class="bg-black text-body2">Очистити контакт</q-tooltip>
                  </q-btn>
                </div>
              </div>
            </div>

            <!-- Read-only Contact (для інших статусів) -->
            <div v-else class="readonly-section">
              <div class="section-header q-mb-sm">
                <div class="text-subtitle1">Контакт (тільки перегляд)</div>
              </div>
              <div v-if="editedItem.contact" class="contact-preview q-mb-sm">
                <div class="contact-preview-header">
                  <q-icon name="person" size="24px" class="q-mr-sm"/>
                  <div class="contact-preview-info">
                    <div class="contact-name">{{ editedItem.contact.name }}</div>
                    <div class="contact-details">
                      <span v-if="editedItem.contact.phone">{{ editedItem.contact.phone }}</span>
                      <span v-if="editedItem.contact.phone && editedItem.contact.email"> • </span>
                      <span v-if="editedItem.contact.email">{{ editedItem.contact.email }}</span>
                    </div>
                  </div>
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="info"
                    @click="showContactDetailsDialog = true"
                  >
                    <q-tooltip class="bg-black text-body2">Деталі контакту</q-tooltip>
                  </q-btn>
                </div>
              </div>
              <div v-else class="text-grey-7 q-mb-sm">
                Контакт не вказано
              </div>
            </div>
          </div>

          <!-- Warehouse Section -->
          <div class="col-6">
            <!-- Editable Warehouse (для pending та confirmed) -->
            <div v-if="['pending', 'confirmed'].includes(editedItem.status)" class="warehouse-wrapper">
              <div class="section-header q-mb-sm">
                <div class="text-subtitle1">Склад</div>
              </div>
              <!-- Favorite Warehouses -->
              <div class="row q-gutter-md q-mb-md"
                   v-if="warehouseStore.favoriteWarehouses && warehouseStore.favoriteWarehouses.length > 0">
                <div
                  :class="{
                    'favorite-warehouse-button-active':
                      warehouseSelection.warehouse != null &&
                      warehouseSelection.warehouse.id === warehouseInfo.warehouse.id,
                  }"
                  class="favorite-warehouse-button q-pa-sm"
                  @click="fillWarehouseFromFavorite(index)"
                  v-for="(warehouseInfo, index) in warehouseStore.favoriteWarehouses"
                  :key="index"
                >
                  {{ warehouseInfo.warehouse.name }} ({{ warehouseInfo.city.name }},
                  {{ warehouseInfo.warehouse.address }})
                </div>
              </div>

              <!-- Country and City Selects -->
              <div class="row q-col-gutter-md q-mb-xs">
                <q-select
                  autocomplete="false"
                  :hide-dropdown-icon="warehouseSelection.country != null"
                  outlined
                  v-model="warehouseSelection.country"
                  use-input
                  hide-selected
                  fill-input
                  label="Країна"
                  input-debounce="400"
                  :options="countryStore.items"
                  option-value="id"
                  option-label="name"
                  @update:model-value="countryUpdate"
                  @filter="countryFilter"
                  :loading="loadingStates.country"
                  class="col-6"
                >
                  <template
                    v-if="warehouseSelection.country != null && warehouseSelection.country.id != undefined && !loadingStates.country"
                    v-slot:append
                  >
                    <q-icon
                      name="cancel"
                      @click.stop.prevent="
                        warehouseSelection.country = null;
                        countryUpdate();
                      "
                      class="cursor-pointer"
                    />
                  </template>
                </q-select>

                <q-select
                  autocomplete="false"
                  :hide-dropdown-icon="
                    warehouseSelection.country == null ||
                    warehouseSelection.country.id == undefined ||
                    warehouseSelection.city != null
                  "
                  outlined
                  v-model="warehouseSelection.city"
                  label="Місто"
                  use-input
                  hide-selected
                  fill-input
                  input-debounce="400"
                  :options="cityStore.items"
                  option-value="id"
                  option-label="name"
                  @update:model-value="cityUpdate"
                  @filter="cityFilter"
                  :loading="loadingStates.city"
                  class="col-6"
                  :disable="
                    warehouseSelection.country == null ||
                    warehouseSelection.country.id == undefined
                  "
                  :rules="[
                    () =>
                      !warehouseSelection.country ||
                      (warehouseSelection.city != null &&
                        warehouseSelection.city.id != undefined) ||
                      'Оберіть місто',
                  ]"
                >
                  <template
                    v-if="warehouseSelection.city != null && warehouseSelection.city.id != undefined && !loadingStates.city"
                    v-slot:append
                  >
                    <q-icon
                      name="cancel"
                      @click.stop.prevent="
                        warehouseSelection.city = null;
                        cityUpdate();
                      "
                      class="cursor-pointer"
                    />
                  </template>
                </q-select>
              </div>

              <!-- Warehouse Select -->
              <div class="row">
                <q-select
                  autocomplete="false"
                  :hide-dropdown-icon="
                    warehouseSelection.city == null ||
                    warehouseSelection.city.id == undefined ||
                    warehouseSelection.warehouse != null
                  "
                  outlined
                  v-model="warehouseSelection.warehouse"
                  label="Склад"
                  use-input
                  hide-selected
                  fill-input
                  input-debounce="400"
                  :options="warehouseStore.simpleItems"
                  option-value="id"
                  option-label="name"
                  @update:model-value="warehouseUpdate"
                  @filter="warehouseFilter"
                  :loading="loadingStates.warehouse"
                  class="col-12"
                  :disable="
                    warehouseSelection.city == null ||
                    warehouseSelection.city.id == undefined
                  "
                  :rules="[
                    () =>
                      !warehouseSelection.city ||
                      (warehouseSelection.warehouse != null &&
                        warehouseSelection.warehouse.id != undefined) ||
                      'Оберіть склад',
                  ]"
                >
                  <template v-slot:option="scope">
                    <q-item v-bind="scope.itemProps" class="flex items-center">
                      {{ scope.opt.name }} ({{ scope.opt.address }})
                    </q-item>
                  </template>
                  <template
                    v-if="warehouseSelection.warehouse != null && warehouseSelection.warehouse.id != undefined && !loadingStates.warehouse"
                    v-slot:append
                  >
                    <q-icon
                      name="cancel"
                      @click.stop.prevent="
                        warehouseSelection.warehouse = null;
                        warehouseUpdate();
                      "
                      class="cursor-pointer"
                    />
                  </template>
                </q-select>
              </div>
            </div>

            <!-- Read-only Warehouse (для інших статусів) -->
            <div v-else class="readonly-section">
              <div class="section-header q-mb-sm">
                <div class="text-subtitle1">Склад (тільки перегляд)</div>
              </div>
              <div v-if="editedItem.warehouse" class="warehouse-preview q-mb-md">
                <div class="warehouse-preview-header">
                  <div class="warehouse-preview-info">

                    <div class="row items-center q-mb-sm">
                      <q-icon name="warehouse" size="24px" class="q-mr-sm"/>
                      <div class="warehouse-name">
                        {{ editedItem.warehouse.name }}
                        <span v-if="editedItem.warehouse.city?.name || editedItem.warehouse.city?.country?.name" class="text-grey-7 text-weight-regular">
                          ({{ [editedItem.warehouse.city?.name, editedItem.warehouse.city?.country?.name].filter(Boolean).join(', ') }})
                        </span>
                      </div>
                    </div>
                    <div class="warehouse-details">
                      <span v-if="editedItem.warehouse.address">Адреса: {{ editedItem.warehouse.address }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-grey-7 q-mb-md">
                Склад не вказано
              </div>
            </div>
          </div>

          <!-- Items Section -->
          <div class="col-6">
            <!-- Editable Items (для pending та confirmed) -->
            <OrderItemsListComponent
              v-if="['pending', 'confirmed'].includes(editedItem.status)"
              class="full-width"
              v-model="editedItem.order_items"
              :warehouse-id="editedItem.warehouse_id"
              :currency="editedItem.currency"
              :order-status="editedItem.status"
            />

            <!-- Read-only Items (для інших статусів) -->
            <div v-else class="readonly-section">
              <div class="section-header q-mb-sm">
                <div class="text-subtitle1">Товари (тільки перегляд)</div>
              </div>
              <q-list class="q-mb-md" bordered separator v-if="editedItem.order_items.length > 0">
                <q-item v-for="(item, index) in editedItem.order_items" :key="index">
                  <q-item-section>
                    <q-item-label>
                      {{ item.item?.title || `Item #${item.item_id}` }}
                      <span v-if="item.item?.color?.article || item.item?.size?.value" class="text-grey-7">
                        ({{item.item?.article}}{{ item.item?.color?.article ? ` ${item.item.color.article}` : null }}{{ item.item?.size?.value ? ` ${item.item.size.value}` : null }})
                      </span>
                    </q-item-label>
                    <q-item-label caption>
                      {{ item.quantity }} × {{ item.price_per_one_unit }} {{ editedItem.currency }}
                      = {{ (item.quantity * item.price_per_one_unit).toFixed(2) }} {{ editedItem.currency }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
              <div v-else class="text-grey-7 q-mb-md">Немає товарів</div>
            </div>
          </div>

          <!-- Services Section -->
          <div class="col-6">
            <!-- Editable Services (для pending та confirmed) -->
            <OrderServicesListComponent
              v-if="['pending', 'confirmed'].includes(editedItem.status)"
              class="full-width"
              v-model="editedItem.order_services"
              :currency="editedItem.currency"
            />

            <!-- Read-only Services (для інших статусів) -->
            <div v-else class="readonly-section">
              <div class="section-header q-mb-sm">
                <div class="text-subtitle1">Послуги (тільки перегляд)</div>
              </div>
              <q-list class="q-mb-md" bordered separator v-if="editedItem.order_services.length > 0">
                <q-item v-for="(service, index) in editedItem.order_services" :key="index">
                  <q-item-section>
                    <q-item-label>{{ service.service?.title || `Service #${service.service_id}` }}</q-item-label>
                    <q-item-label caption>
                      {{ service.quantity }} × {{ service.price_per_one_unit }} {{ editedItem.currency }}
                      = {{ (service.quantity * service.price_per_one_unit).toFixed(2) }} {{ editedItem.currency }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
              <div v-else class="text-grey-7 q-mb-md">Немає послуг</div>
            </div>
          </div>

          <!-- Validation Error Banner -->
          <div class="col-12" v-if="itemsServicesValidationError">
            <q-banner ref="validationErrorBannerRef" class="bg-red-1 text-red-9" rounded>
              <template v-slot:avatar>
                <q-icon name="error" color="red"/>
              </template>
              {{ itemsServicesValidationError }}
            </q-banner>
          </div>

          <div class="col-12 q-mt-md q-pa-none"></div>

          <!-- Discount and Total -->
          <div class="col-12 text-subtitle1">Вартість</div>
          <div class="col-6">
            <q-input
              outlined
              v-model.number="editedItem.discount"
              label="Знижка"
              type="number"
              step="0.01"
              :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
            />
          </div>

          <div class="col-6">
            <q-input
              outlined
              :model-value="totalPrice"
              label="Загальна сума (авто)"
              readonly
              bg-color="grey-3"
            >
            </q-input>
          </div>

          <!-- Payment Fields -->
          <div class="col-12 text-subtitle1">Передоплата</div>
          <q-input
            outlined
            v-model.number="editedItem.amount_of_advance_payment_on_card"
            label="Картка"
            type="number"
            step="0.01"
            class="col-4"
            :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
          />
          <q-input
            outlined
            v-model.number="editedItem.amount_of_advance_payment_via_terminal"
            label="Термінал"
            type="number"
            step="0.01"
            class="col-4"
            :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
          />
          <q-input
            outlined
            v-model.number="editedItem.amount_of_advance_payment_as_cash"
            label="Готівка"
            type="number"
            step="0.01"
            class="col-4"
            :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
          />

          <div class="col-12 text-subtitle1">Фінальна оплата</div>
          <q-input
            outlined
            v-model.number="editedItem.amount_of_final_payment_on_card"
            label="Картка"
            type="number"
            step="0.01"
            class="col-4"
            :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
          />
          <q-input
            outlined
            v-model.number="editedItem.amount_of_final_payment_via_terminal"
            label="Термінал"
            type="number"
            step="0.01"
            class="col-4"
            :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
          />
          <q-input
            outlined
            v-model.number="editedItem.amount_of_final_payment_as_cash"
            label="Готівка"
            type="number"
            step="0.01"
            class="col-4"
            :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
          />

          <!-- Dates -->
          <div class="col-12 text-subtitle1">Дати</div>
          <DateTimeInputComponent
            label="Дедлайн виконання"
            class="col-4"
            v-model="editedItem.completion_deadline"
          />
          <DateTimeInputComponent
            label="Дата повної оплати"
            class="col-4"
            v-model="editedItem.fully_payed_at"
          />
          <q-input
            outlined
            readonly
            bg-color="grey-3"
            label="Дата завершення (автоматично)"
            class="col-4"
            :model-value="editedItem.completed_at || '-'"
          >
            <template v-slot:prepend>
              <q-icon name="event"/>
            </template>
          </q-input>

          <!-- Notes -->
          <div class="col-12 text-subtitle1 q-mt-md">Нотатки</div>
          <q-input
            outlined
            v-model="editedItem.notes"
            label="Нотатки до замовлення"
            type="textarea"
            autogrow
            :maxlength="5000"
            counter
            class="col-12"
            :rules="[
              (val) => !val || val.length <= 5000 || 'Не більше 5000 символів',
            ]"
          />

          <template v-if="['completed', 'in_progress'].includes(editedItem.status)">
            <div class="col-12 text-subtitle1 q-mt-md">Залученість у виконання завдання</div>
            <div class="col-4">
              <q-select
                outlined
                v-model="editedItem.involvement_level_1_user_id"
                :options="userStore.users"
                option-value="id"
                option-label="name"
                emit-value
                hide-dropdown-icon
                map-options
                clearable
                use-input
                @filter="userFilterLevel1"
                label="Повна (11%)"
                :loading="userLoadingStates.level1"
                :disable="editedItem.involvement_level_2_user_id !== null || editedItem.involvement_level_3_user_id !== null"
              >
                <template v-slot:append v-if="!userLoadingStates.level1 && !editedItem.involvement_level_1_user_id">
                  <q-icon name="search"/>
                </template>
              </q-select>
              <q-btn
                v-if="editedItem.involvement_level_2_user_id === null && editedItem.involvement_level_3_user_id === null"
                flat
                dense
                color="primary"
                size="sm"
                class="q-mt-xs"
                @click="editedItem.involvement_level_1_user_id = userStore.data.id"
              >
                Обрати себе
              </q-btn>
            </div>
            <div class="col-4">
              <q-select
                outlined
                v-model="editedItem.involvement_level_2_user_id"
                :options="userStore.users.filter(u => u.id !== editedItem.involvement_level_1_user_id && u.id !== editedItem.involvement_level_3_user_id)"
                option-value="id"
                option-label="name"
                emit-value
                map-options
                hide-dropdown-icon
                clearable
                use-input
                @filter="userFilterLevel2"
                label="Часткова (8%)"
                :loading="userLoadingStates.level2"
                :disable="editedItem.involvement_level_1_user_id !== null"
              >
                <template v-slot:append v-if="!userLoadingStates.level2 && !editedItem.involvement_level_2_user_id">
                  <q-icon name="search"/>
                </template>
              </q-select>
              <q-btn
                v-if="editedItem.involvement_level_1_user_id === null && editedItem.involvement_level_3_user_id !== userStore.data.id"
                flat
                dense
                color="primary"
                size="sm"
                class="q-mt-xs"
                @click="editedItem.involvement_level_2_user_id = userStore.data.id"
              >
                Обрати себе
              </q-btn>
            </div>
            <div class="col-4">
              <q-select
                outlined
                v-model="editedItem.involvement_level_3_user_id"
                :options="userStore.users.filter(u => u.id !== editedItem.involvement_level_1_user_id && u.id !== editedItem.involvement_level_2_user_id)"
                option-value="id"
                option-label="name"
                emit-value
                map-options
                hide-dropdown-icon
                clearable
                use-input
                @filter="userFilterLevel3"
                label="Дотична (5%)"
                :loading="userLoadingStates.level3"
                :disable="editedItem.involvement_level_1_user_id !== null"
              >
                <template v-slot:append v-if="!userLoadingStates.level3 && !editedItem.involvement_level_3_user_id">
                  <q-icon name="search"/>
                </template>
              </q-select>
              <q-btn
                v-if="editedItem.involvement_level_1_user_id === null && editedItem.involvement_level_2_user_id !== userStore.data.id"
                flat
                dense
                color="primary"
                size="sm"
                class="q-mt-xs"
                @click="editedItem.involvement_level_3_user_id = userStore.data.id"
              >
                Обрати себе
              </q-btn>
            </div>
          </template>
        </q-card-section>

        <q-separator/>

        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn
            flat
            color="primary"
            type="submit"
            :loading="sectionStore.dialogs.update.isLoading"
          ><b>Зберегти</b></q-btn>
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- Contact Details Dialog -->
  <ContactDetailsComponent
    v-model="showContactDetailsDialog"
    :contact="editedItem.contact"
  />

  <!-- Create Contact Dialog -->
  <q-dialog v-model="showCreateContactDialog" persistent>
    <q-card style="min-width: 500px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="contact_page" color="black" size="md" class="q-mr-sm"/>
          Створити контакт
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="createContact">
        <q-card-section
          style="max-height: 70vh"
          class="scroll q-pt-md row q-col-gutter-sm"
        >
          <q-input
            class="col-12"
            outlined
            v-model="newContact.name"
            autofocus
            label="Ім'я"
            lazy-rules
            :rules="[
              (val) => (val !== null && val !== '') || 'Введіть ім\'я',
              (val) => val.length <= 255 || 'Не більше 255 символів',
            ]"
          />

          <q-input
            outlined
            class="col-6"
            v-model="newContact.phone"
            label="Телефон: 380..."
            mask="+###############"
            unmasked-value
            type="tel"
            lazy-rules
            :rules="[
              (val) => !val || phoneValidationRule(val)
            ]"
          />

          <q-input
            outlined
            class="col-6"
            v-model="newContact.email"
            label="Email"
            type="email"
            lazy-rules
            :rules="[
              (val) => !val || val.length <= 150 || 'Не більше 150 символів',
              (val) => !val || /.+@.+\\..+/.test(val) || 'Введіть коректний email',
            ]"
          />

          <q-input
            outlined
            class="col-12"
            v-model="newContact.address"
            label="Адреса"
            type="text"
            lazy-rules
            :rules="[
              (val) => !val || val.length <= 255 || 'Не більше 255 символів',
            ]"
          />

          <q-input
            outlined
            class="col-12"
            v-model="newContact.additional_info"
            label="Додаткова інформація"
            type="textarea"
            autogrow
            lazy-rules
            :rules="[
              (val) => !val || val.length <= 255 || 'Не більше 255 символів',
            ]"
          />

          <div class="col-12">
            <div class="text-subtitle2 q-mb-sm">Бажані платформи зв'язку</div>
            <q-field
              stack-label
              :rules="[
                (val) => (Array.isArray(val) && val.length > 0) || 'Виберіть хоча б одну платформу',
              ]"
              :model-value="newContact.preferred_platforms"
              borderless
            >
              <template v-slot:control>
                <div class="row q-gutter-sm">
                  <q-checkbox v-model="newContact.preferred_platforms" val="call" label="Дзвінок" color="primary"/>
                  <q-checkbox v-model="newContact.preferred_platforms" val="sms" label="SMS" color="primary"/>
                  <q-checkbox v-model="newContact.preferred_platforms" val="email" label="Email" color="primary"/>
                  <q-checkbox v-model="newContact.preferred_platforms" val="telegram" label="Telegram" color="blue"/>
                  <q-checkbox v-model="newContact.preferred_platforms" val="viber" label="Viber" color="purple"/>
                  <q-checkbox v-model="newContact.preferred_platforms" val="whatsapp" label="Whatsapp" color="green"/>
                  <q-checkbox v-model="newContact.preferred_platforms" val="instagram" label="Instagram" color="pink"/>
                  <q-checkbox v-model="newContact.preferred_platforms" val="other" label="Інша" color="cyan"/>
                </div>
              </template>
            </q-field>
          </div>
        </q-card-section>

        <q-separator/>

        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn
            flat
            color="primary"
            type="submit"
          ><b>Створити</b></q-btn>
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<style scoped>
.warehouse-wrapper {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 4px;
  padding: 16px 16px 5px;
  background-color: #fafafa;
}

.favorite-warehouse-button {
  border-radius: 5px;
  border: 1px solid rgb(0, 0, 0, 0.18);
  cursor: pointer;
  transition: all 0.2s ease-in-out;
}

.favorite-warehouse-button:hover {
  background-color: #b53cda;
  color: white;
}

.favorite-warehouse-button-active {
  background-color: #b53cda;
  color: white;
}

.readonly-section {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 4px;
  padding: 16px 16px 6px;
  background-color: #f5f5f5;
}

.section-header {
  margin-bottom: 12px;
}

/* Make all inputs white in warehouse section */
.warehouse-wrapper :deep(.q-field__control) {
  background-color: white;
}

.contact-preview {
  background-color: white;
  border: 1px solid #d0d0d0;
  border-radius: 6px;
  padding: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.contact-preview-header {
  display: flex;
  align-items: center;
  gap: 8px;
}

.contact-preview-info {
  flex: 1;
}

.contact-name {
  font-size: 1.1em;
  font-weight: 500;
  color: #333;
}

.contact-details {
  font-size: 0.9em;
  color: #666;
  margin-top: 4px;
}
</style>
