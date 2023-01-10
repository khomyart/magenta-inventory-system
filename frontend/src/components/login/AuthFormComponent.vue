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
      v-model="authInfo.email"
    />
    <q-input
      filled
      square
      v-model="authInfo.password"
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
      <q-btn label="Увійти" style="width: 50%" type="submit" color="primary" />
    </div>
  </q-form>
</template>
<script setup>
import { ref, reactive } from "vue";
import { useQuasar } from "quasar";
import { api } from "src/boot/axios";
const $q = useQuasar();

let isPwd = ref(true);
let authInfo = reactive({
  email: "",
  password: "",
});
function onSubmit() {
  api
    .post("/authentication", JSON.stringify(authInfo))
    .then((response) => {
      console.log(response);
    })
    .catch(() => {
      $q.notify({
        position: "top",
        color: "negative",
        message: "Невірні данні авторизації",
        group: false,
      });
    });
}
</script>
<style scoped></style>
