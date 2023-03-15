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
          <q-icon
            name="face_retouching_natural"
            color="black"
            size="md"
            class="q-mr-sm"
          />
          Гендер
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="sectionStore.create(newItem)">
        <q-card-section
          style="max-height: 50vh; width: 300px"
          class="scroll q-pt-md"
        >
          <q-input
            class="q-mb-sm"
            outlined
            v-model="newItem.name"
            autofocus
            label="Назва"
            :rules="[
              (val) => (val !== null && val !== '') || 'Введіть назву',
              (val) => val.length <= 150 || 'Не більше 150 символів',
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
import { useGenderStore } from "src/stores/genderStore";
const sectionStore = useGenderStore();

function showCreateDialog() {
  newItem.name = "";
  sectionStore.dialogs.create.isShown = true;
}

let newItem = reactive({
  name: "",
});
</script>
<style scoped></style>
