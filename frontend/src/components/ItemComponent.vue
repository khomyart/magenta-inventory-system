<template>
  <div class="row q-mt-md item q-py-sm">
    <div class="item-cell">
      <div :style="`width: ${props.cellsWidth.name}px;`">
        {{ props.itemInfo.name }}
      </div>
    </div>
    <div class="filter-separator name-separator"></div>
    <div class="item-cell">
      <div :style="`width: ${props.cellsWidth.type}px;`">
        <img
          class="item-type-icon"
          :src="`${props.itemInfo.type.icon}`"
          alt=""
        />
        {{ props.itemInfo.type.name }}
      </div>
    </div>
    <div class="filter-separator name-separator"></div>
    <div class="item-cell">
      <div :style="`width: ${props.cellsWidth.gender}px;`">
        <img
          class="item-type-icon"
          :src="`${props.itemInfo.gender.icon}`"
          alt=""
        />
        {{ props.itemInfo.gender.name }}
      </div>
    </div>
    <div class="filter-separator name-separator"></div>
    <div class="item-cell">
      <div :style="`width: ${props.cellsWidth.size}px;`">
        <span :id="`size-of-item-${props.itemInfo.id}`">
          {{ props.itemInfo.size.name }}
        </span>
        <q-tooltip
          :offset="[10, 5]"
          :target="`#size-of-item-${props.itemInfo.id}`"
          class="bg-black text-body2"
          anchor="center left"
          self="center right"
          >{{ props.itemInfo.size.description }}</q-tooltip
        >
      </div>
    </div>
    <div class="filter-separator name-separator"></div>
    <div class="item-cell q-pa-md">
      <div :style="`width: ${props.cellsWidth.color}px;`">
        <div
          :id="`color-of-item-${props.itemInfo.id}`"
          class="item-color"
          :style="`background-color: ${props.itemInfo.color.value};`"
          @click="copyColor(props.itemInfo.color.value)"
        >
          <span
            :style="`color: ${props.itemInfo.color.textColor}`"
            class="item-color-text"
            >{{ props.itemInfo.color.value }}</span
          >
        </div>
        <q-tooltip
          :offset="[10, 5]"
          :target="`#color-of-item-${props.itemInfo.id}`"
          class="bg-black text-body2"
          anchor="center left"
          self="center right"
          >{{ props.itemInfo.color.name }}</q-tooltip
        >
      </div>
    </div>
    <div class="filter-separator name-separator"></div>
    <div class="item-cell">
      <div :style="`width: ${props.cellsWidth.amount}px;`">
        {{ props.itemInfo.amount }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { useQuasar } from "quasar";

const props = defineProps(["itemInfo", "cellsWidth"]);
const $q = useQuasar();

function copyColor(colorValue) {
  navigator.clipboard.writeText(colorValue);
  $q.notify({
    position: "top",
    color: "primary",
    message: `Колір зкопійовано: "${colorValue}"`,
    group: false,
  });
}
</script>

<style scoped>
.item {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 3px;
}
.item-cell {
  padding: 4px 16px;
  height: 40px;
  display: flex;
  align-items: center;
}
.item-cell div {
  display: flex;
  align-items: center;
}
.item-type-icon {
  height: 25px;
  padding-right: 10px;
}
.item-color {
  width: 100%;
  height: 32px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 5px;
  cursor: pointer;
}
</style>
