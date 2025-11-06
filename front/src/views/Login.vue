<!-- // src/views/Login.vue -->
<template>
  <div class="login-container">
    <div class="login-background">
      <div class="login-card">
        <!-- Header con Logo -->
        <div class="login-header text-center mb-4">
          <div class="logo-container mb-3">
            <img
              src="../assets/images/Recurso 33@4x.png"
              alt="Logo de la aplicación"
              class="login-logo"
            />
          </div>
          <h1 class="login-title">Bienvenido</h1>
          <p class="login-subtitle">Inicia sesión con tu usuario</p>
        </div>

        <!-- Formulario -->
        <form @submit.prevent="handleLogin" class="login-form">
          <div class="mb-3">
            <label for="floatingInput" class="form-label">Correo electrónico</label>
            <input
              type="email"
              class="form-control form-control-lg"
              id="floatingInput"
              placeholder="example@detpon.com"
              v-model="email"
              @input="errorMsg = ''"  required
            />
          </div>
          
          <div class="mb-3">
            <label for="floatingPassword" class="form-label">Contraseña</label>
            <input
              type="password"
              class="form-control form-control-lg"
              id="floatingPassword"
              placeholder="Ingresa tu contraseña"
              v-model="password"
              @input="errorMsg = ''"  required
            />
          </div>

          <!-- Mensaje de error -->
          <div v-if="errorMsg" class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ errorMsg }}
            <button type="button" class="btn-close" @click="errorMsg = ''"></button>
          </div>

          <!-- Botón de login -->
          <button class="btn btn-login w-100 py-3 mt-3" type="submit" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            {{ loading ? 'Ingresando...' : 'Iniciar sesión' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import loginRequest from '../api/auth.js' 
import '../assets/login.css'; 

const router = useRouter()
const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMsg = ref('')

async function handleLogin() {
  loading.value = true

  try {
    const data = await loginRequest(email.value, password.value)

    // =======================================================
    // ¡¡ESTE ES EL SEGURO QUE ARREGLARÁ TU PROBLEMA!!
    // =======================================================
    // Si el backend (por culpa de un bug o caché) devuelve 200
    // pero no envía un token, lo tratamos como un error.
    if (!data.token) {
        throw new Error(data.message || "Respuesta inválida del servidor");
    }
    // =======================================================

    // Si llega aquí, es porque SÍ hay un token
    localStorage.setItem('auth_token', data.token) 
    localStorage.setItem('user', JSON.stringify(data.user || {}))
    router.push({ name: 'dashboard' }) 
  
  } catch (err) {
    // Ahora el 'throw new Error' de arriba también será
    // atrapado aquí.
    errorMsg.value = err.message || 'Error al iniciar sesión'
  } finally {
    loading.value = false
  }
}

</script>
