<template>
  <q-form
    @submit.prevent="onSubmit"
    class="q-gutter-md"
    style="width: 400px; margin-left: 0px"
  >
    <q-input
      filled
      square
      class="q-mb-lg"
      type="email"
      label="Пошта"
      v-model="userData.email"
    />
    <q-input
      filled
      square
      v-model="userData.password"
      class="q-mb-lg"
      :type="isPwd ? 'password' : 'text'"
      label="Пароль"
    >
      <template v-slot:append>
        <q-icon
          :name="isPwd ? 'visibility_off' : 'visibility'"
          class="cursor-pointer"
          @click="isPwd = !isPwd"
        />
      </template>
    </q-input>
    <div class="col-12 flex justify-center">
      <q-btn
        label="Увійти"
        :loading="store.isLoading"
        style="width: 50%"
        type="submit"
        color="primary"
      />
    </div>
  </q-form>
</template>
<script setup>
import { ref, reactive } from "vue";
import { useQuasar } from "quasar";
import { useUserStore } from "src/stores/userStore";

const $q = useQuasar();
const store = useUserStore();

let isPwd = ref(true);
// let isLoading = ref(false);
let userData = reactive({
  email: "",
  password: "",
});

function onSubmit() {
  store.login(userData);
}
</script>
<style scoped></style>
