<script setup>
import { reactive } from "vue";
import {useSpendStore} from "stores/spendStore";
import DateTimeInputComponent from "components/input/DateTimeInputComponent.vue";
import {useAppConfigStore} from "stores/appConfigStore";
import moment from "moment";
const sectionStore = useSpendStore();
const appStore = useAppConfigStore();
function showCreateDialog() {
  newItem.title = "";
  newItem.amount_on_card = 0;
  newItem.amount_via_terminal = 0;
  newItem.amount_as_cash = 0;
  newItem.currency = "UAH";
  newItem.happened_at = "";
  newItem.is_hidden = false;
  sectionStore.dialogs.create.isShown = true;
}

let newItem = reactive({
  title: "",
  amount_on_card: 0,
  amount_via_terminal: 0,
  amount_as_cash: 0,
  currency: "",
  happened_at: "",
  is_hidden: false
});

const setCurrentDate = () => {
  newItem.happened_at = moment().format('DD/MM/YYYY HH:mm');
}
</script>

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
          <q-icon name="recycling" color="black" size="md" class="q-mr-sm"/>
          Витрата
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="sectionStore.create(newItem)">
        <q-card-section
          style="max-height: 50vh; width: 400px"
          class="scroll q-pt-md row q-col-gutter-sm"
        >
          <q-input
            class="col-12"
            outlined
            v-model="newItem.title"
            autofocus
            label="Назва"
            :rules="[
                (val) => (val !== null && val !== '') || 'Введіть назву',
                (val) => val.length <= 255 || 'Не більше 255 символів',
              ]"
          />
          <q-select
            hide-dropdown-icon
            outlined
            v-model="newItem.currency"
            label="Валюта"
            :options="['UAH', 'USD', 'EUR']"
            class="col-12"
          />
          <q-input
            outlined
            class="col-4"
            v-model="newItem.amount_on_card"
            label="Картка"
            type="number"
            step="0.01"
            :rules="[
                (val) => val >= 0 || 'Не менше 0',
              ]"
          />
          <q-input
            outlined
            class="col-4"
            v-model="newItem.amount_via_terminal"
            label="Рахунок"
            type="number"
            step="0.01"
            :rules="[
                (val) => val >= 0 || 'Не менше 0',
              ]"
          />
          <q-input
            outlined
            class="col-4"
            v-model="newItem.amount_as_cash"
            label="Готівка"
            type="number"
            step="0.01"
            :rules="[
                (val) => val >= 0 || 'Не менше 0',
              ]"
          />
          <DateTimeInputComponent label="Дата витрати" class="full-width" v-model="newItem.happened_at" use-rules>
          </DateTimeInputComponent>
          <div class="row full-width">
            <q-checkbox v-if="appStore.allowenses.isValidFor('hide', 'spends')"
                        v-model="newItem.is_hidden"
                        label="Приховано"
            />
            <q-space></q-space>
            <q-btn @click="setCurrentDate" color="primary">Встановити дату</q-btn>
          </div>
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

<style scoped></style>
