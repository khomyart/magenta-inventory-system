<template>
  <div
    class="list-item q-pa-sm q-pa-sm-md q-mt-sm column"
    :class="{
      'impossible-to-outcome':
        sectionStore.outcome.items[props.itemIndex].outcomeAmount >
          props.itemDetail.amount ||
        (sectionStore.outcome.items[props.itemIndex].reasonName === 'other' &&
          sectionStore.outcome.items[props.itemIndex].additionalReasonName ===
            ''),
    }"
  >
    <div class="list-item-body-main">
      <div class="list-item-body-left">
        <div class="list-item-description-image-holder">
          <img
            v-if="props.itemDetail.image != null"
            class="list-item-description-image"
            :src="`${props.imagesStoreUrl}/${props.itemDetail.image}`"
          />
        </div>
        <div class="list-item-description-text-holder q-ml-md">
          <div class="title">
            {{ props.itemDetail.title }}
          </div>
          <div class="article">
            <div class="article-text q-mr-xs">
              {{ props.itemDetail.article }}
            </div>
            <div
              v-if="props.itemDetail.color_value"
              class="article-color q-mr-xs"
              :style="{ backgroundColor: props.itemDetail.color_value }"
            >
              <span :style="{ color: props.itemDetail.text_color_value }">{{
                props.itemDetail.color_article
              }}</span>
            </div>
            <div v-if="props.itemDetail.size" class="size">
              {{ props.itemDetail.size }}
            </div>
          </div>
          <div class="available">Наявність: {{ props.itemDetail.amount }}</div>
        </div>
      </div>
      <div class="list-item-body-right q-ml-sm-sm q-ml-none">
        <q-btn icon="more_vert" rounded flat>
          <q-tooltip
            class="bg-black text-body2"
            anchor="bottom middle"
            self="top middle"
            :offset="[0, 5]"
          >
            Деталі
          </q-tooltip>
          <q-menu :offset="[0, 5]" anchor="top middle" self="bottom middle">
            <q-list style="min-width: 100px">
              <q-item clickable v-close-popup @click="showAmountDialog">
                <div class="context-menu-item">
                  <q-icon size="sm" name="edit" left></q-icon>
                  <span>Вказати кількість</span>
                </div>
              </q-item>
              <q-item clickable v-close-popup @click="showReasonDialog">
                <div class="context-menu-item">
                  <q-icon size="sm" name="edit_note" left></q-icon>
                  <span>Вказати причину</span>
                </div>
              </q-item>
              <q-item
                clickable
                v-close-popup
                @click="removeItem(props.itemIndex)"
              >
                <div class="context-menu-item">
                  <q-icon size="sm" name="delete" left></q-icon>
                  <span>Видалити</span>
                </div>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </div>
    </div>
    <q-separator class="q-mt-sm q-mt-sm-md"></q-separator>
    <div class="list-item-body-detail q-mt-sm">
      <div
        :class="{
          'warning-text':
            sectionStore.outcome.items[props.itemIndex].outcomeAmount >
              props.itemDetail.amount ||
            sectionStore.outcome.items[props.itemIndex].outcomeAmount === '',
        }"
      >
        <q-tooltip
          v-if="
            sectionStore.outcome.items[props.itemIndex].outcomeAmount >
            props.itemDetail.amount
          "
          class="bg-black text-body2"
          anchor="bottom start"
          self="top start"
          :offset="[5, 3]"
        >
          Списання перебільшує наявність
        </q-tooltip>
        Кількість для списання:
        {{ outcomeAmountOfItem }}
      </div>
      <div
        class="q-mt-sm"
        :class="{
          'warning-text':
            sectionStore.outcome.items[props.itemIndex].reasonName ===
              'other' &&
            sectionStore.outcome.items[props.itemIndex].additionalReasonName ===
              '',
        }"
      >
        <q-tooltip
          v-if="
            sectionStore.outcome.items[props.itemIndex].reasonName ===
              'other' &&
            sectionStore.outcome.items[props.itemIndex].additionalReasonName ===
              ''
          "
          class="bg-black text-body2"
          anchor="bottom start"
          self="top start"
          :offset="[5, 3]"
        >
          Вкажіть причину списання
        </q-tooltip>
        Причина: {{ outcomeReasonLabel }} {{ reasonDetail }}
      </div>
    </div>
  </div>
  <div class="validator-container col-12 q-mt-none q-ml-lg q-pt-none">
    <q-input
      class="col-12 validator q-mb-none q-pb-none"
      :reactive-rules="true"
      :rules="[
        () =>
          sectionStore.outcome.items[props.itemIndex].outcomeAmount <=
            props.itemDetail.amount || 'Списання перебільшує наявність',
        () =>
          !(
            sectionStore.outcome.items[props.itemIndex].reasonName ===
              'other' &&
            sectionStore.outcome.items[props.itemIndex].additionalReasonName ===
              ''
          ) || 'Вкажіть причину списання',
      ]"
    ></q-input>
  </div>

  <!-- AMOUNT DIALOG -->
  <q-dialog v-model="showEditAmountDialog">
    <q-card style="width: 100vw; max-width: 400px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="edit" color="black" size="md" class="q-mr-sm" />
          Кількість
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit.prevent="submitAmount">
        <q-card-section class="col-12">
          <q-input
            outlined
            v-model="tempVariables.outcomeAmount"
            label="Вкажіть кількість списання"
            mask="######"
            :rules="[
              (val) =>
                val <= props.itemDetail.amount ||
                'Списання перебільшує наявність',
              (val) => val != '' || 'Вкажіть кількість',
              (val) => val > 0 || 'Не менше 1',
            ]"
          ></q-input>
          <div class="col-12 text-right">
            Наявність: {{ props.itemDetail.amount }}
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn flat color="primary" type="submit"><b>Зберегти</b></q-btn>
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- REASON DIALOG -->
  <q-dialog v-model="showEditReasonDialog">
    <q-card style="width: 100vw; max-width: 500px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="edit" color="black" size="md" class="q-mr-sm" />
          Причина
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit.prevent="submitReasonDialog">
        <q-card-section class="col-12">
          <div class="row q-col-gutter-md">
            <q-select
              v-model="tempVariables.reasonName"
              :options="reasons"
              label="Оберіть причину"
              emit-value
              map-options
              outlined
              class="col-12 col-sm-6 q-pb-sm"
            />
            <q-input
              outlined
              class="col-12 col-sm-6"
              label="Назва причини"
              v-model="tempVariables.additionalReasonName"
              v-if="tempVariables.reasonName === 'other'"
              :rules="[
                (val) => val.length > 0 || 'Введіть назву причини',
                (val) => val.length < 255 || 'Не більше 255 символів',
              ]"
            />
            <q-input
              outlined
              label="Деталі"
              v-model="tempVariables.reasonDetail"
              :class="{
                'col-12 col-sm-6': tempVariables.reasonName != 'other',
                'col-12 q-pt-xs col-sm-12 q-pt-sm-sm':
                  tempVariables.reasonName === 'other',
              }"
            />
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn flat color="primary" type="submit"><b>Зберегти</b></q-btn>
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
<script setup>
import { ref, reactive } from "vue";
import { useItemStore } from "src/stores/itemStore";
import { computed, watch } from "vue";

