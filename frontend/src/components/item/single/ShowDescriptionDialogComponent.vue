<template>
  <q-dialog v-model="sectionStore.dialogs.itemDescription.isShown">
    <q-card class="card">
      <q-card-section class="row items-center q-pb-md">
        <div class="text-h6 images-dialog-header">
          <q-icon name="apps" color="black" size="md" class="icon-header" />
          <div class="q-ml-sm text-header">
            {{ sectionStore.dialogs.itemDescription.title }}
          </div>
          <q-btn
            icon="close"
            flat
            round
            dense
            v-close-popup
            class="close-button-header"
          />
        </div>
      </q-card-section>
      <q-card-section class="flex justify-center content"
        ><div v-html="sectionStore.dialogs.itemDescription.content"></div>
      </q-card-section>
      <q-card-section class="flex justify-end">
        <!-- <q-btn
          color="primary"
          @click="copyContent(sectionStore.dialogs.itemDescription.content)"
          >Копіювати</q-btn
        > -->
        <q-btn flat color="black" v-close-popup>Гаразд</q-btn>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { useQuasar } from "quasar";
import { useItemStore } from "src/stores/itemStore";

const sectionStore = useItemStore();
const $q = useQuasar();

function copyContent(value) {
  navigator.clipboard.writeText(value);
  $q.notify({
    position: "top",
    color: "primary",
    message: `Опис скопійовано`,
    // group: false,
    actions: [
      {
        icon: "close",
        color: "white",
        round: true,
        handler: () => {},
      },
    ],
  });
}
</script>

<style scoped>
.images-dialog-header {
  display: flex;
  flex-direction: row;
  width: 100%;
}
.icon-header {
  display: flex;
  flex: 0 0;
  margin-right: 5px;
}
.text-header {
  flex: 1 1 auto;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
}
.close-button-header {
  margin-left: 5px;
  display: flex;
  flex: 0 0;
}
.card {
  width: 500px;
  max-width: 95vw;
  max-height: 95vh;
}
.content {
  max-height: 70vh;
  padding: 10px 30px;
}

.content > div {
  width: 100%;
  padding: 0;
}
</style>
