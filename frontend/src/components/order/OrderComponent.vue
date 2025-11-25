<script setup>
import {computed, onUpdated, ref} from "vue";
import {getClientTime} from "app/helpers/GeneralPurposeFunctions";

const emit = defineEmits([
  "showEditDialog",
  "showRemoveDialog",
  "showCancelDialog",
  "showConfirmDialog",
  "showStartWorkDialog",
  "showCompleteDialog",
  "showPaymentDialog",
  "showContactDetails",
  "showNotesDialog",
  "clearUpdatedItemId",
  "copyValue",
]);

const props = defineProps([
  "itemInfo",
  "gap",
  "updated",
  "allowenses",
  "isFirst",
  "isLast",
  "itemsBorderRadius",
]);

let isUpdated = ref(false);

onUpdated(() => {
  isUpdated.value = props.updated;
  if (isUpdated.value) {
    emit("clearUpdatedItemId");
  }
});

const currencyIcon = computed(() => (
  {UAH: "₴", USD: "$", EUR: "€"}[props.itemInfo.currency] || ""
));

const totalPrice = computed(() => parseFloat(props.itemInfo.total_price).toFixed(2));

const statusDisplay = computed(() => {
  const statusMap = {
    'pending': 'Очікує',
    'confirmed': 'Підтверджено',
    'in_progress': 'В роботі',
    'completed': 'Виконано',
    'cancelled': 'Скасовано',
  };
  return statusMap[props.itemInfo.status] || props.itemInfo.status;
});

const statusColor = computed(() => {
  const colorMap = {
    'pending': 'orange',
    'confirmed': 'primary',
    'in_progress': 'blue',
    'completed': 'green',
    'cancelled': 'grey',
  };
  return colorMap[props.itemInfo.status] || 'grey';
});

const contactDisplay = computed(() => {
  if (!props.itemInfo.contact) return '-';
  const name = props.itemInfo.contact.name || '-';
  const phone = props.itemInfo.contact.phone || '';
  return phone ? `${name} (+${phone})` : name;
});

const formattedDeadline = computed(() => {
  if (!props.itemInfo.completion_deadline) return '-';
  return getClientTime(props.itemInfo.completion_deadline, 'ua', true);
});

const formattedCreatedAt = computed(() => {
  if (!props.itemInfo.created_at) return '-';
  return getClientTime(props.itemInfo.created_at, 'ua', true);
});

const formattedCompletedAt = computed(() => {
  if (!props.itemInfo.completed_at) return '-';
  return getClientTime(props.itemInfo.completed_at, 'ua', true);
});

const formattedFullyPayedAt = computed(() => {
  if (!props.itemInfo.fully_payed_at) return '-';
  return getClientTime(props.itemInfo.fully_payed_at, 'ua', true);
});

const advancePayment = computed(() => {
  const total =
    (parseFloat(props.itemInfo.amount_of_advance_payment_on_card) || 0) +
    (parseFloat(props.itemInfo.amount_of_advance_payment_via_terminal) || 0) +
    (parseFloat(props.itemInfo.amount_of_advance_payment_as_cash) || 0);
  return total.toFixed(2);
});

const finalPayment = computed(() => {
  const total =
    (parseFloat(props.itemInfo.amount_of_final_payment_on_card) || 0) +
    (parseFloat(props.itemInfo.amount_of_final_payment_via_terminal) || 0) +
    (parseFloat(props.itemInfo.amount_of_final_payment_as_cash) || 0);
  return total.toFixed(2);
});

const notesDisplay = computed(() => {
  if (!props.itemInfo.notes) return '-';
  const maxLength = 30;
  return props.itemInfo.notes.length > maxLength
    ? props.itemInfo.notes.substring(0, maxLength) + '...'
    : props.itemInfo.notes;
});

