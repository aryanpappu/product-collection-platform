import api from './api';

const collectionService = {
    // Get all collections with pagination
    getCollections: async (page = 1, perPage = 20) => {
        const response = await api.get('/collections', {
            params: {page, per_page: perPage},
        });
        return response.data;
    },

    // Get single collection by ID
    getCollectionById: async (id) => {
        const response = await api.get(`/collections/${id}`);
        return response.data;
    },

    // Create new collection
    createCollection: async (data) => {
        const response = await api.post('/collections', data);
        return response.data;
    },

    // Update collection
    updateCollection: async (id, data) => {
        const response = await api.put(`/collections/${id}`, data);
        return response.data;
    },

    // Delete collection
    deleteCollection: async (id) => {
        const response = await api.delete(`/collections/${id}`);
        return response.data;
    },

    // Upload CSV for collection operations
    uploadCsv: async (collectionId, file, operationType) => {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('operation_type', operationType);

        const response = await api.post(`/collections/${collectionId}/upload-csv`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        return response.data;
    },

    // Get all operations for a collection
    getOperations: async (collectionId, page = 1) => {
        const response = await api.get(`/collections/${collectionId}/operations`, {
            params: {page},
        });
        return response.data;
    },

    // Get operation status
    getOperationStatus: async (collectionId, operationId) => {
        const response = await api.get(`/collections/${collectionId}/operations/${operationId}`);
        return response.data;
    },

    // Get operation errors
    getOperationErrors: async (collectionId, operationId, page = 1) => {
        const response = await api.get(`/collections/${collectionId}/operations/${operationId}/errors`, {
            params: {page},
        });
        return response.data;
    },
};

export default collectionService;
