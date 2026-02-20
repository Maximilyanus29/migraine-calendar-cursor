import axios from 'axios'

export const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
  },
})

export async function getMe() {
  const { data } = await api.get('/me')
  return data.user
}

export async function login(email, password) {
  const { data } = await api.post('/login', { email, password })
  return data.user
}

export async function logout() {
  await api.post('/logout', {})
}

export async function listAttacksByMonth(month) {
  const { data } = await api.get('/attacks', { params: { month } })
  return data.attacks
}

export async function getAttackByDate(date) {
  const { data } = await api.get(`/attacks/${date}`)
  return data.attack
}

export async function getTemplate(date) {
  const { data } = await api.get('/attacks/template', { params: { date } })
  return data.template
}

export async function upsertAttack(date, payload) {
  const { data } = await api.put(`/attacks/${date}`, payload)
  return data.attack
}

export async function deleteAttack(date) {
  await api.delete(`/attacks/${date}`)
}

