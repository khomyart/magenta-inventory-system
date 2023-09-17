<template>
  <q-btn flat round color="black" icon="sync_alt" @click="showItemMoveDialog">
    <q-tooltip
      class="bg-black text-body2"
      anchor="bottom left"
      :offset="[0, 7]"
    >
      Перемістити
    </q-tooltip>
  </q-btn>

  <q-dialog v-model="sectionStore.dialogs.itemMove.isShown">
    <q-card style="width: 100vw; max-width: 600px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="sync_alt" color="black" size="md" class="q-mr-sm" />
          Переміщення
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit.prevent="submit">
        <q-card-section
          style="max-height: 700px; height: 70vh"
          class="scroll col-12"
        >
          <ItemMoveGroupComponent />
        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn
            flat
            color="primary"
            type="submit"
            :loading="sectionStore.dialogs.itemMove.isLoading"
            ><b>Виконати</b></q-btn
          >
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
<script setup>
import { useItemStore } from "src/stores/itemStore";
import ItemMoveGroupComponent from "./ItemMoveGroupComponent.vue";
const sectionStore = useItemStore();

function submit() {
  sectionStore.sendItemMoveData();
}

function showItemMoveDialog() {
  sectionStore.dialogs.itemMove.isShown = true;
  sectionStore.itemMove = {
    from: {
      country: null,
      city: null,
      warehouse: null,
    },
    to: {
      country: null,
      city: null,
      warehouse: null,
    },

    reasonName: "moving",
    additionalReasonName: "",
    reasonDetail: "",
    itemMoveAmount: "",
    items: [],
  };
}
</script>
<style scoped></style>
