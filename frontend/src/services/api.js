import axios from 'axios';
import {toast} from 'react-toastify';

// Base API URL - update this to match your Laravel backend
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

// localStorage key for merchant
const MERCHANT_STORAGE_KEY = 'selected_merchant';

// Helper function to get merchant ID from localStorage
const getMerchantId = () => {
    try {
        const storedMerchant = localStorage.getItem(MERCHANT_STORAGE_KEY);
        if (storedMerchant) {
            const merchant = JSON.parse(storedMerchant);
            return merchant.id;
        }
    } catch (error) {
        console.error('Failed to get merchant ID:', error);
    }
    return null;
};

// Create axios instance
const api = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    timeout: 30000, // 30 seconds
});

// Request interceptor
api.interceptors.request.use(
    (config) => {
        // Add merchant ID from localStorage to headers
        const merchantId = getMerchantId();
        if (merchantId) {
            config.headers['X-Merchant-ID'] = merchantId;
        }

        // You can add auth token here if needed
        // const token = localStorage.getItem('token');
        // if (token) {
        //   config.headers.Authorization = `Bearer ${token}`;
        // }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor
api.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        // Handle errors globally
        if (error.response) {
            // Server responded with error status
            const {status, data} = error.response;

            switch (status) {
                case 400:
                    toast.error(data.message || 'Bad request. Please check your input.');
                    break;
                case 401:
                    toast.error('Unauthorized. Please login again.');
                    // Redirect to login if needed
                    break;
                case 403:
                    toast.error('Forbidden. You do not have permission.');
                    break;
                case 404:
                    toast.error(data.message || 'Resource not found.');
                    break;
                case 422:
                    // Validation errors
                    if (data.errors) {
                        Object.values(data.errors).forEach((errorArray) => {
                            errorArray.forEach((msg) => toast.error(msg));
                        });
                    } else {
                        toast.error(data.message || 'Validation error.');
                    }
                    break;
                case 500:
                    toast.error('Server error. Please try again later.');
                    break;
                default:
                    toast.error(data.message || 'An error occurred.');
            }
        } else if (error.request) {
            // Request made but no response
            toast.error('Network error. Please check your connection.');
        } else {
            // Other errors
            toast.error('An unexpected error occurred.');
        }

        return Promise.reject(error);
    }
);

export default api;
