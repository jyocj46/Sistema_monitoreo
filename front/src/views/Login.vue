<template>
  <div class="d-flex align-items-center py-4 bg-body-tertiary vh-100">
    <main class="form-signin w-100 m-auto">
      <form @submit.prevent="handleLogin" class="needs-validation" novalidate>
        <div class="logo-container mb-4">
          <img
            src="../assets/images/Recurso 35@4x.png"
            alt="Logo de la aplicación"
            class="login-logo"
          />
        </div>
        <h1 class="h3 mb-3 fw-normal">Inicia sesión</h1>
        
        <div class="form-floating">
          <input
            type="email"
            class="form-control"
            id="floatingInput"
            placeholder="example@detpon.com"
            v-model="email"
            required
          />
          <label for="floatingInput">Correo electrónico</label>
        </div>
        
        <div class="form-floating">
          <input
            type="password"
            class="form-control"
            id="floatingPassword"
            placeholder="Password"
            v-model="password"
            required
          />
          <label for="floatingPassword">Contraseña</label>
        </div>

        <p v-if="errorMsg" class="text-danger mt-2">{{ errorMsg }}</p>

        <button class="btn btn-primary w-100 py-2 mt-3" type="submit" :disabled="loading">
          <span v-if="loading">Ingresando...</span>
          <span v-else>Iniciar sesión</span>
        </button>
        
      </form>
    </main>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
// 6. Asumiendo que moviste la lógica a 'src/api/auth.js'
import loginRequest from '../api/auth.js' 
import '../assets/login.css'; 

const router = useRouter()

// Estas refs ahora se llenarán gracias a v-model
const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMsg = ref('')

// Esta función ahora coincide con el @submit
async function handleLogin() {
  errorMsg.value = ''
  loading.value = true

  try {
    // Los .value se pasan correctamente a la función importada
    const data = await loginRequest(email.value, password.value)
    localStorage.setItem('auth_token', data.token)
    localStorage.setItem('user', JSON.stringify(data.user || {}))
    router.push({ name: 'dashboard' })
  } catch (err) {
    errorMsg.value = err.message || 'Error al iniciar sesión'
  } finally {
    loading.value = false
  }
}
</script>
