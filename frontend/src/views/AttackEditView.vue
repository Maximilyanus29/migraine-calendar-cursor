<template>
  <div class="row-between" style="margin-bottom: 14px;">
    <div>
      <div style="font-size: 20px; font-weight: 780;">Приступ</div>
      <div class="muted" style="font-size: 13px;">{{ date }}</div>
    </div>
    <div class="row">
      <button @click="goBack">← К календарю</button>
      <button v-if="loaded && exists" class="danger" @click="onDelete" :disabled="saving">Удалить</button>
      <button class="primary" @click="onSave" :disabled="saving || !loaded">
        {{ saving ? 'Сохраняем…' : 'Сохранить' }}
      </button>
    </div>
  </div>

  <div class="card" style="display:flex; flex-direction:column; gap: 12px;">
    <div v-if="!loaded" class="muted">Загружаем…</div>

    <div v-else style="display:flex; flex-direction:column; gap: 12px;">
      <div class="row" style="align-items:flex-start;">
        <div style="flex:1;">
          <div class="muted" style="font-size: 12px; margin: 0 0 6px;">Начало</div>
          <input v-model="form.start_time" type="time" />
        </div>
        <div style="flex:1;">
          <div class="muted" style="font-size: 12px; margin: 0 0 6px;">Конец</div>
          <input v-model="form.end_time" type="time" />
        </div>
        <div style="flex:1;">
          <div class="muted" style="font-size: 12px; margin: 0 0 6px;">Боль (0–10)</div>
          <input v-model.number="form.pain_level" type="number" min="0" max="10" />
        </div>
      </div>

      <div>
        <div class="muted" style="font-size: 12px; margin: 0 0 6px;">Лекарства</div>
        <input v-model="form.medications" placeholder="Например: ибупрофен 200мг" />
      </div>

      <div>
        <div class="muted" style="font-size: 12px; margin: 0 0 6px;">Заметки</div>
        <textarea v-model="form.notes" placeholder="Триггеры, симптомы, что помогло…" />
      </div>

      <div v-if="hint" class="muted" style="font-size: 12px;">
        {{ hint }}
      </div>

      <div v-if="error" style="color: var(--danger); font-size: 13px;">
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { deleteAttack, getAttackByDate, getTemplate, upsertAttack } from '../api'

const props = defineProps({
  date: { type: String, required: true },
})

const router = useRouter()

const loaded = ref(false)
const saving = ref(false)
const exists = ref(false)
const error = ref('')
const hint = ref('')

const form = reactive({
  start_time: '',
  end_time: '',
  pain_level: null,
  medications: '',
  notes: '',
})

const date = computed(() => props.date)

function timeForInput(v) {
  if (!v) return ''
  return String(v).slice(0, 5) // HH:MM:SS -> HH:MM
}

function normalizePayload() {
  return {
    start_time: form.start_time || null,
    end_time: form.end_time || null,
    pain_level: form.pain_level === '' ? null : form.pain_level,
    medications: form.medications?.trim() || null,
    notes: form.notes?.trim() || null,
  }
}

async function load() {
  loaded.value = false
  error.value = ''
  hint.value = ''

  const attack = await getAttackByDate(date.value)
  if (attack) {
    exists.value = true
    form.start_time = timeForInput(attack.start_time)
    form.end_time = timeForInput(attack.end_time)
    form.pain_level = attack.pain_level ?? null
    form.medications = attack.medications ?? ''
    form.notes = attack.notes ?? ''
  } else {
    exists.value = false
    const tpl = await getTemplate(date.value)
    form.start_time = timeForInput(tpl.start_time)
    form.end_time = timeForInput(tpl.end_time)
    form.pain_level = tpl.pain_level ?? null
    form.medications = tpl.medications ?? ''
    form.notes = tpl.notes ?? ''
    hint.value = 'День пустой — подставили данные предыдущего приступа (если он был).'
  }

  loaded.value = true
}

async function onSave() {
  saving.value = true
  error.value = ''
  try {
    await upsertAttack(date.value, normalizePayload())
    exists.value = true
    await router.push('/calendar')
  } catch (e) {
    error.value = 'Не удалось сохранить. Проверьте поля и повторите.'
  } finally {
    saving.value = false
  }
}

async function onDelete() {
  if (!confirm('Удалить приступ за этот день?')) return
  saving.value = true
  error.value = ''
  try {
    await deleteAttack(date.value)
    await router.push('/calendar')
  } catch (e) {
    error.value = 'Не удалось удалить.'
  } finally {
    saving.value = false
  }
}

async function goBack() {
  await router.push('/calendar')
}

onMounted(load)
</script>

