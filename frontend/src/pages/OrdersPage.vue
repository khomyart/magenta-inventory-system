<template>
  <div class="page">
    <div class="toolbar row q-px-md q-mt-md">
      <q-input
        v-model="
          appStore.filters.data[currentSection].selectedParams.status.value
        "
        debounce="700"
        outlined
        placeholder="Введіть статус замовлення"
        dense
        class="q-mr-md"
        style="width: 300px"
        :loading="sectionStore.data.isItemsLoading"
      >
        <template v-slot:append v-if="!sectionStore.data.isItemsLoading">
          <q-icon name="search" />
        </template>
      </q-input>
      <CreateOrderComponent v-if="allowenses.create" />
      <q-btn
        flat
        round
        color="black"
        icon="sync"
        @click="sectionStore.receive()"
      >
        <q-tooltip
          anchor="bottom left"
          :offset="[-20, 7]"
          class="bg-black text-body2"
        >
          Оновити список
        </q-tooltip>
      </q-btn>
    </div>

    <div class="content">
      <q-inner-loading :showing="sectionStore.data.isItemsLoading">
        <q-spinner-puff size="50px" color="primary" />
      </q-inner-loading>
      <q-toolbar class="text-black filter q-px-none q-py-md bg-white">
        <SortingComponent
          :filterIn="currentSection"
          :sectionStore="sectionStore"
        />
        <div class="filter-separator">
          <div class="vertical-line"></div>
        </div>
        <template v-for="(item, index) in fieldsSequance" :key="index">
          <ButtonComponent
            v-if="fieldsDetails[index].type !== 'display-only'"
            :appStore="appStore"
            :sectionName="currentSection"
            :sectionStore="sectionStore"
            :name="fieldsSequance[index]"
            :targetFields="
              fieldsDetails[index].additionalFieldsForFiltering ?? []
            "
            :label="fieldsDetails[index].label"
            :searchBarLabel="fieldsDetails[index].searchBarLabel"
            :orderButtonLabels="fieldsDetails[index].orderButtonLabels"
            :width="computedFilterWidth.buttons[fieldsSequance[index]]"
            :mode="fieldsDetails[index].type"
            @clear-filter="clearFilter"
            @change-filter-mode="onChangedFieldFilterMode"
            @set-filter-order="setFilterOrder"
          />
          <div v-else class="filter-button" :style="{ width: computedFilterWidth.buttons[fieldsSequance[index]] + 'px' }">
            <div class="text-center text-grey-7" style="padding: 8px;">{{ fieldsDetails[index].label }}</div>
          </div>
        </template>
      </q-toolbar>
      <table class="items">
        <tr>
          <td :width="60"></td>
          <td :width="computedFilterWidth.fields.separator"></td>
          <template v-for="(item, index) in fieldsSequance" :key="index">
            <td
              v-if="index != fieldsSequance.length - 1"
              :width="computedFilterWidth.fields[fieldsSequance[index]]"
            ></td>
            <td
              v-if="index != fieldsSequance.length - 1"
              :width="computedFilterWidth.fields.separator"
            ></td>
            <td
              v-if="index == fieldsSequance.length - 1"
              :width="
                computedFilterWidth.fields[fieldsSequance[index]] +
                computedFilterWidth.fields.lastSeparator
              "
            ></td>
          </template>
        </tr>
        <template v-for="(item, index) in sectionStore.items" :key="index">
          <OrderComponent
            @show-remove-dialog="showRemoveDialog"
            @show-edit-dialog="showUpdateDialog"
            @show-cancel-dialog="showCancelDialog"
            @show-confirm-dialog="showConfirmDialog"
            @show-start-work-dialog="showStartWorkDialog"
            @show-complete-dialog="showCompleteDialog"
            @show-payment-dialog="showPaymentDialog"
            @clear-updated-item-id="clearUpdatedItemId"
            @copy-value="copyValue"
            :allowenses="allowenses"
            :itemInfo="item"
            :gap="appStore.other.visualTheme.gapsBetweenItems[currentSection]"
            :updated="item.id == sectionStore.data.updatedItemId"
            :isFirst="index == 0"
            :isLast="index == sectionStore.items.length - 1"
            :itemsBorderRadius="
              appStore.other.visualTheme.itemsBorderRadius[currentSection]
            "
          />
        </template>
      </table>
    </div>

    <div class="footer">
      <div class="footer-left-part flex items-center">
        <span class="q-mr-sm">Записів на сторінці</span>
        <q-select
          class="item-per-page-selector"
          outlined
          dense
          v-model="appStore.amountOfItemsPerPages[currentSection]"
          :options="appStore.availableAmaountOfItemsPerPage"
          options-dense
        />
        <q-separator vertical class="q-mx-md"></q-separator>
        <q-pagination
          v-model="appStore.currentPages[currentSection]"
          color="purple"
          :max="sectionStore.data.lastPage"
          :max-pages="6"
          boundary-numbers
        />
      </div>
      <div class="footer-right-part q-mr-md">
        Кількість: {{ sectionStore.data.amountOfItems }}
      </div>
    </div>

    <q-dialog v-model="sectionStore.dialogs.delete.isShown">
      <q-card>
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon name="warning" color="red" size="md" class="q-mr-sm" />
            Видалення
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-card-section style="width: 350px">
          Ви справді бажаєте видалити замовлення #{{ deletedItem.id }}?
        </q-card-section>
        <q-separator></q-separator>
        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn
            @click="sectionStore.delete(deletedItem.id)"
            flat
            type="submit"
            color="negative"
            :loading="sectionStore.dialogs.delete.isLoading"
          ><b>Так</b></q-btn
          >
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="sectionStore.dialogs.cancel.isShown">
      <q-card>
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon name="cancel" color="orange" size="md" class="q-mr-sm" />
            Відміна замовлення
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-card-section style="width: 400px">
          <div>
            Ви справді бажаєте відмінити замовлення #{{ cancelledItem.id }}?
          </div>
          <q-checkbox
            v-if="['in_progress', 'completed'].includes(cancelledItem.status)"
            v-model="cancelledItem.returnItems"
            label="Повернути товари до складу"
            class="q-mt-md"
            color="primary"
          />
        </q-card-section>
        <q-separator></q-separator>
        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Скасувати</b></q-btn>
          <q-btn
            @click="sectionStore.cancel(cancelledItem.id, cancelledItem.returnItems)"
            flat
            type="submit"
            color="orange"
            :loading="sectionStore.dialogs.cancel.isLoading"
          ><b>Так</b></q-btn
          >
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="sectionStore.dialogs.confirm.isShown">
      <q-card>
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon name="task_alt" color="primary" size="md" class="q-mr-sm" />
            Підтвердження замовлення
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-card-section style="width: 450px">
          <div class="q-mb-md">
            Ви бажаєте підтвердити замовлення #{{ confirmedItem.id }}?
          </div>
          <q-checkbox
            v-model="confirmedItem.enterAdvance"
            label="Внести аванс"
            color="primary"
            :class="{'q-mb-md': confirmedItem.enterAdvance, 'q-mb-none': !confirmedItem.enterAdvance}"
          />
          <div v-if="confirmedItem.enterAdvance" class="q-gutter-xs">
            <q-input
              outlined
              v-model.number="confirmedItem.amount_of_advance_payment_on_card"
              label="Карткою онлайн"
              type="number"
              step="0.01"
              min="0"
              :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
            />
            <q-input
              outlined
              v-model.number="confirmedItem.amount_of_advance_payment_via_terminal"
              label="Терміналом"
              type="number"
              step="0.01"
              min="0"
              :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
            />
            <q-input
              outlined
              v-model.number="confirmedItem.amount_of_advance_payment_as_cash"
              label="Готівкою"
              type="number"
              step="0.01"
              min="0"
              :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
            />
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Скасувати</b></q-btn>
          <q-btn
            @click="sectionStore.confirm(
              confirmedItem.id,
              confirmedItem.enterAdvance ? {
                amount_of_advance_payment_on_card: confirmedItem.amount_of_advance_payment_on_card,
                amount_of_advance_payment_via_terminal: confirmedItem.amount_of_advance_payment_via_terminal,
                amount_of_advance_payment_as_cash: confirmedItem.amount_of_advance_payment_as_cash
              } : null
            )"
            flat
            type="submit"
            color="primary"
            :loading="sectionStore.dialogs.confirm.isLoading"
          ><b>Підтвердити</b></q-btn
          >
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="sectionStore.dialogs.startWork.isShown">
      <q-card>
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon name="play_arrow" color="blue" size="md" class="q-mr-sm" />
            Прийняти в роботу
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-card-section style="width: 400px">
          Ви справді бажаєте прийняти замовлення #{{ startWorkItem.id }} в роботу?
        </q-card-section>
        <q-separator></q-separator>
        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Скасувати</b></q-btn>
          <q-btn
            @click="sectionStore.startWork(startWorkItem.id)"
            flat
            type="submit"
            color="blue"
            :loading="sectionStore.dialogs.startWork.isLoading"
          ><b>Так</b></q-btn
          >
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="sectionStore.dialogs.complete.isShown">
      <q-card style="min-width: 500px;">
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon name="check_circle" color="green" size="md" class="q-mr-sm" />
            Виконання замовлення
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-card-section>
          <div class="text-subtitle1 q-pb-md">Залученість у виконання завдання</div>
          <div class="q-gutter-md">
            <q-select
              outlined
              v-model="completeItem.involvement_level_1_user_id"
              :options="userStore.users"
              option-value="id"
              option-label="name"
              emit-value
              map-options
              clearable
              hide-dropdown-icon
              use-input
              @filter="userFilterLevel1"
              label="Повна (8%)"
              :loading="userLoadingStates.level1"
              :disable="completeItem.involvement_level_2_user_id !== null || completeItem.involvement_level_3_user_id !== null"
            >
              <template v-slot:append v-if="!userLoadingStates.level1 && !completeItem.involvement_level_1_user_id">
                <q-icon name="search" />
              </template>
            </q-select>
            <q-select
              outlined
              v-model="completeItem.involvement_level_2_user_id"
              :options="userStore.users.filter(u => u.id !== completeItem.involvement_level_1_user_id && u.id !== completeItem.involvement_level_3_user_id)"
              option-value="id"
              option-label="name"
              emit-value
              map-options
              clearable
              hide-dropdown-icon
              use-input
              @filter="userFilterLevel2"
              label="Часткова (5%)"
              :loading="userLoadingStates.level2"
              :disable="completeItem.involvement_level_1_user_id !== null"
            >
              <template v-slot:append v-if="!userLoadingStates.level2 && !completeItem.involvement_level_2_user_id">
                <q-icon name="search" />
              </template>
            </q-select>
            <q-select
              outlined
              v-model="completeItem.involvement_level_3_user_id"
              :options="userStore.users.filter(u => u.id !== completeItem.involvement_level_1_user_id && u.id !== completeItem.involvement_level_2_user_id)"
              option-value="id"
              option-label="name"
              emit-value
              map-options
              clearable
              hide-dropdown-icon
              use-input
              @filter="userFilterLevel3"
              label="Дотична (3%)"
              :loading="userLoadingStates.level3"
              :disable="completeItem.involvement_level_1_user_id !== null"
            >
              <template v-slot:append v-if="!userLoadingStates.level3 && !completeItem.involvement_level_3_user_id">
                <q-icon name="search" />
              </template>
            </q-select>
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Скасувати</b></q-btn>
          <q-btn
            @click="sectionStore.complete(completeItem.id, {
              involvement_level_1_user_id: completeItem.involvement_level_1_user_id,
              involvement_level_2_user_id: completeItem.involvement_level_2_user_id,
              involvement_level_3_user_id: completeItem.involvement_level_3_user_id,
            })"
            flat
            type="submit"
            color="green"
            :loading="sectionStore.dialogs.complete.isLoading"
          ><b>Виконати</b></q-btn
          >
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="sectionStore.dialogs.payment.isShown">
      <q-card>
        <q-card-section>
          <div class="text-h6 flex items-center">
            <q-icon name="payments" color="purple" size="md" class="q-mr-sm" />
            Внести оплату
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-card-section style="width: 500px">
          <div class="q-mb-md text-h6 text-primary">
            Залишилося сплатити: {{ remainingAmount }}
          </div>

          <!-- Поточні внесені суми -->
          <div v-if="paymentItem.amount_of_final_payment_on_card > 0 || paymentItem.amount_of_final_payment_via_terminal > 0 || paymentItem.amount_of_final_payment_as_cash > 0" class="q-mb-md q-pa-md bg-grey-2" style="border-radius: 8px;">
            <div class="text-subtitle1 q-mb-sm">Вже внесено:</div>
            <div v-if="paymentItem.amount_of_final_payment_on_card > 0" class="text-body2">
              Карткою онлайн: <span class="text-bold">{{ paymentItem.amount_of_final_payment_on_card.toFixed(2) }}</span>
            </div>
            <div v-if="paymentItem.amount_of_final_payment_via_terminal > 0" class="text-body2">
              Терміналом: <span class="text-bold">{{ paymentItem.amount_of_final_payment_via_terminal.toFixed(2) }}</span>
            </div>
            <div v-if="paymentItem.amount_of_final_payment_as_cash > 0" class="text-body2">
              Готівкою: <span class="text-bold">{{ paymentItem.amount_of_final_payment_as_cash.toFixed(2) }}</span>
            </div>
          </div>

          <!-- Поля для нової оплати -->
          <div class="text-subtitle1 q-pb-md">Додати оплату:</div>
          <div class="q-gutter-sm">
            <q-input
              outlined
              v-model.number="paymentItem.amount_of_final_payment_on_card_new"
              label="Карткою онлайн"
              type="number"
              step="0.01"
              min="0"
              :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
            >
              <template v-slot:append>
                <q-btn
                  dense
                  flat
                  color="primary"
                  label="Повна сума"
                  size="sm"
                  @click="fillRemainingAmount('card')"
                  :disable="parseFloat(remainingAmount) <= 0"
                />
              </template>
            </q-input>
            <q-input
              outlined
              v-model.number="paymentItem.amount_of_final_payment_via_terminal_new"
              label="Терміналом"
              type="number"
              step="0.01"
              min="0"
              :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
            >
              <template v-slot:append>
                <q-btn
                  dense
                  flat
                  color="primary"
                  label="Повна сума"
                  size="sm"
                  @click="fillRemainingAmount('terminal')"
                  :disable="parseFloat(remainingAmount) <= 0"
                />
              </template>
            </q-input>
            <q-input
              outlined
              v-model.number="paymentItem.amount_of_final_payment_as_cash_new"
              label="Готівкою"
              type="number"
              step="0.01"
              min="0"
              :rules="[(val) => (val !== null && val !== undefined && val !== '') || 'Поле обов\'язкове', (val) => val >= 0 || 'Не менше 0']"
            >
              <template v-slot:append>
                <q-btn
                  dense
                  flat
                  color="primary"
                  label="Повна сума"
                  size="sm"
                  @click="fillRemainingAmount('cash')"
                  :disable="parseFloat(remainingAmount) <= 0"
                />
              </template>
            </q-input>
          </div>
        </q-card-section>
        <q-separator></q-separator>
        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Скасувати</b></q-btn>
          <q-btn
            @click="sectionStore.payment(
              paymentItem.id,
              {
                amount_of_final_payment_on_card: paymentItem.amount_of_final_payment_on_card_new,
                amount_of_final_payment_via_terminal: paymentItem.amount_of_final_payment_via_terminal_new,
                amount_of_final_payment_as_cash: paymentItem.amount_of_final_payment_as_cash_new
              }
            )"
            flat
            type="submit"
            color="purple"
            :loading="sectionStore.dialogs.payment.isLoading"
          ><b>Внести оплату</b></q-btn
          >
        </q-card-actions>
      </q-card>
    </q-dialog>

    <EditOrderComponent :order-data="editedOrder" />
  </div>
