import {useState} from 'react';
import {useNavigate, useParams} from 'react-router-dom';
import {useQuery, useQueryClient} from '@tanstack/react-query';
import {Box, Button, Chip, CircularProgress, Container, Divider, IconButton, Paper, Typography,} from '@mui/material';
import {ArrowBack as ArrowBackIcon, Edit as EditIcon, Upload as UploadIcon,} from '@mui/icons-material';
import collectionService from '../services/collectionService';
import CollectionFormDialog from '../components/collections/CollectionFormDialog';
import CollectionCsvUpload from '../components/collections/CollectionCsvUpload';
import CollectionOperationsPanel from '../components/collections/CollectionOperationsPanel';
import MerchantSwitcher from '../components/common/MerchantSwitcher';

function CollectionDetailPage() {
    const {id} = useParams();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [openEditDialog, setOpenEditDialog] = useState(false);
    const [openCsvUploadDialog, setOpenCsvUploadDialog] = useState(false);

    // Fetch collection details
    const {data, isLoading, isError, error} = useQuery({
        queryKey: ['collection', id],
        queryFn: () => collectionService.getCollectionById(id),
    });

    const handleBack = () => {
        navigate('/collections');
    };

    const handleEdit = () => {
        setOpenEditDialog(true);
    };

    const handleCloseEditDialog = () => {
        setOpenEditDialog(false);
    };

    const handleEditSuccess = () => {
        queryClient.invalidateQueries(['collection', id]);
        queryClient.invalidateQueries(['collections']);
        handleCloseEditDialog();
    };

    const handleOpenCsvUpload = () => {
        setOpenCsvUploadDialog(true);
    };

    const handleCloseCsvUpload = () => {
        setOpenCsvUploadDialog(false);
    };

    const handleCsvUploadSuccess = () => {
        queryClient.invalidateQueries(['collection-operations', id]);
        queryClient.invalidateQueries(['collection', id]);
    };

    if (isLoading) {
        return (
            <Box display="flex" justifyContent="center" alignItems="center" minHeight="100vh">
                <CircularProgress/>
            </Box>
        );
    }

    if (isError) {
        return (
            <Container>
                <Box py={4}>
                    <Typography color="error">
                        Error loading collection: {error?.message}
                    </Typography>
                    <Button onClick={handleBack} sx={{mt: 2}}>
                        Back to Collections
                    </Button>
                </Box>
            </Container>
        );
    }

    const collection = data?.data;

    return (
        <Container maxWidth="lg">
            <Box py={4}>
                {/* Merchant Switcher */}
                <Box mb={3}>
                    <MerchantSwitcher/>
                </Box>

                <Box display="flex" alignItems="center" gap={2} mb={3}>
                    <IconButton onClick={handleBack}>
                        <ArrowBackIcon/>
                    </IconButton>
                    <Typography variant="h4" component="h1" fontWeight="bold" flexGrow={1}>
                        {collection.name}
                    </Typography>
                    <IconButton onClick={handleEdit} color="primary">
                        <EditIcon/>
                    </IconButton>
                </Box>

                <Paper sx={{p: 3, mb: 3}}>
                    <Typography variant="h6" gutterBottom>
                        Collection Details
                    </Typography>
                    <Divider sx={{mb: 2}}/>
                    <Box>
                        <Typography variant="body1" gutterBottom>
                            <strong>Description:</strong> {collection.description || 'No description'}
                        </Typography>
                        <Typography variant="body1" gutterBottom>
                            <strong>Slug:</strong> {collection.slug}
                        </Typography>
                        <Box sx={{display: 'flex', alignItems: 'center', gap: 1, mb: 1}}>
                            <Typography variant="body1">
                                <strong>Status:</strong>
                            </Typography>
                            <Chip
                                label={collection.is_active ? 'Active' : 'Inactive'}
                                color={collection.is_active ? 'success' : 'default'}
                                size="small"
                            />
                        </Box>
                        <Typography variant="body1" gutterBottom>
                            <strong>Total Products:</strong> {collection.products_count || 0}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            Created: {new Date(collection.created_at).toLocaleString()}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            Updated: {new Date(collection.updated_at).toLocaleString()}
                        </Typography>
                    </Box>
                </Paper>

                <Paper sx={{p: 3}}>
                    <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
                        <Typography variant="h5" fontWeight="bold">
                            Manage Products via CSV
                        </Typography>
                        <Button
                            variant="contained"
                            size="large"
                            startIcon={<UploadIcon/>}
                            onClick={handleOpenCsvUpload}
                            color="primary"
                        >
                            Upload CSV
                        </Button>
                    </Box>
                    <Divider sx={{mb: 3}}/>
                    <CollectionOperationsPanel collectionId={collection.id}/>
                </Paper>
            </Box>

            <CollectionFormDialog
                open={openEditDialog}
                onClose={handleCloseEditDialog}
                collection={collection}
                onSuccess={handleEditSuccess}
            />

            <CollectionCsvUpload
                collectionId={collection.id}
                open={openCsvUploadDialog}
                onClose={handleCloseCsvUpload}
                onSuccess={handleCsvUploadSuccess}
            />
        </Container>
    );
}

export default CollectionDetailPage;
