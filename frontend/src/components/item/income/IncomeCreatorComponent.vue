<template>
  <q-btn
    flat
    round
    color="black"
    icon="arrow_downward"
    @click="showIncomeCreatorDialog"
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
      <q-form @submit.prevent="submit">
        <q-card-section
          style="max-height: 700px; height: 60vh"
          class="scroll col-12 q-pt-md"
        >
          <div class="row q-mb-sm text-h6">
            <div class="col-12 flex items-center justify-between text-bold">
              <span class="q-pl-md text-weight-medium">Склади</span>
              <div class="flex items-center">
                <q-btn
                  class="q-mr-sm"
                  round
                  flat
                  icon="add"
                  @click="addWarehouse"
                >
                  <q-tooltip
                    class="bg-black text-body2"
                    anchor="bottom middle"
                    self="top middle"
                    :offset="[0, 5]"
                  >
                    Додати склад
                  </q-tooltip>
                </q-btn>
                <q-btn
                  class="q-mr-sm"
                  round
                  flat
                  icon="delete"
                  @click="removeAllWarehouses"
                >
                  <q-tooltip
                    class="bg-black text-body2"
                    anchor="bottom middle"
                    self="top middle"
                    :offset="[0, 5]"
                  >
                    Видалити всі склади
                  </q-tooltip>
                </q-btn>
              </div>
            </div>
          </div>
          <IncomeCreatorWarehouseComponent
            v-for="(warehouse, index) in sectionStore.income"
            :key="index"
            :index="index"
          >
          </IncomeCreatorWarehouseComponent>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn
            flat
            color="primary"
            type="submit"
            :loading="sectionStore.dialogs.incomeCreator.isLoading"
            ><b>Створити</b></q-btn
          >
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
<script setup>
import { useItemStore } from "src/stores/itemStore";
import IncomeCreatorWarehouseComponent from "./IncomeCreatorWarehouseComponent.vue";
const sectionStore = useItemStore();

const warehouseTemplate = {
  country: null,
  city: null,
  warehouse: null,
  batches: [],
};

function submit() {
  sectionStore.sendIncomeData();
}

function showIncomeCreatorDialog() {
  sectionStore.dialogs.incomeCreator.isShown = true;
  sectionStore.income = [
    {
      country: null,
      city: null,
      warehouse: null,
      batches: [
        {
          amount: "",
          price: null,
          currency: "UAH",
          items: [],
        },
      ],
    },
  ];
}

function addWarehouse() {
  let warehouse = JSON.parse(JSON.stringify(warehouseTemplate));
  sectionStore.income.push(warehouse);
}

function removeAllWarehouses() {
  sectionStore.income = [];
}
</script>
<style scoped></style>
