import axios from 'axios';

// Separate axios instance for merchant API (no X-Merchant-ID header needed)
const merchantApi = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    timeout: 30000,
});

const merchantService = {
    // Get all merchants
    getMerchants: async () => {
        const response = await merchantApi.get('/merchants');
        return response.data;
    },
};

export default merchantService;
