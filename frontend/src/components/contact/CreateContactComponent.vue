<script setup>
import {computed, reactive} from "vue";
import {useContactStore} from "stores/contactStore";
const sectionStore = useContactStore();
import parsePhoneNumberFromString, {AsYouType} from "libphonenumber-js";
function showCreateDialog() {
  newItem.name = "";
  newItem.phone = "";
  newItem.email = "";
  newItem.address = "";
  newItem.additional_info = "";
  newItem.preferred_platforms = [];
  sectionStore.dialogs.create.isShown = true;
}

let newItem = reactive({
  name: "",
  phone: "",
  email: "",
  address: "",
  additional_info: "",
  preferred_platforms: []
});

const phoneValidationRule = (val) => {
  let message = "Введіть коректний номер телефону";

  let parsedPhone = parsePhoneNumberFromString("+" + val);
  if (parsedPhone) {
    return parsedPhone.isValid() || message;
  }

  return message;
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
          <q-icon name="contact_page" color="black" size="md" class="q-mr-sm"/>
          Контакт
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-form @submit="sectionStore.create(newItem)">
        <q-card-section
          style="max-height: 70vh; width: 450px"
          class="scroll q-mt-xs row q-col-gutter-sm"
        >
          <q-input
            class="col-12"
            outlined
            v-model="newItem.name"
            autofocus
            label="Ім'я"
            lazy-rules
            :rules="[
                (val) => (val !== null && val !== '') || 'Введіть ім\'я',
                (val) => val.length <= 255 || 'Не більше 255 символів',
              ]"
          />

          <q-input
            outlined
            class="col-6 q-mb-sm"
            v-model="newItem.phone"
            label="Телефон: 380..."
            mask="+###############"
            unmasked-value
            type="tel"
            lazy-rules
            :rules="[
                (val) => !val || phoneValidationRule(val)
              ]"
          />

          <q-input
            outlined
            class="col-6 q-mb-sm"
            v-model="newItem.email"
            label="Email"
            type="email"
            lazy-rules
            :rules="[
                (val) => !val || val.length <= 150 || 'Не більше 150 символів',
                (val) => !val || /.+@.+\..+/.test(val) || 'Введіть коректний email',
              ]"
          />

          <q-input
            outlined
            class="col-12"
            v-model="newItem.address"
            label="Адреса"
            type="text"
            lazy-rules
            :rules="[
                (val) => !val || val.length <= 255 || 'Не більше 255 символів',
              ]"
          />

          <q-input
            outlined
            class="col-12"
            v-model="newItem.additional_info"
            label="Додаткова інформація"
            type="textarea"
            autogrow
            lazy-rules
            :rules="[
                (val) => !val || val.length <= 255 || 'Не більше 255 символів',
              ]"
          />

          <div class="text-h6">Бажані платформи зв'язку</div>
          <q-field
            stack-label
            :rules="[
              (val) => (Array.isArray(val) && val.length > 0) || 'Виберіть хоча б одну платформу',
            ]"
            :model-value="newItem.preferred_platforms"
            borderless
          >
            <template v-slot:control>
              <div class="row q-gutter-sm">
                <q-checkbox v-model="newItem.preferred_platforms" val="call" label="Дзвінок" color="primary" />
                <q-checkbox v-model="newItem.preferred_platforms" val="sms" label="SMS" color="primary" />
                <q-checkbox v-model="newItem.preferred_platforms" val="email" label="Email" color="primary" />
                <q-checkbox v-model="newItem.preferred_platforms" val="telegram" label="Telegram" color="blue" />
                <q-checkbox v-model="newItem.preferred_platforms" val="viber" label="Viber" color="purple" />
                <q-checkbox v-model="newItem.preferred_platforms" val="whatsapp" label="Whatsapp" color="green" />
                <q-checkbox v-model="newItem.preferred_platforms" val="instagram" label="Instagram" color="pink" />
                <q-checkbox v-model="newItem.preferred_platforms" val="other" label="Інша" color="cyan" />
              </div>
            </template>
          </q-field>

        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn flat color="black" v-close-popup><b>Відміна</b></q-btn>
          <q-btn
            flat
            color="primary"
            type="submit"
            :loading="sectionStore.dialogs.create.isLoading"
          ><b>Створити</b></q-btn>
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<style scoped></style>
