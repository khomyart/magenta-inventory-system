<template>
  <q-form
    @submit.prevent="onSubmit"
    class="q-gutter-md"
    style="width: 400px; margin-left: 0px"
  >
    <q-input
      outlined
      class="q-mb-lg"
      type="email"
      label="Пошта"
      v-model="userData.email"
    />
    <q-input
      outlined
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
        :loading="store.data.isLoading"
        style="width: 50%"
        type="submit"
        color="primary"
      />
    </div>
  </q-form>

  <q-dialog
    v-model="appConfigStore.errors.responses[422].isShown"
    transition-show="scale"
    transition-hide="scale"
  >
    <q-card style="width: 350px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon size="md" class="q-mr-sm" name="error" color="red"></q-icon>
          Помилка
        </div>
      </q-card-section>
      <q-separator></q-separator>

      <q-card-section class="q-pt-md">
        {{ appConfigStore.errors.responses[422].text }}
      </q-card-section>

      <q-separator></q-separator>
      <q-card-actions align="right">
        <q-btn flat color="black" v-close-popup>Гаразд</q-btn>
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
<script setup>
import { ref, reactive, watch } from "vue";
import { useAppConfigStore } from "src/stores/appConfigStore";
import { useUserStore } from "src/stores/userStore";
import { useRouter } from "vue-router";

const router = useRouter();
const store = useUserStore();
const appConfigStore = useAppConfigStore();

let isPwd = ref(true);
let userData = reactive({
  email: "",
  password: "",
});

function onSubmit() {
  store.login(userData);
}

watch(
  () => store.data.isLoginSuccesed,
  (newValue) => {
    if (newValue == true) {
      router.push("/dashboard");
      store.data.isLoginSuccesed = false;
    }
  }
);
</script>
<style scoped></style>
