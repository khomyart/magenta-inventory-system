<script setup>
import {computed, onUpdated, ref} from "vue";

const emit = defineEmits([
  "showEditDialog",
  "showRemoveDialog",
  "showCancelDialog",
  "showConfirmDialog",
  "showStartWorkDialog",
  "showCompleteDialog",
  "showPaymentDialog",
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

const contactDisplay = computed(() => {
  return props.itemInfo.contact?.name || `ID: ${props.itemInfo.contact_id || '-'}`;
});

const formattedDeadline = computed(() => {
  if (!props.itemInfo.completion_deadline) return '-';
  const date = new Date(props.itemInfo.completion_deadline);
  return date.toLocaleDateString('uk-UA');
});

const formattedCreatedAt = computed(() => {
  if (!props.itemInfo.created_at) return '-';
  const date = new Date(props.itemInfo.created_at);
  return date.toLocaleDateString('uk-UA');
});

const involvementDisplay = computed(() => {
  const parts = [];
  if (props.itemInfo.involvement_level_1_user) {
    parts.push(`${props.itemInfo.involvement_level_1_user.name} (8%)`);
  }
  if (props.itemInfo.involvement_level_2_user) {
    parts.push(`${props.itemInfo.involvement_level_2_user.name} (5%)`);
  }
  if (props.itemInfo.involvement_level_3_user) {
    parts.push(`${props.itemInfo.involvement_level_3_user.name} (3%)`);
  }
  return parts.length > 0 ? parts.join(', ') : '-';
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
          ) && ['pending', 'in_progress', 'confirmed', 'completed'].includes(props.itemInfo.status)" icon="list" round flat>
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
              <q-item v-if="props.allowenses.update && props.itemInfo.status !== 'pending' && props.itemInfo.status !== 'cancelled' && !props.itemInfo.fully_payed_at" clickable v-close-popup
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
    <!-- Status -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', props.itemInfo.status, 'Статус')">
        <div class="item-text">{{ statusDisplay }}</div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Total Price -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', `${currencyIcon}${totalPrice}`, 'Сума')">
        <div class="item-text">{{ `${currencyIcon}${totalPrice}` }}</div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Contact ID -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', contactDisplay, 'Контакт')">
        <div class="item-text">{{ contactDisplay }}</div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Completion Deadline -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', formattedDeadline, 'Дедлайн')">
        <div class="item-text">{{ formattedDeadline }}</div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Created At -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', formattedCreatedAt, 'Дата створення')">
        <div class="item-text">{{ formattedCreatedAt }}</div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Involvement -->
    <td class="item-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }" style="cursor: pointer"
           @click="$emit('copyValue', involvementDisplay, 'Залучені')">
        <div class="item-text">{{ involvementDisplay }}</div>
      </div>
    </td>
    <td class="separator-cell">
      <div :class="{ 'bottom-border': (props.gap == 0 && props.isLast) || props.gap != 0 }"></div>
    </td>
    <!-- Notes -->
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
        <div class="item-text" style="display: flex; justify-content: center; align-items: center;">
          <q-btn v-if="props.itemInfo.notes" icon="description" round flat size="sm" color="primary">
            <q-tooltip max-width="300px" class="bg-black text-body2">
              <div class="text-bold q-mb-xs">Нотатки:</div>
              <div style="white-space: pre-wrap;">{{ props.itemInfo.notes }}</div>
            </q-tooltip>
          </q-btn>
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
