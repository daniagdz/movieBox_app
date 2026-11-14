import {request} from './client'

export const fetchCsrCookie = () => request('/sanctum/csrf-cookie')
export const fetchUser = () => request('/api/user')