const sectionStore = useItemStore();
const props = defineProps(["itemDetail", "itemIndex", "imagesStoreUrl"]);
const reasons = [
  {
    label: "Продаж",
    value: "sell",
  },
  {
    label: "Утилізація",
    value: "recycle",
  },
  {
    label: "Інша",
    value: "other",
  },
];

let showEditAmountDialog = ref(false);
let showEditReasonDialog = ref(false);
let tempVariables = reactive({
  reasonName: "sell",
  additionalReasonName: "",
  reasonDetail: "",
  outcomeAmount: "",
});

function removeItem(itemIndex) {
  sectionStore.outcome.items.splice(itemIndex, 1);
}

function showAmountDialog() {
  showEditAmountDialog.value = true;
  tempVariables.outcomeAmount =
    sectionStore.outcome.items[props.itemIndex].outcomeAmount;
}
function submitAmount() {
  sectionStore.outcome.items[props.itemIndex].outcomeAmount =
    tempVariables.outcomeAmount;
  showEditAmountDialog.value = false;
  sectionStore.outcome.items[props.itemIndex].outcomeAmountCustomMode = true;
}

function showReasonDialog() {
  showEditReasonDialog.value = true;
  tempVariables.reasonName =
    sectionStore.outcome.items[props.itemIndex].reasonName;
  tempVariables.additionalReasonName =
    sectionStore.outcome.items[props.itemIndex].additionalReasonName;
  tempVariables.reasonDetail =
    sectionStore.outcome.items[props.itemIndex].reasonDetail;
}
function submitReasonDialog() {
  sectionStore.outcome.items[props.itemIndex].reasonName =
    tempVariables.reasonName;
  sectionStore.outcome.items[props.itemIndex].additionalReasonName =
    tempVariables.additionalReasonName;
  sectionStore.outcome.items[props.itemIndex].reasonDetail =
    tempVariables.reasonDetail;

  showEditReasonDialog.value = false;
  sectionStore.outcome.items[props.itemIndex].reasonCustomMode = true;
}

