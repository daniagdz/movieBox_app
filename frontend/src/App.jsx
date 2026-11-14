import { useEffect, useState } from 'react'
import { fetchUser } from './api/auth'

export default function App() {
  const [status, setStatus] = useState('Comprobando sesión...')

  useEffect(() => {
    fetchUser()
      .then((user) => setStatus(`Sesión iniciada: ${user.email}`))
      .catch((error) => setStatus(`Sin sesión (HTTP ${error.status})`))
  }, [])

  return <p>{status}</p>
}