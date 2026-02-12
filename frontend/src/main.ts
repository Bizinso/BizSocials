import { createApp } from 'vue'
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
import App from './App.vue'
import router from './router'
import { setupPrimeVue } from './plugins/primevue'
import { setupDayjs } from './plugins/dayjs'
import './assets/css/main.css'
import 'primeicons/primeicons.css'

const app = createApp(App)

const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)
app.use(pinia)

setupPrimeVue(app)
setupDayjs()

app.use(router)

app.mount('#app')
