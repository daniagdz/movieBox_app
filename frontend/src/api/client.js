function getCookie(name) {
    const cookies = document.cookie.split(';')

    const found = cookies.find(cookie => cookie.trim().startsWith(name + '='))
    if (!found) {
        return null
    }

    const value = found.split('=')[1]
    return decodeURIComponent(value)
}

/*
adaptas esa función request para que devuelva los errores estructurados 
(como hacías en Next.js con el 400 de validación), te puedes ahorrar 
el 80% del código de tus archivos de servicios
*/
export async function request(path, { method = 'GET', body } = {}) {
    const response = await fetch(path, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            ...(body && { ' Content-Type': 'application/json' }),
            ...(method !== 'GET' && { 'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') }),
        },
        body: body ? JSON.stringify(body) : undefined,
    })

    const data = response.status === 204 ? null : await response.json().catch(() => null)
    if (!data) {
        throw { status: response.status, data }
    }

    // --- GESTIÓN CENTRALIZADA DE ERRORES ---
    if (response.status === 401) {
        window.location.href = "/login";
        throw new Error("Sesión caducada");
    }

    if (response.status === 403) {
        throw new Error("No tienes permiso para realizar esta acción");
    }

    // Laravel devuelve 422 cuando falla la validación de un formulario
    if (response.status === 422 || response.status === 400) {
        const error = new Error("Datos no válidos");
        error.errors = data?.errors || {}; // Guardamos los errores campo por campo
        throw error;
    }

    if (!response.ok) {
        throw new Error(data?.message || "Ocurrió un error en el servidor");
    }
    return data
}