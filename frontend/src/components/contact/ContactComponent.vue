<script setup>
import {computed, onUpdated, ref} from "vue";
import parsePhoneNumberFromString from "libphonenumber-js";

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

  if (isUpdated.value === true) {
    emit("clearUpdatedItemId");
  }
});

const preferredPlatformsLabel = computed(() => {
  let platformsMap = {
    "other": "Інші",
    "sms": "SMS",
    "call": "Дзвінок",
  }
  let tmpArray = props.itemInfo.preferred_platforms.map((el) => {
    if (Object.keys(platformsMap).includes(el)) {
      return platformsMap[el];
    }
    return el.charAt(0).toUpperCase() + el.slice(1);
  })
  return tmpArray.join(", ");
})

const parsedPhoneNumber = computed(() => {
  return parsePhoneNumberFromString("+" + props.itemInfo.phone).formatInternational()
})
</script>

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
                    props.itemInfo.name
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
        @click="$emit('copyValue', props.itemInfo.name, 'Ім\'я')"
      >
        <div class="item-text">
          {{ props.itemInfo.name }}
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
        @click="$emit('copyValue', props.itemInfo.phone, 'Телефон')"
      >
        <div class="item-text">
          {{ parsedPhoneNumber }}
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
        @click="$emit('copyValue', props.itemInfo.email, 'Електронну пошту')"
      >
        <div class="item-text">
          {{ props.itemInfo.email }}
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
        @click="$emit('copyValue', props.itemInfo.address, 'Адресу')"
      >
        <div class="item-text">
          {{ props.itemInfo.address }}
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
        @click="$emit('copyValue', props.itemInfo.additional_info, 'Додаткову інформацію')"
      >
        <div class="item-text">
          {{ props.itemInfo.additional_info }}
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
      >
        <div class="item-text">
          {{ preferredPlatformsLabel }}
        </div>
      </div>
    </td>
  </tr>
</template>

<style scoped>

</style>

