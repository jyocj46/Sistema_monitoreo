
// src/api/auth.js 
const API_URL = 'https://drover.detpon.com/api'; 


export default async function loginRequest(email, password) {
  const res = await fetch(`${API_URL}/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ email, password }) 
  })

  if (!res.ok) {
    const errData = await res.json().catch(() => ({}))
    throw new Error(errData.message || 'Credenciales inválidas')
  }

  return res.json()
}