const outcomeAmountOfItem = computed(() => {
  let outcomeAmount = sectionStore.outcome.items[props.itemIndex].outcomeAmount;
  return outcomeAmount === "" ? "-" : outcomeAmount;
});

const outcomeReasonLabel = computed(() => {
  let label = "";
  let reason = sectionStore.outcome.items[props.itemIndex].reasonName;

  if (reason != "other") {
    switch (reason) {
      case "sell":
        label = "Продаж";
        break;
      case "recycle":
        label = "Утилізація";
        break;
      default:
        break;
    }
    return label;
  }

  let additionalReasonName =
    sectionStore.outcome.items[props.itemIndex].additionalReasonName;

  label = additionalReasonName === "" ? "-" : additionalReasonName;

  return label;
});

const reasonDetail = computed(() => {
  let reasonDetail = sectionStore.outcome.items[props.itemIndex].reasonDetail;
  return reasonDetail === "" ? "" : `(${reasonDetail})`;
});

watch(
  () => sectionStore.outcome.outcomeAmount,
  (newValue) => {
    if (!sectionStore.outcome.items[props.itemIndex].outcomeAmountCustomMode) {
      sectionStore.outcome.items[props.itemIndex].outcomeAmount = newValue;
    }
  }
);

watch(
  () => sectionStore.outcome.reasonName,
  (newValue) => {
    if (!sectionStore.outcome.items[props.itemIndex].reasonCustomMode) {
      sectionStore.outcome.items[props.itemIndex].reasonName = newValue;
    }
  }
);

watch(
  () => sectionStore.outcome.additionalReasonName,
  (newValue) => {
    if (!sectionStore.outcome.items[props.itemIndex].reasonCustomMode) {
      sectionStore.outcome.items[props.itemIndex].additionalReasonName =
        newValue;
    }
  }
);

watch(
  () => sectionStore.outcome.reasonDetail,
  (newValue) => {
    if (!sectionStore.outcome.items[props.itemIndex].reasonCustomMode) {
      sectionStore.outcome.items[props.itemIndex].reasonDetail = newValue;
    }
  }
);
</script>
<style scoped>
.list-item {
  height: fit-content;
  border-radius: 5px;
  border: 1px solid rgba(96, 0, 92, 0.18);
  transition: all 0.2s ease-in-out;
}
.impossible-to-outcome {
  border: 1px solid rgba(218, 84, 84, 0.486);
  box-shadow: 0px 0px 8px rgba(253, 0, 0, 0.315);
}
.warning-text {
  color: rgb(218, 84, 84);
}

.validator-container {
  height: 18px;
  position: relative;
  overflow: hidden;
}
.validator {
  position: relative;
  top: -1px;
  transform: translate(0, -100%);
}
.list-item-body-main {
  width: 100%;
  display: flex;
  flex-direction: row;
  align-items: center;
}
.list-item-body-left {
  display: flex;
  flex-direction: row;
  flex: 1 1 auto;
  min-width: 0;
}
.list-item-body-right {
  display: flex;
  flex-direction: row;
  flex: 0 0 auto;
  height: fit-content;
}
.list-item-body-right button {
  width: 42px;
  height: 42px;
  margin-left: 5px;
}
.list-item-description-image-holder {
  min-width: 75px;
  min-height: 75px;
  width: 75px;
  height: 75px;
  overflow: hidden;
  display: flex;
  justify-content: center;
  border-radius: 5px;
  background-color: rgb(217, 217, 205);
  flex: 0 0 75px;
}
.list-item-description-image {
  height: 100%;
  width: auto;
}
.list-item-description-text-holder {
  display: flex;
  flex: 1 1 auto;
  min-width: 0px;
  flex-direction: column;
  justify-content: space-between;
}
.title {
  width: 100%;
  height: fit-content;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.article {
  display: flex;
  flex-direction: row;
  overflow: hidden;
}
.available {
  width: 100%;
  height: fit-content;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.article-color {
  width: fit-content;
  padding: 0 10px;
  height: 20px;
  border-radius: 3px;
  font-size: 0.8em;
  display: flex;
  align-items: center;
}

.buttons-holder {
  height: fit-content;
}
.buttons-holder button {
  width: 42px;
  height: 42px;
}
</style>
