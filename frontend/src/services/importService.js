import api from './api';

const importService = {
    // Upload CSV file to import products
    uploadImport: async (file, collectionIds = []) => {
        const formData = new FormData();
        formData.append('file', file);

        if (collectionIds.length > 0) {
            collectionIds.forEach((id, index) => {
                formData.append(`collection_ids[${index}]`, id);
            });
        }

        const response = await api.post('/imports', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        return response.data;
    },

    // Get list of import jobs with pagination
    getImports: async (page = 1, perPage = 20) => {
        const response = await api.get('/imports', {
            params: {page, per_page: perPage},
        });
        return response.data;
    },

    // Get status of a specific import job
    getImportStatus: async (importJobId) => {
        const response = await api.get(`/imports/${importJobId}`);
        return response.data;
    },

    // Get errors for a specific import job
    getImportErrors: async (importJobId) => {
        const response = await api.get(`/imports/${importJobId}/errors`);
        return response.data;
    },
};

export default importService;
