<script setup>
import {reactive, ref, computed, onMounted, watch, nextTick} from "vue";
import {useOrderStore} from "stores/orderStore";
import {useContactStore} from "stores/contactStore";
import {useWarehouseStore} from "stores/warehouseStore";
import {useCityStore} from "src/stores/helpers/cityStore";
import {useCountryStore} from "src/stores/helpers/countryStore";
import {useAppConfigStore} from "src/stores/appConfigStore";
import {api} from "src/boot/axios";
import parsePhoneNumberFromString from "libphonenumber-js";
import { getServerTime } from "app/helpers/GeneralPurposeFunctions";
import OrderItemsListComponent from "./OrderItemsListComponent.vue";
import OrderServicesListComponent from "./OrderServicesListComponent.vue";
import ContactDetailsComponent from "../contact/ContactDetailsComponent.vue";
import DateTimeInputComponent from "components/input/DateTimeInputComponent.vue";

const sectionStore = useOrderStore();
const contactStore = useContactStore();
const warehouseStore = useWarehouseStore();
const countryStore = useCountryStore();
const cityStore = useCityStore();
const appConfigStore = useAppConfigStore();

const contactSelectRef = ref(null);
const contactsOptions = ref([]);
const tempContactHolder = ref(null);
const selectedContactDetails = ref(null);
const loadingContactState = ref(false);
const showCreateContactDialog = ref(false);
const showContactDetailsDialog = ref(false);
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

const newContact = reactive({
  name: "",
  phone: "",
  email: "",
  address: "",
  preferred_platforms: [],
  additional_info: "",
});

function showCreateDialog() {
  newItem.status = "pending";
  newItem.currency = "UAH";
  newItem.discount = 0;
  newItem.warehouse_id = null;
  newItem.contact_id = null;
  newItem.order_items = [];
  newItem.order_services = [];
  newItem.amount_of_advance_payment_on_card = 0;
  newItem.amount_of_advance_payment_via_terminal = 0;
  newItem.amount_of_advance_payment_as_cash = 0;
  newItem.amount_of_final_payment_on_card = 0;
  newItem.amount_of_final_payment_via_terminal = 0;
  newItem.amount_of_final_payment_as_cash = 0;
  newItem.completion_deadline = null;
  newItem.fully_payed_at = null;
  newItem.notes = "";

  // Reset warehouse selection
  warehouseSelection.country = null;
  warehouseSelection.city = null;
  warehouseSelection.warehouse = null;

  // Reset contact selection
  tempContactHolder.value = null;
  selectedContactDetails.value = null;
  contactsOptions.value = [];

  // Reset validation error
  itemsServicesValidationError.value = '';

  warehouseStore.getFavoriteWarehouses();

  sectionStore.dialogs.create.isShown = true;
}

let newItem = reactive({
  status: "pending",
  currency: "UAH",
  discount: 0,
  warehouse_id: null,
  contact_id: null,
  order_items: [],
  order_services: [],
  amount_of_advance_payment_on_card: 0,
  amount_of_advance_payment_via_terminal: 0,
  amount_of_advance_payment_as_cash: 0,
  amount_of_final_payment_on_card: 0,
  amount_of_final_payment_via_terminal: 0,
  amount_of_final_payment_as_cash: 0,
  completion_deadline: null,
  fully_payed_at: null,
  notes: "",
});

const totalPrice = computed(() => {
  let total = 0;

  // Calculate items total
  newItem.order_items.forEach(item => {
    if (item.item_id && item.price_per_one_unit && item.quantity) {
      total += item.price_per_one_unit * item.quantity;
    }
  });

  // Calculate services total
  newItem.order_services.forEach(service => {
    if (service.service_id && service.price_per_one_unit && service.quantity) {
      total += service.price_per_one_unit * service.quantity;
    }
  });

  // Apply discount
  total -= newItem.discount || 0;

  return Math.max(0, total).toFixed(2);
});

// Автоматично очищати помилку валідації при додаванні товарів/послуг
watch([() => newItem.order_items.length, () => newItem.order_services.length], () => {
  if (itemsServicesValidationError.value && (newItem.order_items.length > 0 || newItem.order_services.length > 0)) {
    itemsServicesValidationError.value = '';
  }
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
    newItem.contact_id = contact.id;
  }
  tempContactHolder.value = null;

  // Trigger validation to update field status
  if (contactSelectRef.value) {
    contactSelectRef.value.validate();
  }
}

function clearContact() {
  selectedContactDetails.value = null;
  newItem.contact_id = null;

  // Trigger validation to update field status
  if (contactSelectRef.value) {
    contactSelectRef.value.validate();
  }
}

function fillWarehouseFromFavorite(index) {
  warehouseSelection.country = warehouseStore.favoriteWarehouses[index].country;
  warehouseSelection.city = warehouseStore.favoriteWarehouses[index].city;
  warehouseSelection.warehouse = warehouseStore.favoriteWarehouses[index].warehouse;
  newItem.warehouse_id = warehouseStore.favoriteWarehouses[index].warehouse.id;
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
  newItem.warehouse_id = null;
}

function cityUpdate() {
  warehouseSelection.warehouse = null;
  newItem.warehouse_id = null;
}

