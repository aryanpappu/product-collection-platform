import {useState} from 'react';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {useNavigate} from 'react-router-dom';
import {
    Box,
    Button,
    Card,
    CardActions,
    CardContent,
    CircularProgress,
    Container,
    Grid,
    IconButton,
    Pagination,
    Typography,
} from '@mui/material';
import {Add as AddIcon, Delete as DeleteIcon, Edit as EditIcon, Visibility as ViewIcon,} from '@mui/icons-material';
import {toast} from 'react-toastify';
import collectionService from '../services/collectionService';
import CollectionFormDialog from '../components/collections/CollectionFormDialog';

function CollectionListPage() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [openDialog, setOpenDialog] = useState(false);
    const [editingCollection, setEditingCollection] = useState(null);

    // Fetch collections using React Query
    const {data, isLoading, isError, error} = useQuery({
        queryKey: ['collections', page],
        queryFn: () => collectionService.getCollections(page, 20),
    });

    // Delete mutation
    const deleteMutation = useMutation({
        mutationFn: (id) => collectionService.deleteCollection(id),
        onSuccess: () => {
            queryClient.invalidateQueries(['collections']);
            toast.success('Collection deleted successfully!');
        },
    });

    const handlePageChange = (event, value) => {
        setPage(value);
    };

    const handleCreate = () => {
        setEditingCollection(null);
        setOpenDialog(true);
    };

    const handleEdit = (collection) => {
        setEditingCollection(collection);
        setOpenDialog(true);
    };

    const handleDelete = async (id, name) => {
        if (window.confirm(`Are you sure you want to delete "${name}"?`)) {
            deleteMutation.mutate(id);
        }
    };

    const handleView = (id) => {
        navigate(`/collections/${id}`);
    };

    const handleCloseDialog = () => {
        setOpenDialog(false);
        setEditingCollection(null);
    };

    const handleFormSuccess = () => {
        queryClient.invalidateQueries(['collections']);
        handleCloseDialog();
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
                        Error loading collections: {error?.message}
                    </Typography>
                </Box>
            </Container>
        );
    }

    const collections = data?.data || [];
    const pagination = data?.meta?.pagination || {};

    return (
        <Container maxWidth="lg">
            <Box py={4}>
                <Box display="flex" justifyContent="space-between" alignItems="center" mb={4}>
                    <Typography variant="h4" component="h1" fontWeight="bold">
                        Collections
                    </Typography>
                    <Button
                        variant="contained"
                        startIcon={<AddIcon/>}
                        onClick={handleCreate}
                    >
                        Create Collection
                    </Button>
                </Box>

                {collections.length === 0 ? (
                    <Box textAlign="center" py={8}>
                        <Typography variant="h6" color="text.secondary" gutterBottom>
                            No collections found
                        </Typography>
                        <Button
                            variant="contained"
                            startIcon={<AddIcon/>}
                            onClick={handleCreate}
                            sx={{mt: 2}}
                        >
                            Create your first collection
                        </Button>
                    </Box>
                ) : (
                    <>
                        <Grid container spacing={3}>
                            {collections.map((collection) => (
                                <Grid item xs={12} sm={6} md={4} key={collection.id}>
                                    <Card>
                                        <CardContent>
                                            <Typography variant="h6" gutterBottom>
                                                {collection.name}
                                            </Typography>
                                            <Typography
                                                variant="body2"
                                                color="text.secondary"
                                                sx={{
                                                    overflow: 'hidden',
                                                    textOverflow: 'ellipsis',
                                                    display: '-webkit-box',
                                                    WebkitLineClamp: 2,
                                                    WebkitBoxOrient: 'vertical',
                                                    minHeight: '40px',
                                                }}
                                            >
                                                {collection.description || 'No description'}
                                            </Typography>
                                            <Box mt={2}>
                                                <Typography variant="caption" color="text.secondary">
                                                    Products: {collection.products_count || 0}
                                                </Typography>
                                                <br/>
                                                <Typography variant="caption" color="text.secondary">
                                                    Status: {collection.is_active ? 'Active' : 'Inactive'}
                                                </Typography>
                                            </Box>
                                        </CardContent>
                                        <CardActions>
                                            <IconButton
                                                size="small"
                                                color="primary"
                                                onClick={() => handleView(collection.id)}
                                                title="View Details"
                                            >
                                                <ViewIcon/>
                                            </IconButton>
                                            <IconButton
                                                size="small"
                                                color="info"
                                                onClick={() => handleEdit(collection)}
                                                title="Edit"
                                            >
                                                <EditIcon/>
                                            </IconButton>
                                            <IconButton
                                                size="small"
                                                color="error"
                                                onClick={() => handleDelete(collection.id, collection.name)}
                                                disabled={deleteMutation.isLoading}
                                                title="Delete"
                                            >
                                                <DeleteIcon/>
                                            </IconButton>
                                        </CardActions>
                                    </Card>
                                </Grid>
                            ))}
                        </Grid>

                        {pagination.total_pages > 1 && (
                            <Box display="flex" justifyContent="center" mt={4}>
                                <Pagination
                                    count={pagination.total_pages}
                                    page={page}
                                    onChange={handlePageChange}
                                    color="primary"
                                />
                            </Box>
                        )}
                    </>
                )}
            </Box>

            <CollectionFormDialog
                open={openDialog}
                onClose={handleCloseDialog}
                collection={editingCollection}
                onSuccess={handleFormSuccess}
            />
        </Container>
    );
}

export default CollectionListPage;
