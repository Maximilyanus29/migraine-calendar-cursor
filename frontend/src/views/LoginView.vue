<template>
  <div class="card" style="max-width: 520px; margin: 48px auto;">
    <div style="display:flex; flex-direction:column; gap:6px; margin-bottom: 14px;">
      <div style="font-size: 20px; font-weight: 750;">Вход</div>
      <div class="muted">Введите email и пароль, чтобы открыть календарь.</div>
    </div>

    <form @submit.prevent="onSubmit" style="display:flex; flex-direction:column; gap: 12px;">
      <div>
        <div class="muted" style="font-size: 12px; margin: 0 0 6px;">Email</div>
        <input v-model.trim="email" type="email" autocomplete="username" />
      </div>

      <div>
        <div class="muted" style="font-size: 12px; margin: 0 0 6px;">Пароль</div>
        <input v-model="password" type="password" autocomplete="current-password" />
      </div>

      <div v-if="error" style="color: var(--danger); font-size: 13px;">
        {{ error }}
      </div>

      <div class="row-between" style="margin-top: 4px;">
        <div class="muted" style="font-size: 12px;">
          Подсказка: создайте пользователя командой из README.
        </div>
        <button class="primary" :disabled="loading">
          {{ loading ? 'Входим…' : 'Войти' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { login } from '../api'
import { setUser } from '../session'

const router = useRouter()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function onSubmit() {
  error.value = ''
  loading.value = true
  try {
    const user = await login(email.value, password.value)
    setUser(user)
    await router.push('/calendar')
  } catch (e) {
    error.value = 'Неверный email или пароль'
  } finally {
    loading.value = false
  }
}
</script>

