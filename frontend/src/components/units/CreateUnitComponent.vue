<template>
  <q-btn flat round color="black" icon="add" @click="showCreateDialog">
    <q-tooltip
      anchor="bottom left"
      :offset="[-20, 7]"
      class="bg-black text-body2"
    >
      Створити
    </q-tooltip>
  </q-btn>

  <q-dialog v-model="sectionStore.dialogs.create.isShown">
    <q-card>
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="dataset" color="black" size="md" class="q-mr-sm" />
          Одиниця
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="sectionStore.create(newItem)">
        <q-card-section
          style="max-height: 50vh; width: 300px"
          class="scroll q-pt-lg"
        >
          <q-input
            class="q-mb-sm"
            outlined
            v-model="newItem.name"
            autofocus
            label="Значення"
            :rules="[
              (val) => (val !== null && val !== '') || 'Введіть назву',
              (val) => val.length <= 20 || 'Не більше 20 символів',
            ]"
          />
          <q-input
            outlined
            v-model="newItem.description"
            label="Опис"
            :rules="[
              (val) => (val !== null && val !== '') || 'Введіть опис',
              (val) => val.length <= 50 || 'Не більше 50 символів',
            ]"
          />
        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn
            flat
            color="primary"
            type="submit"
            :loading="sectionStore.dialogs.create.isLoading"
            ><b>Створити</b></q-btn
          >
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
<script setup>
import { reactive } from "vue";
import { useUnitStore } from "src/stores/unitStore";
const sectionStore = useUnitStore();

function showCreateDialog() {
  newItem.name = "";
  newItem.description = "";
  sectionStore.dialogs.create.isShown = true;
}

let newItem = reactive({
  name: "",
  description: "",
});
</script>
<style scoped></style>