</template>

<script setup>
import { reactive, onMounted, watch, computed, ref } from "vue";
import { useRouter } from "vue-router";
import { useAppConfigStore } from "src/stores/appConfigStore";
import { useOrderStore } from "stores/orderStore";
import { useUserStore } from "stores/userStore";
import { useQuasar } from "quasar";
import CreateOrderComponent from "components/order/CreateOrderComponent.vue";
import EditOrderComponent from "components/order/EditOrderComponent.vue";
import OrderComponent from "components/order/OrderComponent.vue";
import SortingComponent from "src/components/filter_bar/SortingComponent.vue";
import ButtonComponent from "src/components/filter_bar/ButtonComponent.vue";

const currentSection = "orders";
const appStore = useAppConfigStore();
const sectionStore = useOrderStore();
const userStore = useUserStore();
const router = useRouter();
const $q = useQuasar();

const fieldsSequance = ["status", "total_price", "contact_id", "completion_deadline", "created_at", "involvement", "notes"];
const fieldsDetails = [
  { label: "Статус", searchBarLabel: "Статус", type: "universal", orderButtonLabels: { up: "Від A до Я", down: "Від Я до А" }},
  { label: "Сума", searchBarLabel: "Загальна сума", type: "number", orderButtonLabels: { up: "Від меншої", down: "Від більшої" }},
  { label: "Контакт ID", searchBarLabel: "ID контакту", type: "number", orderButtonLabels: { up: "Від меншого", down: "Від більшого" }},
  { label: "Дедлайн", searchBarLabel: "Дедлайн виконання", type: "universal", orderButtonLabels: { up: "Від ранніх", down: "Від пізніх" }},
  { label: "Створено", searchBarLabel: "Дата створення", type: "universal", orderButtonLabels: { up: "Від ранніх", down: "Від пізніх" }},
  { label: "Залучені", searchBarLabel: "Залучені", type: "display-only", orderButtonLabels: { up: "Від A до Я", down: "Від Я до А" }},
  { label: "Нотатки", searchBarLabel: "Нотатки", type: "universal", orderButtonLabels: { up: "Від A до Я", down: "Від Я до А" }},
];

