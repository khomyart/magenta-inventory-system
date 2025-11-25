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
  },
  autoSetCurrentTime: {
    type: Boolean,
    default: true
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
    val => !!val || 'Поле не може бути порожнім'
  ] : null;
})

// Function to handle date selection and add current time if not set
function handleDateUpdate(newVal) {
  if (newVal) {
    // Check if the new value has time part and if it's 00:00
    const parts = newVal.split(' ');
    const timePart = parts.length > 1 ? parts[1] : null;

    // If time is 00:00 or not set
    if (!timePart || timePart === '00:00') {
      const datePart = parts[0];

      if (props.autoSetCurrentTime) {
        // Add current time if autoSetCurrentTime is true
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        date.value = `${datePart} ${hours}:${minutes}`;
      } else {
        // Keep 00:00 if autoSetCurrentTime is false
        date.value = `${datePart} 00:00`;
      }
    }
    // Otherwise keep the value as is (user manually set time)
  }
}
</script>

<template>
  <q-input debounce="700" :dense="dense" outlined v-model="date" :label="label" :rules="rules" mask="##/##/#### ##:##">

    <template v-slot:append>
      <q-icon v-if="date && date !== ''" name="close" class="cursor-pointer" @click="date = ''" />

      <q-icon name="event" class="cursor-pointer q-mx-sm">
        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
          <q-date v-model="date" mask="DD/MM/YYYY HH:mm" @update:model-value="handleDateUpdate">
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
