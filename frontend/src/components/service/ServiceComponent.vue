<script setup>
import {computed, onUpdated, ref} from "vue";

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
const unconvertedPrice = computed(() => parseFloat(props.itemInfo.unconverted_price).toFixed(2));
const convertedPrice = computed(() =>
  props.itemInfo.currency !== "UAH"
    ? `(₴ ~${parseFloat(props.itemInfo.converted_price_to_uah).toFixed(2)})`
    : ""
);

const priceForCopying = computed(() => {
  let priceForCopying = "";

  let mainPart = Math.floor(props.itemInfo.unconverted_price);
  let mainPartLabel =
    props.itemInfo.currency === "UAH"
      ? "грн"
      : props.itemInfo.currency === "USD"
        ? "дол"
        : props.itemInfo.currency === "EUR"
          ? "у.о"
          : "";

  let secondaryPart = (
    (props.itemInfo.unconverted_price - mainPart) *
    100
  ).toFixed(0);
  let showSecondaryPart = secondaryPart == 0 ? false : true;
  secondaryPart = secondaryPart < 10 ? `0${secondaryPart}` : secondaryPart;
  let secondaryPartLabel =
    props.itemInfo.currency === "UAH"
      ? "коп"
      : props.itemInfo.currency === "USD"
        ? "цент"
        : props.itemInfo.currency === "EUR"
          ? "є.ц"
          : "";

  priceForCopying =
    showSecondaryPart === true
      ? `${mainPart} ${mainPartLabel}. ${secondaryPart} ${secondaryPartLabel}.`
      : `${mainPart} ${mainPartLabel}.`;

  return priceForCopying;
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
        <q-btn v-if="props.allowenses.update || props.allowenses.delete" icon="list" round flat>
          <q-menu :offset="[5, 5]">
            <q-list style="min-width: 100px">
              <q-item v-if="props.allowenses.update" clickable v-close-popup
                      @click="$emit('showEditDialog', props.itemInfo)">
                <div class="context-menu-item">
                  <q-icon size="sm" name="edit" left/>
                  <span>Редагувати</span></div>
              </q-item>
              <q-item v-if="props.allowenses.delete" clickable v-close-popup
                      @click="$emit('showRemoveDialog', props.itemInfo.id, props.itemInfo.title)">
                <div class="context-menu-item">
                  <q-icon size="sm" color="red" name="delete" left/>
                  <span>Видалити</span></div>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', props.itemInfo.title, 'Назву')">
        <div class="item-text">{{ props.itemInfo.title }}</div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
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
        }"
           @click="$emit('copyValue', priceForCopying, 'Вартість')">
        <div class="item-text">{{ `${currencyIcon}${unconvertedPrice} ${convertedPrice}` }}</div>
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
