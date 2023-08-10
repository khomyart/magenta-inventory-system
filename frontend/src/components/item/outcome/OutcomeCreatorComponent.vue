<template>
  <q-btn
    flat
    round
    color="black"
    icon="arrow_upward"
    @click="showOutcomeCreatorDialog"
  >
    <q-tooltip
      class="bg-black text-body2"
      anchor="bottom left"
      :offset="[-20, 7]"
    >
      Списання
    </q-tooltip>
  </q-btn>

  <q-dialog v-model="sectionStore.dialogs.outcomeCreator.isShown">
    <q-card style="width: 100vw; max-width: 600px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="arrow_upward" color="black" size="md" class="q-mr-sm" />
          Списання
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit.prevent="submit">
        <q-card-section
          style="max-height: 700px; height: 70vh"
          class="scroll col-12"
        >
          <OutcomeCreatorGroupComponent />
        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn
            flat
            color="primary"
            type="submit"
            :loading="sectionStore.dialogs.outcomeCreator.isLoading"
            ><b>Створити</b></q-btn
          >
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
<script setup>
import { useItemStore } from "src/stores/itemStore";
import OutcomeCreatorGroupComponent from "./OutcomeCreatorGroupComponent.vue";
const sectionStore = useItemStore();

function submit() {
  sectionStore.sendOutcomeData();
}

function showOutcomeCreatorDialog() {
  sectionStore.dialogs.outcomeCreator.isShown = true;
  sectionStore.outcome = {
    country: null,
    city: null,
    warehouse: null,
    reasonName: "sell",
    additionalReasonName: "",
    reasonDetail: "",
    outcomeAmount: "",
    items: [],
  };
}
</script>
<style scoped></style>