const advancePaymentDetails = computed(() => {
  const details = [];
  const card = parseFloat(props.itemInfo.amount_of_advance_payment_on_card) || 0;
  const terminal = parseFloat(props.itemInfo.amount_of_advance_payment_via_terminal) || 0;
  const cash = parseFloat(props.itemInfo.amount_of_advance_payment_as_cash) || 0;

  if (card > 0) details.push(`Карткою онлайн: ${currencyIcon.value}${card.toFixed(2)}`);
  if (terminal > 0) details.push(`Терміналом: ${currencyIcon.value}${terminal.toFixed(2)}`);
  if (cash > 0) details.push(`Готівкою: ${currencyIcon.value}${cash.toFixed(2)}`);

  return details.length > 0 ? details.join('\n') : 'Немає авансових платежів';
});

const finalPaymentDetails = computed(() => {
  const details = [];
  const card = parseFloat(props.itemInfo.amount_of_final_payment_on_card) || 0;
  const terminal = parseFloat(props.itemInfo.amount_of_final_payment_via_terminal) || 0;
  const cash = parseFloat(props.itemInfo.amount_of_final_payment_as_cash) || 0;

  if (card > 0) details.push(`Карткою онлайн: ${currencyIcon.value}${card.toFixed(2)}`);
  if (terminal > 0) details.push(`Терміналом: ${currencyIcon.value}${terminal.toFixed(2)}`);
  if (cash > 0) details.push(`Готівкою: ${currencyIcon.value}${cash.toFixed(2)}`);

  return details.length > 0 ? details.join('\n') : 'Немає фінальних платежів';
});

const remainingPayment = computed(() => {
  const total = parseFloat(props.itemInfo.total_price) || 0;
  const advanceTotal = parseFloat(advancePayment.value) || 0;
  const finalTotal = parseFloat(finalPayment.value) || 0;
  const remaining = total - advanceTotal - finalTotal;
  return remaining.toFixed(2);
});

const remainingPaymentDisplay = computed(() => {
  const value = parseFloat(remainingPayment.value);
  if (value < 0) {
    return `- ${currencyIcon.value}${Math.abs(value).toFixed(2)}`;
  }
  return `${currencyIcon.value}${remainingPayment.value}`;
});

// Helper function to format price for copying
const formatPriceForCopy = (price) => {
  const mainPart = Math.floor(price);
  const mainPartLabel =
    props.itemInfo.currency === "UAH"
      ? "грн"
      : props.itemInfo.currency === "USD"
        ? "дол"
        : props.itemInfo.currency === "EUR"
          ? "у.о"
          : "";

  const secondaryPart = ((price - mainPart) * 100).toFixed(0);
  const showSecondaryPart = secondaryPart != 0;
  const formattedSecondaryPart = secondaryPart < 10 ? `0${secondaryPart}` : secondaryPart;
  const secondaryPartLabel =
    props.itemInfo.currency === "UAH"
      ? "коп"
      : props.itemInfo.currency === "USD"
        ? "цент"
        : props.itemInfo.currency === "EUR"
          ? "є.ц"
          : "";

  return showSecondaryPart
    ? `${mainPart} ${mainPartLabel}. ${formattedSecondaryPart} ${secondaryPartLabel}.`
    : `${mainPart} ${mainPartLabel}.`;
};

const statusForCopy = computed(() => statusDisplay.value);

const advancePaymentForCopy = computed(() => formatPriceForCopy(parseFloat(advancePayment.value)));

const finalPaymentForCopy = computed(() => formatPriceForCopy(parseFloat(finalPayment.value)));

const totalPriceForCopy = computed(() => formatPriceForCopy(parseFloat(totalPrice.value)));

const remainingPaymentForCopy = computed(() => {
  const value = parseFloat(remainingPayment.value);
  if (value < 0) {
    return `- ${formatPriceForCopy(Math.abs(value))}`;
  }
  return formatPriceForCopy(value);
});
</script>