const allowenses = {
  create: appStore.allowenses.isValidFor("create", currentSection),
  update: appStore.allowenses.isValidFor("update", currentSection),
  delete: appStore.allowenses.isValidFor("delete", currentSection),
};

let deletedItem = reactive({ id: "" });
let cancelledItem = reactive({ id: "", status: "", returnItems: false });
let confirmedItem = reactive({
  id: "",
  enterAdvance: true,
  amount_of_advance_payment_on_card: 0,
  amount_of_advance_payment_via_terminal: 0,
  amount_of_advance_payment_as_cash: 0
});
let startWorkItem = reactive({ id: "" });
let completeItem = reactive({
  id: "",
  involvement_level_1_user_id: null,
  involvement_level_2_user_id: null,
  involvement_level_3_user_id: null,
});
let userLoadingStates = reactive({
  level1: false,
  level2: false,
  level3: false,
});
let paymentItem = reactive({
  id: "",
  total_price: 0,
  amount_of_advance_payment_on_card: 0,
  amount_of_advance_payment_via_terminal: 0,
  amount_of_advance_payment_as_cash: 0,
  amount_of_final_payment_on_card: 0,
  amount_of_final_payment_via_terminal: 0,
  amount_of_final_payment_as_cash: 0,
  amount_of_final_payment_on_card_new: 0,
  amount_of_final_payment_via_terminal_new: 0,
  amount_of_final_payment_as_cash_new: 0
});
let editedOrder = ref(null);

