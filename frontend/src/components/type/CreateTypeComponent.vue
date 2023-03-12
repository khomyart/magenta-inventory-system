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
          <q-icon name="interests" color="black" size="md" class="q-mr-sm" />
          Вид
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
            v-model="newItem.article"
            autofocus
            label="Артикль"
            :rules="[
              (val) => (val !== null && val !== '') || 'Введіть артикль',
              (val) => val.length <= 8 || 'Не більше 8 символів',
            ]"
          />
          <q-input
            outlined
            v-model="newItem.name"
            label="Назва"
            :rules="[
              (val) => (val !== null && val !== '') || 'Введіть назву',
              (val) => val.length <= 128 || 'Не більше 128 символів',
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
import { useTypeStore } from "src/stores/typeStore";

const sectionStore = useTypeStore();

function showCreateDialog() {
  newItem.article = "";
  newItem.name = "";
  sectionStore.dialogs.create.isShown = true;
}

let newItem = reactive({
  article: "",
  name: "",
});
</script>
<style scoped></style>