<template>
  <tr :height="props.gap"></tr>
  <tr class="item" :class="{ updated: isUpdated }" :id="`item${props.itemInfo.id}`">
    <td class="item-cell">
      <div :class="{
          'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0,
        }"
           :style="{
          borderRadius:
            props.gap == 0
              ? props.isFirst && !props.isLast
                ? `${props.itemsBorderRadius}px 0 0 0`
                : !props.isFirst && props.isLast
                ? `0 0 0 ${props.itemsBorderRadius}px`
                : props.isFirst && props.isLast
                ? `${props.itemsBorderRadius}px 0 0 ${props.itemsBorderRadius}px`
                : ``
              : `${props.itemsBorderRadius}px 0 0 ${props.itemsBorderRadius}px`,
        }">
        <q-btn v-if="(
          props.allowenses.update || props.allowenses.delete
          ) && ['pending', 'in_progress', 'confirmed', 'completed'].includes(props.itemInfo.status)" icon="list" round
               flat>
          <q-menu :offset="[5, 5]">
            <q-list style="min-width: 100px">
              <q-item v-if="props.allowenses.update && props.itemInfo.status !== 'cancelled'" clickable v-close-popup
                      @click="$emit('showEditDialog', props.itemInfo)">
                <div class="context-menu-item">
                  <q-icon size="sm" name="edit" left/>
                  <span>Редагувати</span></div>
              </q-item>
              <q-item v-if="props.allowenses.update && props.itemInfo.status === 'pending'" clickable v-close-popup
                      @click="$emit('showConfirmDialog', props.itemInfo)">
                <div class="context-menu-item">
                  <q-icon size="sm" color="primary" name="task_alt" left/>
                  <span>Підтвердити замовлення</span></div>
              </q-item>
              <q-item v-if="props.allowenses.update && props.itemInfo.status === 'confirmed'" clickable v-close-popup
                      @click="$emit('showStartWorkDialog', props.itemInfo)">
                <div class="context-menu-item">
                  <q-icon size="sm" color="blue" name="play_arrow" left/>
                  <span>Прийняти в роботу</span></div>
              </q-item>
              <q-item v-if="props.allowenses.update && props.itemInfo.status === 'in_progress'" clickable v-close-popup
                      @click="$emit('showCompleteDialog', props.itemInfo)">
                <div class="context-menu-item">
                  <q-icon size="sm" color="green" name="check_circle" left/>
                  <span>Виконати</span></div>
              </q-item>
              <q-item
                v-if="props.allowenses.update && props.itemInfo.status !== 'pending' && props.itemInfo.status !== 'cancelled' && !props.itemInfo.fully_payed_at"
                clickable v-close-popup
                @click="$emit('showPaymentDialog', props.itemInfo)">
                <div class="context-menu-item">
                  <q-icon size="sm" color="purple" name="payments" left/>
                  <span>Внести оплату</span></div>
              </q-item>
              <q-item v-if="props.allowenses.update && props.itemInfo.status !== 'cancelled'" clickable v-close-popup
                      @click="$emit('showCancelDialog', props.itemInfo)">
                <div class="context-menu-item">
                  <q-icon size="sm" color="orange" name="cancel" left/>
                  <span>Відмінити замовлення</span></div>
              </q-item>
              <!--              <q-item v-if="props.allowenses.delete" clickable v-close-popup-->
              <!--                      @click="$emit('showRemoveDialog', props.itemInfo.id)">-->
              <!--                <div class="context-menu-item">-->
              <!--                  <q-icon size="sm" color="red" name="delete" left/>-->
              <!--                  <span>Видалити</span></div>-->
              <!--              </q-item>-->
            </q-list>
          </q-menu>
        </q-btn>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- ID (Номер) -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', props.itemInfo.id, 'Номер')">
        <div class="item-text">{{ props.itemInfo.id }}</div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Status -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', statusForCopy, 'Статус')">
        <div class="item-text">
          <q-badge :color="statusColor" :label="statusDisplay" />
        </div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Completion Deadline (Дедлайн) -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', formattedDeadline, 'Дедлайн')">
        <div class="item-text">{{ formattedDeadline }}</div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Advance Payment (Аванс) -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', advancePaymentForCopy, 'Аванс')">
        <div class="item-text">
          {{ `${currencyIcon}${advancePayment}` }}
          <q-tooltip max-width="300px" class="bg-black text-body2">
            <div class="text-bold q-mb-xs">Деталізація авансу:</div>
            <div style="white-space: pre-wrap;">{{ advancePaymentDetails }}</div>
          </q-tooltip>
        </div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Final Payment (Оплата) -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', finalPaymentForCopy, 'Оплата')">
        <div class="item-text">
          {{ `${currencyIcon}${finalPayment}` }}
          <q-tooltip max-width="300px" class="bg-black text-body2">
            <div class="text-bold q-mb-xs">Деталізація оплати:</div>
            <div style="white-space: pre-wrap;">{{ finalPaymentDetails }}</div>
          </q-tooltip>
        </div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Total Price (Сума) -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', totalPriceForCopy, 'Сума')">
        <div class="item-text">{{ `${currencyIcon}${totalPrice}` }}</div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Remaining to Pay (До сплати) -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', remainingPaymentForCopy, 'До сплати')">
        <div class="item-text">
          <span :class="{
            'text-red': parseFloat(remainingPayment) > 0 && props.itemInfo.status !== 'cancelled',
            'text-green': parseFloat(remainingPayment) < 0,
            'text-grey-7': parseFloat(remainingPayment) === 0 || props.itemInfo.status === 'cancelled'
          }">
            {{ remainingPaymentDisplay }}
          </span>
        </div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Fully Payed At (Дата оплати) -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', formattedFullyPayedAt, 'Дата повної оплати')">
        <div class="item-text">
          <span v-if="props.itemInfo.fully_payed_at" class="text-green">{{ formattedFullyPayedAt }}</span>
          <span v-else class="text-grey-7">-</span>
        </div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Contact (Контакт) -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }">
        <div class="item-text">
          <span
            v-if="props.itemInfo.contact"
            class="text-primary"
            style="cursor: pointer; "
            @click="$emit('showContactDetails', props.itemInfo.contact)"
          >
            {{ contactDisplay }}
            <q-tooltip class="bg-black text-body2">Переглянути контакт</q-tooltip>
          </span>
          <span v-else class="text-grey-7">-</span>
        </div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Created At (Створено) -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', formattedCreatedAt, 'Дата створення')">
        <div class="item-text">{{ formattedCreatedAt }}</div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Completed At (Завершено) -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', formattedCompletedAt, 'Дата завершення')">
        <div class="item-text">{{ formattedCompletedAt }}</div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Notes (Нотатки) -->
    <td class="item-cell">
      <div :class="{
          'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0,
        }"
           :style="{
          borderRadius:
            props.gap == 0
              ? props.isFirst && !props.isLast
                ? `0 ${props.itemsBorderRadius}px 0 0`
                : !props.isFirst && props.isLast
                ? `0 0 ${props.itemsBorderRadius}px 0`
                : props.isFirst && props.isLast
                ? `0 ${props.itemsBorderRadius}px ${props.itemsBorderRadius}px 0`
                : ``
              : `0 ${props.itemsBorderRadius}px ${props.itemsBorderRadius}px 0`,
        }">
        <div class="item-text">
          <span
            v-if="props.itemInfo.notes"
            class="text-primary"
            style="cursor: pointer;"
            @click="$emit('showNotesDialog', props.itemInfo.notes)"
          >
            {{ notesDisplay }}
            <q-tooltip max-width="300px" class="bg-black text-body2">
              <div class="text-bold q-mb-xs">Нотатки:</div>
              <div style="white-space: pre-wrap;">{{ props.itemInfo.notes }}</div>
            </q-tooltip>
          </span>
          <span v-else class="text-grey-7">-</span>
        </div>
      </div>
    </td>
  </tr>
</template>

<style scoped>
.updated td > div {
  animation: 2s linear color-fading;
}

@keyframes color-fading {
  0% {
    background-color: rgba(255, 255, 255, 0);
  }
  10% {
    background-color: rgba(43, 255, 0, 0.192);
  }
  60% {
    background-color: rgba(43, 255, 0, 0.192);
  }
  100% {
    background-color: rgba(255, 255, 255, 0);
  }
}
</style>
