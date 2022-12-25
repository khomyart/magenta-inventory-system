<template>
  <div class="row q-mt-md">
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
      <div :style="`width: ${props.cellsWidth.size}px;`">розмір</div>
    </div>
    <div class="filter-separator name-separator"></div>
    <div class="item-cell">
      <div :style="`width: ${props.cellsWidth.color}px;`">колір</div>
    </div>
    <div class="filter-separator name-separator"></div>
    <div class="item-cell">
      <div :style="`width: ${props.cellsWidth.amount}px;`">кількість</div>
    </div>
  </div>
</template>
<script setup>
import { onMounted, onUpdated } from "vue";
const props = defineProps(["itemInfo", "cellsWidth"]);
onMounted(() => {});
</script>
<style scoped>
.item-cell {
  padding: 4px 16px;
  height: 40px;
}
.item-cell div {
  display: flex;
  align-items: center;
}
.item-type-icon {
  height: 25px;
  padding-right: 10px;
}
</style>