let tempFieldWidths = reactive({
  status: 0,
  total_price: 0,
  contact_id: 0,
  completion_deadline: 0,
  created_at: 0,
  involvement: 0,
  notes: 0,
});

function clearUpdatedItemId() { setTimeout(() => { sectionStore.data.updatedItemId = 0; }, 2000); }
function copyValue(value, paramName) { navigator.clipboard.writeText(value); $q.notify({ position: "top", color: "primary", message: `${paramName} зкопійовано: "${value}"`, group: false }); }
function showUpdateDialog(item) {
  editedOrder.value = item;
  sectionStore.dialogs.update.isShown = true;
}
function showRemoveDialog(id) { deletedItem.id = id; sectionStore.dialogs.delete.isShown = true; }
function showCancelDialog(item) {
  cancelledItem.id = item.id;
  cancelledItem.status = item.status;
  cancelledItem.returnItems = false;
  sectionStore.dialogs.cancel.isShown = true;
}
function showConfirmDialog(item) {
  confirmedItem.id = item.id;
  confirmedItem.enterAdvance = true;
  confirmedItem.amount_of_advance_payment_on_card = 0;
  confirmedItem.amount_of_advance_payment_via_terminal = 0;
  confirmedItem.amount_of_advance_payment_as_cash = 0;
  sectionStore.dialogs.confirm.isShown = true;
}
function showStartWorkDialog(item) {
  startWorkItem.id = item.id;
  sectionStore.dialogs.startWork.isShown = true;
}
function showCompleteDialog(item) {
  completeItem.id = item.id;
  completeItem.involvement_level_1_user_id = null;
  completeItem.involvement_level_2_user_id = null;
  completeItem.involvement_level_3_user_id = null;
  userStore.fetchUsers();
  sectionStore.dialogs.complete.isShown = true;
}
function showPaymentDialog(item) {
  paymentItem.id = item.id;
  paymentItem.total_price = item.total_price;
  paymentItem.amount_of_advance_payment_on_card = item.amount_of_advance_payment_on_card || 0;
  paymentItem.amount_of_advance_payment_via_terminal = item.amount_of_advance_payment_via_terminal || 0;
  paymentItem.amount_of_advance_payment_as_cash = item.amount_of_advance_payment_as_cash || 0;
  paymentItem.amount_of_final_payment_on_card = item.amount_of_final_payment_on_card || 0;
  paymentItem.amount_of_final_payment_via_terminal = item.amount_of_final_payment_via_terminal || 0;
  paymentItem.amount_of_final_payment_as_cash = item.amount_of_final_payment_as_cash || 0;
  paymentItem.amount_of_final_payment_on_card_new = 0;
  paymentItem.amount_of_final_payment_via_terminal_new = 0;
  paymentItem.amount_of_final_payment_as_cash_new = 0;
  sectionStore.dialogs.payment.isShown = true;
}
function fillRemainingAmount(paymentType) {
  // Calculate current remaining amount
  const totalPaid =
    (paymentItem.amount_of_advance_payment_on_card || 0) +
    (paymentItem.amount_of_advance_payment_via_terminal || 0) +
    (paymentItem.amount_of_advance_payment_as_cash || 0) +
    (paymentItem.amount_of_final_payment_on_card || 0) +
    (paymentItem.amount_of_final_payment_via_terminal || 0) +
    (paymentItem.amount_of_final_payment_as_cash || 0);

  const remaining = Math.max(0, paymentItem.total_price - totalPaid);

  // Reset all new payment fields
  paymentItem.amount_of_final_payment_on_card_new = 0;
  paymentItem.amount_of_final_payment_via_terminal_new = 0;
  paymentItem.amount_of_final_payment_as_cash_new = 0;

  // Fill the selected payment field
  if (paymentType === 'card') {
    paymentItem.amount_of_final_payment_on_card_new = parseFloat(remaining.toFixed(2));
  } else if (paymentType === 'terminal') {
    paymentItem.amount_of_final_payment_via_terminal_new = parseFloat(remaining.toFixed(2));
  } else if (paymentType === 'cash') {
    paymentItem.amount_of_final_payment_as_cash_new = parseFloat(remaining.toFixed(2));
  }
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
function clearFilter(fields, filterType = "all") {
  let filters = appStore.filters;
  if (!Array.isArray(fields)) {
    fields = [fields];
  }

  fields.forEach((field, index) => {
    filters.data[currentSection].selectedParams[field].value = "";
    filters.data[currentSection].selectedParams[field].filterMode =
      filters.availableParams.items[0];

    if (filterType === "number") {
      filters.data[currentSection].selectedParams[field].filterMode =
        filters.availableParams.items[2];
    }

    if (filters.data[currentSection].selectedParams.order.field === field) {
      filters.data[currentSection].selectedParams.order.field = "";
      filters.data[currentSection].selectedParams.order.value = "";
      filters.data[currentSection].selectedParams.order.combined = "";
    }
  })
}
function onChangedFieldFilterMode(field) { if (appStore.filters.data[currentSection].selectedParams[field].value) sectionStore.receive(); }
function setFilterOrder(field, fieldOrder) {
  let order = appStore.filters.data[currentSection].selectedParams.order;
  if (order.field === field && order.value === fieldOrder) {
    order.field = ""; order.value = "";
  } else {
    order.field = field; order.value = fieldOrder;
  }
  order.combined = `${field}${fieldOrder}`;
}

const remainingAmount = computed(() => {
  const totalPaid =
    (paymentItem.amount_of_advance_payment_on_card || 0) +
    (paymentItem.amount_of_advance_payment_via_terminal || 0) +
    (paymentItem.amount_of_advance_payment_as_cash || 0) +
    (paymentItem.amount_of_final_payment_on_card || 0) +
    (paymentItem.amount_of_final_payment_via_terminal || 0) +
    (paymentItem.amount_of_final_payment_as_cash || 0);
  return Math.max(0, paymentItem.total_price - totalPaid).toFixed(2);
});

const computedFilterWidth = computed(() => {
  const dynamicWidths = appStore.filters.data[currentSection].width.dynamic;
  const padding = appStore.filters.availableParams.filterButtonXPadding;
  const separator = appStore.filters.availableParams.separatorWidth;
  return {
    buttons: {
      status: dynamicWidths.status - padding,
      total_price: dynamicWidths.total_price - padding,
      contact_id: dynamicWidths.contact_id - padding,
      completion_deadline: dynamicWidths.completion_deadline - padding,
      created_at: dynamicWidths.created_at - padding,
      notes: dynamicWidths.notes - padding,
    },
    fields: {
      status: dynamicWidths.status,
      total_price: dynamicWidths.total_price,
      contact_id: dynamicWidths.contact_id,
      completion_deadline: dynamicWidths.completion_deadline,
      created_at: dynamicWidths.created_at,
      notes: dynamicWidths.notes,
      separator: separator,
      lastSeparator: separator / 2 - 2,
    },
  };
});

watch(() => appStore.currentPages[currentSection], (page) => { router.push(`/${currentSection}/${page}`); sectionStore.receive(); });
watch(() => appStore.amountOfItemsPerPages[currentSection], () => { if (appStore.currentPages[currentSection] !== 1) appStore.currentPages[currentSection] = 1; else sectionStore.receive(); });
watch(() => [
  appStore.filters.data[currentSection].selectedParams.order.combined,
  appStore.filters.data[currentSection].selectedParams.status.value,
  appStore.filters.data[currentSection].selectedParams.total_price.value,
  appStore.filters.data[currentSection].selectedParams.contact_id.value,
  appStore.filters.data[currentSection].selectedParams.completion_deadline.value,
  appStore.filters.data[currentSection].selectedParams.created_at.value,
  appStore.filters.data[currentSection].selectedParams.notes.value,
], () => {
  sectionStore.receive();
});

onMounted(() => {
  if (appStore.errors.reauth.data.isLogoutThroughtLogoutMethod) {
    appStore.errors.reauth.data.isLogoutThroughtLogoutMethod = false;
    sectionStore.receive();
  }
  appStore.currentPages[currentSection] = Number(router.currentRoute.value.params.page);

  let contentElement = document.querySelector(".content");
  let contentPaddingX = 2 + parseFloat(getComputedStyle(contentElement).paddingLeft) + parseFloat(getComputedStyle(contentElement).paddingRight);

  for (const fieldName in appStore.filters.data[currentSection].width.dynamic) {
    if (appStore.filters.data[currentSection].width.dynamic[fieldName] < appStore.filters.availableParams.minFilterWidth) {
      appStore.filters.data[currentSection].width.dynamic[fieldName] = appStore.filters.data[currentSection].width.default[fieldName];
    }
  }

  let qApp = document.querySelector("#q-app");
  let pageContainer = document.querySelector(".content");

  function addEventToSeparator(separatorObject, fieldName, affectedFieldName = null) {
    function separatorMovementVisualisation() {
      let devider = document.createElement("div");
      devider.classList.add("filter-width-helper");
      devider.style.height = `${pageContainer.clientHeight}px`;
      pageContainer.appendChild(devider);
      devider.style.top = `${pageContainer.offsetTop}px`;
      devider.style.left = `${separatorObject.getBoundingClientRect().x - pageContainer.getBoundingClientRect().x + appStore.filters.availableParams.separatorWidth / 2}px`;
      return devider;
    }

    separatorObject.onmousedown = (mouseDownEvent) => {
      separatorObject.onmouseup = () => {
        onSeparatorRelease();
      };
      document.body.onmouseup = () => {
        onSeparatorRelease();
      };
      qApp.classList.add("disable-interaction");
      Object.keys(appStore.filters.data[currentSection].width.dynamic).forEach((field) => {
        tempFieldWidths[field] = appStore.filters.data[currentSection].width.dynamic[field];
      });

      let initCursorCoord = mouseDownEvent.pageX;
      let initFieldWidth = appStore.filters.data[currentSection].width.dynamic[fieldName];
      let initAffectedFieldWidth = affectedFieldName == null ? null : appStore.filters.data[currentSection].width.dynamic[affectedFieldName];
      let minFilterWidth = appStore.filters.availableParams.minFilterWidth;
      let devider = separatorMovementVisualisation();
      let initDeviderOffsetLeft = devider.offsetLeft;

      function onSeparatorRelease() {
        devider.remove();
        document.body.onmousemove = null;
        document.body.onmouseup = null;
        qApp.classList.remove("disable-interaction");

        if (affectedFieldName != null) {
          if (tempFieldWidths[fieldName] < minFilterWidth) {
            tempFieldWidths[fieldName] = minFilterWidth;
            tempFieldWidths[affectedFieldName] = initAffectedFieldWidth + (initFieldWidth - minFilterWidth);
          } else if (tempFieldWidths[affectedFieldName] < minFilterWidth) {
            tempFieldWidths[affectedFieldName] = minFilterWidth;
            tempFieldWidths[fieldName] = initFieldWidth + (initAffectedFieldWidth - minFilterWidth);
          }
          appStore.filters.data[currentSection].width.dynamic[fieldName] = tempFieldWidths[fieldName];
          appStore.filters.data[currentSection].width.dynamic[affectedFieldName] = tempFieldWidths[affectedFieldName];
        } else {
          if (tempFieldWidths[fieldName] < minFilterWidth) {
            tempFieldWidths[fieldName] = minFilterWidth;
          }
          appStore.filters.data[currentSection].width.dynamic[fieldName] = tempFieldWidths[fieldName];
        }
        appStore.updateLocalStorageConfig();
      }

      document.body.onmousemove = (mouseMoveEvent) => {
        devider.style.left = `${initDeviderOffsetLeft + mouseMoveEvent.pageX - initCursorCoord}px`;
        tempFieldWidths[fieldName] = initFieldWidth + mouseMoveEvent.pageX - initCursorCoord;
        if (affectedFieldName != null) {
          tempFieldWidths[affectedFieldName] = initAffectedFieldWidth - mouseMoveEvent.pageX + initCursorCoord;
        }
      };
    };
  }

  for (let i = 0; i < fieldsSequance.length; i++) {
    let currentItem = document.querySelector(`.filter-separator[name='${fieldsSequance[i]}']`);
    if (!currentItem) continue; // Skip if separator doesn't exist (e.g., for display-only fields)

    if (appStore.filters.availableParams.resizeMode == "straight") {
      addEventToSeparator(currentItem, fieldsSequance[i]);
    }
    if (appStore.filters.availableParams.resizeMode == "affected") {
      if (i > fieldsSequance.length - 2) continue;
      addEventToSeparator(currentItem, fieldsSequance[i], fieldsSequance[i + 1]);
    }
  }
});
</script>
