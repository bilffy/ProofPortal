<template>
  <AuthLayout>
    <!-- <Head title="Login" /> -->
    <!-- <form class="mt-8 bg-white rounded-lg shadow-xl overflow-hidden" @submit.prevent="login">
      <div class="px-10 py-12">
        <h1 class="text-center text-3xl font-bold">Welcome Back!</h1>
        <div class="mt-6 mx-auto w-24 border-b-2" />
        <text-input v-model="form.email" :error="form.errors.email" class="mt-10" label="Email" type="email" autofocus autocapitalize="off" />
        <text-input v-model="form.password" :error="form.errors.password" class="mt-6" label="Password" type="password" />
        <label class="flex items-center mt-6 select-none" for="remember">
          <input id="remember" v-model="form.remember" class="mr-1" type="checkbox" />
          <span class="text-sm">Remember Me</span>
        </label>
      </div>
      <div class="flex px-10 py-4 bg-gray-100 border-t border-gray-100">
        <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Login</loading-button>
      </div>
    </form> -->
    <h1 class="text-3xl text-[#02B3DF] mb-4"> Login</h1>
    <form @submit.prevent="login">
      <InputText>Email</InputText>
      <InputText>Password</InputText>
      <div class="flex w-full items-center justify-between">
        <BaseButton>Login</BaseButton>
        Forgot Password
      </div>
    </form>
  </AuthLayout>
</template>

<script lang="ts">
import { Head } from '@inertiajs/vue3'
import AuthLayout from '../../Shared/AuthLayout.vue';
import InputText from '../../components/widgets/InputText.vue';
import BaseButton from '../../components/widgets/Button/BaseButton.vue';

export default {
  components: {
    Head,
    AuthLayout,
    InputText,
    BaseButton
  },
  data() {
    return {
      form: this.$inertia.form({
        email: 'johndoe@example.com',
        password: 'secret',
        remember: false,
      }),
    }
  },
  methods: {
    login() {
      this.form.post('/login')
    },
  },
}
</script>
