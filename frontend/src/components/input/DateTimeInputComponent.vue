<script setup>
import {onBeforeMount, ref, watch} from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number, Date],
    default: null
  },
  dense: {
    type: Boolean,
    default: false
  },
  label: { // first label is for first input, second label is for the second input
    type: String,
    default: ""
  },
  useRules: {
    type: Boolean,
    default: false
  }
});
const emit = defineEmits(['update:modelValue']);
const date = ref(props.modelValue);

watch(() => props.modelValue, (newVal) => {
  date.value = newVal;
});
watch(date, (newVal) => {
  emit('update:modelValue', newVal);
});
const rules = ref([]);
onBeforeMount(()=>{
  console.log(props.useRules)
  rules.value = props.useRules ? [
    val => val !== '' || 'Поле не може бути порожнім'
  ] : null;
})
</script>

<template>
  <q-input debounce="700" :dense="dense" outlined v-model="date" :label="label" :rules="rules">

    <template v-slot:append>
      <q-icon name="close" class="cursor-pointer q-mr-sm" @click="date = ''">
      </q-icon>

      <q-icon name="event" class="cursor-pointer q-mr-sm">
        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
          <q-date v-model="date" mask="DD/MM/YYYY HH:mm">
            <div class="row items-center justify-end">
              <q-btn v-close-popup label="Close" color="primary" flat />
            </div>
          </q-date>
        </q-popup-proxy>
      </q-icon>

      <q-icon name="access_time" class="cursor-pointer">
        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
          <q-time v-model="date" mask="DD/MM/YYYY HH:mm" format24h>
            <div class="row items-center justify-end">
              <q-btn v-close-popup label="Close" color="primary" flat />
            </div>
          </q-time>
        </q-popup-proxy>
      </q-icon>
    </template>
  </q-input>
</template>

<style scoped>

</style>
