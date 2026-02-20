<template>
  <div class="row-between" style="margin-bottom: 14px;">
    <div>
      <div style="font-size: 20px; font-weight: 780;">Календарь</div>
      <div class="muted" style="font-size: 13px;">{{ monthLabel }}</div>
    </div>
    <div class="row">
      <button @click="prevMonth">←</button>
      <button @click="goToday">Сегодня</button>
      <button @click="nextMonth">→</button>
      <button class="danger" @click="onLogout">Выйти</button>
    </div>
  </div>

  <div class="card">
    <div class="grid" style="margin-bottom: 10px;">
      <div v-for="d in weekDays" :key="d" class="muted" style="font-size: 12px; padding: 0 10px;">
        {{ d }}
      </div>
    </div>

    <div class="grid">
      <a
        v-for="cell in cells"
        :key="cell.date"
        class="day"
        :class="{ today: cell.isToday }"
        :href="`/attack/${cell.date}`"
        @click.prevent="openDate(cell.date)"
        :style="cell.isOutside ? 'opacity:0.45' : ''"
      >
        <div class="row-between">
          <div class="num">{{ cell.day }}</div>
          <div v-if="cell.hit" class="badge hit">приступ · {{ cell.hit.pain_level ?? '—' }}</div>
          <div v-else class="badge">нет</div>
        </div>
        <div class="muted" style="font-size: 12px;">
          {{ cell.date }}
        </div>
      </a>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import dayjs from 'dayjs'
import { useRouter } from 'vue-router'
import { listAttacksByMonth, logout } from '../api'
import { setUser } from '../session'

const router = useRouter()

const current = ref(dayjs().startOf('month'))
const attacksMap = ref(new Map())

const weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']

const monthLabel = computed(() => current.value.format('MMMM YYYY'))
const monthParam = computed(() => current.value.format('YYYY-MM'))

const cells = computed(() => {
  const start = current.value.startOf('month')
  const end = current.value.endOf('month')

  // Сделаем сетку Пн..Вс: dayjs().day() => 0=Вс..6=Сб
  const startDow = (start.day() + 6) % 7 // 0=Пн..6=Вс
  const gridStart = start.subtract(startDow, 'day')

  const endDow = (end.day() + 6) % 7
  const gridEnd = end.add(6 - endDow, 'day')

  const out = []
  let d = gridStart
  const today = dayjs().format('YYYY-MM-DD')
  while (d.isBefore(gridEnd) || d.isSame(gridEnd, 'day')) {
    const date = d.format('YYYY-MM-DD')
    out.push({
      date,
      day: d.date(),
      isToday: date === today,
      isOutside: d.month() !== start.month(),
      hit: attacksMap.value.get(date) ?? null,
    })
    d = d.add(1, 'day')
  }
  return out
})

async function load() {
  const list = await listAttacksByMonth(monthParam.value)
  const m = new Map()
  for (const a of list) m.set(a.attack_date, a)
  attacksMap.value = m
}

function prevMonth() {
  current.value = current.value.subtract(1, 'month').startOf('month')
  load()
}

function nextMonth() {
  current.value = current.value.add(1, 'month').startOf('month')
  load()
}

function goToday() {
  current.value = dayjs().startOf('month')
  load()
}

async function openDate(date) {
  await router.push(`/attack/${date}`)
}

async function onLogout() {
  await logout()
  setUser(null)
  await router.push('/')
}

onMounted(load)
</script>

