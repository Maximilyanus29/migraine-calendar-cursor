import { ref } from 'vue'
import { getMe } from './api'

export const sessionUser = ref(undefined) // undefined = unknown, null = not logged, object = logged

export async function ensureMe() {
  if (sessionUser.value !== undefined) return sessionUser.value
  try {
    const user = await getMe()
    sessionUser.value = user
    return user
  } catch (e) {
    sessionUser.value = null
    return null
  }
}

export function setUser(user) {
  sessionUser.value = user
}

