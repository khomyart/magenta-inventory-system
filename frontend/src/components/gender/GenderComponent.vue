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
      <div>
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
                v-if="
                  props.allowenses.update == true &&
                  props.itemInfo.number_in_row !==
                    props.sectionStore.data.firstItemNumberInRow &&
                  props.isMoveAllowed == true
                "
                clickable
                v-close-popup
                @click="$emit('moveInRow', props.itemInfo.id, 'up')"
              >
                <div class="context-menu-item">
                  <q-icon size="sm" name="expand_less" left></q-icon>
                  <span>Перемістити вгору</span>
                </div>
              </q-item>
              <q-item
                v-if="
                  props.allowenses.update == true &&
                  props.itemInfo.number_in_row !==
                    props.sectionStore.data.lastItemNumberInRow &&
                  props.isMoveAllowed == true
                "
                clickable
                v-close-popup
                @click="$emit('moveInRow', props.itemInfo.id, 'down')"
              >
                <div class="context-menu-item">
                  <q-icon size="sm" name="expand_more" left></q-icon>
                  <span>Перемістити вниз</span>
                </div>
              </q-item>
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
    <td class="separator-cell"><div></div></td>
    <td class="item-cell">
      <div
        style="cursor: pointer"
        @click="$emit('copyValue', props.itemInfo.name, 'Назву')"
      >
        <div class="item-text">
          {{ props.itemInfo.name }}
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
  </tr>
</template>

<script setup>
import { onUpdated, ref } from "vue";

const emit = defineEmits([
  "showEditDialog",
  "showRemoveDialog",
  "clearUpdatedItemId",
  "copyValue",
  "moveInRow",
]);

const props = defineProps([
  "itemInfo",
  "gap",
  "updated",
  "allowenses",
  "sectionStore",
  "isMoveAllowed",
]);
let isUpdated = ref(false);

onUpdated(() => {
  isUpdated.value = props.updated;

  if (isUpdated.value == true) {
    emit("clearUpdatedItemId");
  }
});
</script>
