import { createRouter, createWebHistory } from 'vue-router'
import LoginView from './views/LoginView.vue'
import CalendarView from './views/CalendarView.vue'
import AttackEditView from './views/AttackEditView.vue'
import { ensureMe } from './session'

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: LoginView },
    { path: '/calendar', component: CalendarView },
    { path: '/attack/:date(\\d{4}-\\d{2}-\\d{2})', component: AttackEditView, props: true },
  ],
})

router.beforeEach(async (to) => {
  const user = await ensureMe()

  if (to.path === '/') {
    if (user) return '/calendar'
    return true
  }

  if (!user) return '/'
  return true
})

