// frontend/src/services/api.js
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  r => r,
  err => {
    if (err.response?.status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(err)
  }
)

export default api

// ── SSE streaming helper ──────────────────────────────────────────────────
export async function streamChat(message, onToken, onDone) {
  const token = localStorage.getItem('token')
  const base  = import.meta.env.VITE_API_URL ?? '/api'

  const resp = await fetch(`${base}/chat`, {
    method:  'POST',
    headers: {
      'Content-Type':  'application/json',
      'Accept':        'text/event-stream',
      'Authorization': `Bearer ${token}`,
    },
    body: JSON.stringify({ message }),
  })

  const reader  = resp.body.getReader()
  const decoder = new TextDecoder()
  let   buffer  = ''

  while (true) {
    const { done, value } = await reader.read()
    if (done) break

    buffer += decoder.decode(value, { stream: true })
    const lines = buffer.split('\n')
    buffer = lines.pop() ?? ''

    for (const line of lines) {
      if (!line.startsWith('data:')) continue
      const json = line.slice(5).trim()
      if (!json) continue
      const data = JSON.parse(json)
      if (data.token) onToken(data.token)
      if (data.done)  onDone()
    }
  }
}
