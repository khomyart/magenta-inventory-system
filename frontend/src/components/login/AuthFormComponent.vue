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
        :loading="isLoading"
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
import { useRouter } from "vue-router";

const $q = useQuasar();
const store = useUserStore();
const router = useRouter();

let notification = null;

let isPwd = ref(true);
let isLoading = ref(false);
let userData = reactive({
  email: "",
  password: "",
});

function onSubmit() {
  isLoading.value = true;
  store
    .login(userData)
    .then((response) => {
      sessionStorage.setItem("email", response.data.user.email);
      sessionStorage.setItem("name", response.data.user.name);
      sessionStorage.setItem("token", response.data.auth.token);
      sessionStorage.setItem("expired_at", response.data.auth.expired_at);
      router.push("/items");
      if (notification != null) {
        notification();
      }
    })
    .catch((error) => {
      notification = $q.notify({
        position: "top",
        color: "negative",
        message: error.response.data,
        group: true,
      });
    })
    .finally(() => {
      isLoading.value = false;
    });
}
</script>
<style scoped></style>
