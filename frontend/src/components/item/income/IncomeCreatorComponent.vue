<template>
  <q-btn
    flat
    round
    color="black"
    icon="arrow_downward"
    @click="sectionStore.dialogs.incomeCreator.isShown = true"
  >
    <q-tooltip
      class="bg-black text-body2"
      anchor="bottom left"
      :offset="[-18, 7]"
    >
      Зарахувати надходження
    </q-tooltip>
  </q-btn>

  <q-dialog v-model="sectionStore.dialogs.incomeCreator.isShown">
    <q-card style="width: 95vw; max-width: 600px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon
            name="arrow_downward"
            color="black"
            size="md"
            class="q-mr-sm"
          />
          Надходження
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form>
        <q-card-section
          style="max-height: 700px; height: 60vh"
          class="scroll col-12 q-pt-lg"
        >
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
import { useItemStore } from "src/stores/itemStore";
const sectionStore = useItemStore();
</script>
<style scoped></style>
