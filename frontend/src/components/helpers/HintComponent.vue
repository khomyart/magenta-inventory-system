<template>
  <q-icon name="help" color="black" :size="iconSize">
    <q-tooltip class="bg-black text-body2">
      {{ props.text }}
    </q-tooltip>
  </q-icon>
</template>
<script setup>
import { computed } from "vue";
const props = defineProps(["size", "text"]);
let iconSize = computed(() => {
  return `${props.size}px`;
});
</script>
<style scoped></style>
