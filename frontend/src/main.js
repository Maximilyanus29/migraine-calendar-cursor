import { createApp } from 'vue'
import App from './App.vue'
import { router } from './router'
import dayjs from 'dayjs'
import 'dayjs/locale/ru'

import './style.css'

dayjs.locale('ru')

createApp(App).use(router).mount('#app')

