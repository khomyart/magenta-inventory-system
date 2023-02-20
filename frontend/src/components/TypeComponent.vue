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
        <q-btn icon="list" round flat>
          <q-menu :offset="[5, 5]">
            <q-list style="min-width: 100px">
              <q-item
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
        @click="copyValue(props.itemInfo.article, 'Артикль')"
      >
        <div class="item-article">
          {{ props.itemInfo.article }}
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
    <td class="item-cell">
      <div class="item-name" @click="copyValue(props.itemInfo.name, 'Назву')">
        {{ props.itemInfo.name }}
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
  </tr>
</template>

<script setup>
import { useQuasar } from "quasar";
import { onMounted, onUpdated, ref } from "vue";

const emit = defineEmits([
  "showEditDialog",
  "showRemoveDialog",
  "clearUpdatedItemId",
]);

const props = defineProps(["itemInfo", "gap", "updated"]);
const $q = useQuasar();
let isUpdated = ref(false);

onUpdated(() => {
  isUpdated.value = props.updated;
  emit("clearUpdatedItemId");
});

function copyValue(value, paramName) {
  navigator.clipboard.writeText(value);
  $q.notify({
    position: "top",
    color: "primary",
    message: `${paramName} зкопійовано: "${value}"`,
    group: false,
  });
}
</script>

<style>
:root {
  --cell-height: 50px;
  --cell-border-style: 1px solid rgba(0, 0, 0, 0.18);
}
.item td > div {
  /* border: 1px solid rgba(0, 0, 0, 0.12); */
}
.item:hover td > div {
  background-color: rgba(255, 0, 217, 0.088);
}
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
.item-cell {
  padding: 0px;
}

.item-cell > div {
  width: 100%;
  display: flex;
  align-items: center;
  padding: 5px 10px;
  height: var(--cell-height);
  /* justify-content: center; */
}

.separator-cell div {
  width: 100%;
  height: var(--cell-height);
  box-sizing: border-box;
  /* background-color: rgba(14, 14, 14, 0.032); */
}
tr td {
  padding: 0;
  margin: 0;
}
tr td:first-child > div {
  border-left: var(--cell-border-style);
  border-top: var(--cell-border-style);
  border-bottom: var(--cell-border-style);
  border-radius: 3px 0 0 3px;
}

tr td:nth-child(n + 1):nth-child(-n + 25) > div {
  border-top: var(--cell-border-style);
  border-bottom: var(--cell-border-style);
}

tr td:last-child > div {
  border-right: var(--cell-border-style);
  border-top: var(--cell-border-style);
  border-bottom: var(--cell-border-style);
  border-radius: 0 3px 3px 0;
}

.context-menu-item {
  display: flex;
  align-items: center;
}
.context-menu-item span {
  /* font-size: 1rem; */
}
.item-name {
  white-space: nowrap;
  text-overflow: ellipsis;
  cursor: pointer;
}

.item-type {
  display: flex;
  align-items: center;
}
.item-type > span {
  white-space: nowrap;
  text-overflow: ellipsis;
}
.item-type-icon {
  --icon-width: 35px;
  padding-right: 8px;
  min-width: var(--icon-width);
  max-width: var(--icon-width);
}
.item-gender {
  display: flex;
  align-items: center;
}
.item-gender > span {
  white-space: nowrap;
  text-overflow: ellipsis;
}
.item-color-container {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}
.item-color {
  width: 70%;
  height: 80%;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 5px;
  cursor: pointer;
}
</style>
