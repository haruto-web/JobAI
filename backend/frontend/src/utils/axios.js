import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

// Create axios instance with base configuration
const instance = axios.create({
    baseURL: API_URL,
    withCredentials: true, // Important for handling cookies with CORS
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
});

// Add request interceptor to add auth token
instance.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

// Add response interceptor to handle 401 errors
instance.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            // Clear invalid token
            localStorage.removeItem('token');
            
            // Only redirect if we're on a protected page
            const publicPaths = ['/', '/about', '/login', '/register', '/reset-password'];
            const currentPath = window.location.pathname;
            
            if (!publicPaths.includes(currentPath)) {
                // Redirect to login for protected pages
                window.location.href = '/login';
            }
            
            // Return rejected promise without logging to console
            return Promise.reject({ ...error, silent: true });
        }
        return Promise.reject(error);
    }
);

export default instance;