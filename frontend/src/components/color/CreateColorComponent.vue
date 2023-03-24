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
          <q-icon name="palette" color="black" size="md" class="q-mr-sm" />
          Колір
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="sectionStore.create(newItem)">
        <q-card-section
          style="max-height: 50vh; width: 95vw; max-width: 450px"
          class="scroll col-12 q-pt-lg"
        >
          <div class="row q-col-gutter-lg q-mb-lg">
            <div class="col-6">
              <div
                class="flex-center col-12"
                :style="{
                  height: '56px',
                  fontSize: '20px',
                  padding: '5px',
                  fontWeight: 'bold',
                  borderRadius: '4px',
                  border: '1px solid rgba(0, 0, 0, 0.18)',
                  backgroundColor: newItem.value,
                  color: newItem.text_color_value,
                }"
              >
                <q-tooltip class="bg-black text-body2" :offset="[0, 5]">
                  {{ colorTooltip }}
                </q-tooltip>
                {{ newItem.article }}
              </div>
            </div>
            <div class="col-6">
              <div class="row q-col-gutter-lg">
                <div class="col-6">
                  <q-btn
                    style="height: 56px"
                    :color="`${
                      newItem.text_color_value == '#000000' ? 'purple' : 'white'
                    }`"
                    :text-color="`${
                      newItem.text_color_value == '#000000' ? 'white' : 'black'
                    }`"
                    @click="newItem.text_color_value = '#000000'"
                    >Чорний</q-btn
                  >
                </div>
                <div class="col-6">
                  <q-btn
                    style="width: 100%; height: 56px"
                    :color="`${
                      newItem.text_color_value == '#ffffff' ? 'purple' : 'white'
                    }`"
                    :text-color="`${
                      newItem.text_color_value == '#ffffff' ? 'white' : 'black'
                    }`"
                    @click="newItem.text_color_value = '#ffffff'"
                    >Білий</q-btn
                  >
                </div>
              </div>
            </div>
          </div>
          <div class="row q-mb-sm q-col-gutter-lg">
            <q-input
              outlined
              v-model="newItem.value"
              :rules="['anyColor']"
              class="col-6"
              label="Головний колір"
            >
              <template v-slot:append>
                <q-icon name="colorize" class="cursor-pointer">
                  <q-popup-proxy
                    cover
                    transition-show="scale"
                    transition-hide="scale"
                  >
                    <q-color v-model="newItem.value" />
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>

            <q-input
              outlined
              v-model="newItem.text_color_value"
              :rules="['anyColor']"
              label="Колір артиклю"
              class="col-6"
            >
              <template v-slot:append>
                <q-icon name="colorize" class="cursor-pointer">
                  <q-popup-proxy
                    cover
                    transition-show="scale"
                    transition-hide="scale"
                  >
                    <q-color v-model="newItem.text_color_value" />
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>
          </div>
          <q-input
            class="q-mb-sm"
            outlined
            v-model="newItem.article"
            label="Артикль"
            :rules="[
              (val) => (val !== null && val !== '') || 'Введіть артикль',
              (val) => val.length <= 10 || 'Не більше 10 символів',
            ]"
          />
          <q-input
            outlined
            v-model="newItem.description"
            label="Опис"
            :rules="[
              (val) => (val !== null && val !== '') || 'Введіть опис',
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
import { reactive, computed } from "vue";
import { useColorStore } from "src/stores/colorStore";
const sectionStore = useColorStore();

function showCreateDialog() {
  newItem.value = "";
  newItem.description = "";
  sectionStore.dialogs.create.isShown = true;
}

let newItem = reactive({
  value: "",
  article: "",
  description: "",
  text_color_value: "",
});

const colorTooltip = computed(() => {
  return newItem.description === ""
    ? "Введіть опис кольору"
    : newItem.description;
});
</script>
<style scoped></style>
