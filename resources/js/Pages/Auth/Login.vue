<template>
  <AuthLayout>
    <h1 class="text-3xl text-[#02B3DF] mb-4"> Login</h1>
    <form @submit.prevent="login">
      <InputText>Email</InputText>
      <InputText>Password</InputText>
      <div class="flex w-full items-center justify-between">
        <ButtonPrimary>Login</ButtonPrimary>
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
import ButtonPrimary from '../../components/widgets/Button/ButtonPrimary.vue';

export default {
  components: {
    Head,
    AuthLayout,
    InputText,
    BaseButton,
    ButtonPrimary
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