function warehouseUpdate() {
  if (warehouseSelection.warehouse && warehouseSelection.warehouse.id) {
    newItem.warehouse_id = warehouseSelection.warehouse.id;
  } else {
    newItem.warehouse_id = null;
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
      // Close dialog
      showCreateContactDialog.value = false;

      // Select the newly created contact
      selectedContactDetails.value = res.data.contact;
      newItem.contact_id = res.data.contact.id;

      // Trigger validation to update field status
      if (contactSelectRef.value) {
        contactSelectRef.value.validate();
      }

      // Reset the contact form
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
  if (newItem.order_items.length === 0 && newItem.order_services.length === 0) {
    itemsServicesValidationError.value = "Додайте хоча б один товар або послугу до замовлення";

    // Прокрутити до помилки після рендерингу
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

  itemsServicesValidationError.value = ''; // Очистити помилку перед відправкою

  const payload = {
    ...newItem,
    total_price: parseFloat(totalPrice.value),
    // Remove _uid from items and services before sending to backend
    order_items: newItem.order_items.map(({_uid, ...item}) => item),
    order_services: newItem.order_services.map(({_uid, ...service}) => service),
    // Convert dates to proper format for backend (with timezone conversion)
    completion_deadline: newItem.completion_deadline
      ? getServerTime(newItem.completion_deadline, 'ua', true, true)
      : null,
    fully_payed_at: newItem.fully_payed_at
      ? getServerTime(newItem.fully_payed_at, 'ua', true, true)
      : null,
  };
  sectionStore.create(payload);
}
</script>

<template>
  <q-btn flat round color="black" icon="add" @click="showCreateDialog">
    <q-tooltip anchor="bottom left" :offset="[-20, 7]" class="bg-black text-body2">
      Створити замовлення
    </q-tooltip>
  </q-btn>

  <q-dialog v-model="sectionStore.dialogs.create.isShown" persistent>
    <q-card style="min-width: 700px; max-width: 1100px;">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="receipt_long" color="black" size="md" class="q-mr-sm"/>
          Нове замовлення
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="submitOrder">
        <div class="q-mt-md"></div>
        <q-card-section
          style="max-height: 70vh"
          class="scroll q-pt-none row q-col-gutter-md"
        >
          <!-- Contact Section -->
          <div class="col-6">
            <div class="contact-wrapper">
              <div class="section-header q-mb-sm">
                <div class="text-subtitle1">Контакт</div>
              </div>
              <!-- Contact Search -->
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
                <div
                  class="col-2"
                >
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
          </div>

          <!-- Warehouse Selection -->
          <div class="col-6">
            <div class="warehouse-wrapper">
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
          </div>

          <!-- Items Section -->
          <div class="col-6">
            <OrderItemsListComponent
              class="full-width"
              v-model="newItem.order_items"
              :warehouse-id="newItem.warehouse_id"
              :currency="newItem.currency"
              :order-status="newItem.status"
            />
          </div>

          <!-- Services Section -->
          <div class="col-6">
            <OrderServicesListComponent
              class="full-width"
              v-model="newItem.order_services"
              :currency="newItem.currency"
            />
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

          <div class="col-12 text-subtitle1">Вартість</div>
          <!-- Discount and Total -->
          <div class="col-6">
            <q-input
              outlined
              v-model.number="newItem.discount"
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
            v-model.number="newItem.amount_of_advance_payment_on_card"
            label="Карткою онлайн"
            type="number"
            step="0.01"
            class="col-4"
            :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
          />
          <q-input
            outlined
            v-model.number="newItem.amount_of_advance_payment_via_terminal"
            label="Терміналом"
            type="number"
            step="0.01"
            class="col-4"
            :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
          />
          <q-input
            outlined
            v-model.number="newItem.amount_of_advance_payment_as_cash"
            label="Готівкою"
            type="number"
            step="0.01"
            class="col-4"
            :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
          />

          <div class="col-12 text-subtitle1">Фінальна оплата</div>
          <q-input
            outlined
            v-model.number="newItem.amount_of_final_payment_on_card"
            label="Карткою онлайн"
            type="number"
            step="0.01"
            class="col-4"
            :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
          />
          <q-input
            outlined
            v-model.number="newItem.amount_of_final_payment_via_terminal"
            label="Терміналом"
            type="number"
            step="0.01"
            class="col-4"
            :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
          />
          <q-input
            outlined
            v-model.number="newItem.amount_of_final_payment_as_cash"
            label="Готівкою"
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
            v-model="newItem.completion_deadline"
          />
          <DateTimeInputComponent
            label="Дата повної оплати"
            class="col-4"
            v-model="newItem.fully_payed_at"
          />

          <!-- Notes -->
          <div class="col-12 text-subtitle1 q-mt-md">Нотатки</div>
          <q-input
            outlined
            v-model="newItem.notes"
            type="textarea"
            autogrow
            class="col-12"
            :rules="[
              (val) => !val || val.length <= 5000 || 'Не більше 5000 символів',
            ]"
            counter
            :maxlength="5000"
          />
        </q-card-section>
        <q-separator/>

        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn
            flat
            color="primary"
            type="submit"
            :loading="sectionStore.dialogs.create.isLoading"
          ><b>Створити</b></q-btn>
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

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
              (val) => (val !== null && val !== '') || 'Вкажіть телефон',
              (val) => phoneValidationRule(val)
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
              (val) => !val || /.+@.+\..+/.test(val) || 'Введіть коректний email',
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

  <!-- Contact Details Dialog -->
  <ContactDetailsComponent
    v-model="showContactDetailsDialog"
    :contact="selectedContactDetails"
  />

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

.contact-wrapper {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 4px;
  padding: 16px 16px 6px;
  background-color: #fafafa;
}

.section-header {
  margin-bottom: 12px;
}

/* Make all inputs white in contact and warehouse sections */
.contact-wrapper :deep(.q-field__control),
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

.contact-option-name {
  font-weight: 500;
}
</style>
