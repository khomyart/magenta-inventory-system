<template>
  <tr :height="props.gap"></tr>
  <tr
    class="item"
    :class="{
      updated: isUpdated,
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
                    props.itemInfo.description
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
        :id="`colorItem_${props.itemInfo.id}`"
        style="cursor: pointer"
        @click="$emit('copyValue', props.itemInfo.value, 'Колір')"
      >
        <q-tooltip
          :offset="[0, 5]"
          :target="`#colorItem_${props.itemInfo.id}`"
          class="bg-black text-body2"
          anchor="center left"
          self="center right"
          >{{ props.itemInfo.value }}</q-tooltip
        >
        <div
          class="item-color"
          :style="{
            backgroundColor: props.itemInfo.value,
            width: '100%',
          }"
        ></div>
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
        @click="$emit('copyValue', props.itemInfo.article, 'Артикль')"
      >
        <div class="item-text">
          {{ props.itemInfo.article }}
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
        @click="$emit('copyValue', props.itemInfo.description, 'Опис')"
      >
        <div class="item-text">
          {{ props.itemInfo.description }}
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
        :id="`textColorItem_${props.itemInfo.id}`"
        style="cursor: pointer"
        @click="
          $emit('copyValue', props.itemInfo.text_color_value, 'Колір тексту')
        "
      >
        <q-tooltip
          :offset="[0, 5]"
          :target="`#textColorItem_${props.itemInfo.id}`"
          class="bg-black text-body2"
          anchor="center left"
          self="center right"
          >{{ props.itemInfo.text_color_value }}</q-tooltip
        >
        <div
          class="item-color"
          :style="{
            backgroundColor: props.itemInfo.text_color_value,
            width: '100%',
          }"
        ></div>
      </div>
    </td>
  </tr>
</template>

<script setup>
import { onUpdated, ref } from "vue";

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

  if (isUpdated.value == true) {
    emit("clearUpdatedItemId");
  }
});
</script>
