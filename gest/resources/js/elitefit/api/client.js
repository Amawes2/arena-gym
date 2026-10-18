import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    timeout: 15000,
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

api.interceptors.request.use((config) => {
    const gymId = localStorage.getItem('elitefit_gym_id');

    if (gymId) {
        config.params = { ...config.params, gym_id: gymId };
    }

    return config;
});

export const web = axios.create({
    baseURL: '/',
    timeout: 15000,
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

web.interceptors.request.use((config) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (csrfToken) {
        config.headers['X-CSRF-TOKEN'] = csrfToken;
    }

    return config;
});

export default api;

export function formatFcfa(amount) {
    return new Intl.NumberFormat('fr-FR').format(Math.round(amount)) + ' FCFA';
}
