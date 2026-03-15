<script setup>
import {computed, onUpdated, ref} from "vue";
import {getClientTime} from "app/helpers/GeneralPurposeFunctions";

const emit = defineEmits([
  "showEditDialog",
  "showRemoveDialog",
  "clearUpdatedItemId",
  "copyValue",
]);

const props = defineProps([
  "itemInfo",
  "gap",
  "updated",
  "allowenses",
  "sectionStore",
  "isFirst",
  "isLast",
  "itemsBorderRadius",
]);

let isUpdated = ref(false);

onUpdated(() => {
  isUpdated.value = props.updated;

  if (isUpdated.value == true) {
    emit("clearUpdatedItemId");
  }
});

const transactionHappenedTime = computed(() => {
  return getClientTime(props.itemInfo.happened_at, "ua", true)
})
const transactionCreationTime = computed(() => {
  return getClientTime(props.itemInfo.created_at, "ua", true)
})

const currencyIcon = computed(() => {
  let icon = "";

  switch (props.itemInfo.currency) {
    case "UAH":
      icon = "₴";
      break;
    case "USD":
      icon = "$";
      break;
    case "EUR":
      icon = "€";
      break;
    default:
      break;
  }

  return icon;
});

const totalPrice = computed(() => {
  return parseFloat(props.itemInfo.total_price).toFixed(2);
});

const convertedPrice = computed(() => {
  return props.itemInfo.currency !== "UAH" && props.itemInfo.converted_price_to_uah
    ? `(₴ ~${parseFloat(props.itemInfo.converted_price_to_uah).toFixed(2)})`
    : "";
});

const paymentDetails = computed(() => {
  const details = [];
  const card = parseFloat(props.itemInfo.amount_on_card) || 0;
  const terminal = parseFloat(props.itemInfo.amount_via_terminal) || 0;
  const cash = parseFloat(props.itemInfo.amount_as_cash) || 0;

  if (card > 0) details.push(`Картка: ${currencyIcon.value}${card.toFixed(2)}`);
  if (terminal > 0) details.push(`Термінал: ${currencyIcon.value}${terminal.toFixed(2)}`);
  if (cash > 0) details.push(`Готівка: ${currencyIcon.value}${cash.toFixed(2)}`);

  return details.length > 0 ? details.join('\n') : 'Деталі відсутні';
});

const typeLabel = computed(() => {
  return props.itemInfo.type === 'income' ? 'Дохід' : 'Витрата';
});

const typeColor = computed(() => {
  return props.itemInfo.type === 'income' ? 'text-green' : 'text-red';
});
</script>

<template>
  <tr :height="props.gap"></tr>
  <tr
    class="item"
    :class="{
      'updated': isUpdated,
    }"
    :id="`item${props.itemInfo.id}`"
  >
    <td class="item-cell">
      <div
        :class="{
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
        }"
      >
        <q-btn
          v-if="
            props.allowenses.update == true || props.allowenses.delete == true
          "
          icon="list"
          round
          flat
        >
          <q-menu :offset="[5, 5]">
            <q-list style="min-width: 100px">
              <q-item
                v-if="props.allowenses.update == true"
                clickable
                v-close-popup
                @click="$emit('showEditDialog', props.itemInfo)"
              >
                <div class="context-menu-item">
                  <q-icon size="sm" name="edit" left></q-icon>
                  <span>Редагувати</span>
                </div>
              </q-item>
              <q-item
                v-if="props.allowenses.delete == true"
                clickable
                v-close-popup
                @click="
                  $emit(
                    'showRemoveDialog',
                    props.itemInfo.id,
                    props.itemInfo.title
                  )
                "
              >
                <div class="context-menu-item">
                  <q-icon size="sm" color="red" name="delete" left></q-icon>
                  <span>Видалити</span>
                </div>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </div>
    </td>
    <td class="separator-cell">
      <div
        :class="{
          'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0,
        }"
      ></div>
    </td>
    <td class="item-cell">
      <div
        :class="{
          'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0,
        }"
        style="cursor: pointer"
        @click="$emit('copyValue', props.itemInfo.title, 'Назва')"
      >
        <div class="item-text">
          {{ props.itemInfo.title }}
        </div>
      </div>
    </td>
    <td class="separator-cell">
      <div
        :class="{
          'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0,
        }"
      ></div>
    </td>
    <td class="item-cell">
      <div
        :class="{
          'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0,
        }"
      >
        <div class="item-text" :class="typeColor">
          {{ typeLabel }}
        </div>
      </div>
    </td>
    <td class="separator-cell">
      <div
        :class="{
          'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0,
        }"
      ></div>
    </td>
    <td class="item-cell">
      <div
        :class="{
          'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0,
        }"
        style="cursor: pointer"
        @click="$emit('copyValue', totalPrice, 'Сума')"
      >
        <div class="item-text">
          {{ `${currencyIcon}${totalPrice} ${convertedPrice}` }}
          <q-tooltip max-width="300px" class="bg-black text-body2">
            <div class="text-bold q-mb-xs">Деталізація суми:</div>
            <div style="white-space: pre-wrap;">{{ paymentDetails }}</div>
          </q-tooltip>
        </div>
      </div>
    </td>
    <td class="separator-cell">
      <div
        :class="{
          'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0,
        }"
      ></div>
    </td>
    <td class="item-cell">
      <div
        :class="{
          'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0,
        }"
        style="cursor: pointer"
        @click="$emit('copyValue', transactionHappenedTime, 'Дату транзакції')"
      >
        <div class="item-text">
          {{ transactionHappenedTime }}
        </div>
      </div>
    </td>
    <td class="separator-cell">
      <div
        :class="{
          'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0,
        }"
      ></div>
    </td>
    <td class="item-cell">
      <div
        :class="{
          'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0,
        }"
        style="cursor: pointer"
        @click="$emit('copyValue', transactionCreationTime, 'Дату створення')"
      >
        <div class="item-text">
          {{ transactionCreationTime }}
        </div>
      </div>
    </td>
    <td class="separator-cell">
      <div
        :class="{
          'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0,
        }"
      ></div>
    </td>
    <td class="item-cell">
      <div
        :class="{
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
        }"
        style="cursor: pointer"
        @click="$emit('copyValue', props.itemInfo.created_by_user.name, 'Автор')"
      >
        <div class="item-text">
          {{ props.itemInfo.created_by_user?.name }}
        </div>
      </div>
    </td>
  </tr>
</template>

<style scoped>
</style>
