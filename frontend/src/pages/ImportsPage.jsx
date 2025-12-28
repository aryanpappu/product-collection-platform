import {useState} from 'react';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {useNavigate} from 'react-router-dom';
import {
    Alert,
    Box,
    Button,
    Card,
    CardContent,
    Chip,
    CircularProgress,
    Collapse,
    Container,
    LinearProgress,
    Pagination,
    Paper,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Typography,
} from '@mui/material';
import {
    CheckCircle as CheckCircleIcon,
    CloudUpload as CloudUploadIcon,
    Error as ErrorIcon,
    ExpandLess as ExpandLessIcon,
    ExpandMore as ExpandMoreIcon,
    HourglassEmpty as HourglassIcon,
    Upload as UploadIcon,
} from '@mui/icons-material';
import {toast} from 'react-toastify';
import importService from '../services/importService';

function ImportsPage() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [showUpload, setShowUpload] = useState(true);
    const [selectedFile, setSelectedFile] = useState(null);
    const [selectedCollections, setSelectedCollections] = useState([]);

    // Fetch imports using React Query
    const {data, isLoading, isError, error} = useQuery({
        queryKey: ['imports', page],
        queryFn: () => importService.getImports(page, 20),
        refetchInterval: 5000, // Refresh every 5 seconds to show progress
    });


    // Upload mutation
    const uploadMutation = useMutation({
        mutationFn: (data) => importService.uploadImport(data.file, data.collectionIds),
        onSuccess: (response) => {
            toast.success('Import started successfully!');
            setSelectedFile(null);
            setSelectedCollections([]);
            queryClient.invalidateQueries(['imports']);
            navigate(`/imports/${response.data.import_job_id}`);
        },
    });


    const handleFileChange = (event) => {
        const file = event.target.files[0];
        if (file) {
            if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
                toast.error('Please select a CSV file');
                return;
            }
            setSelectedFile(file);
        }
    };

    const handleCollectionChange = (event) => {
        const value = event.target.value;
        setSelectedCollections(typeof value === 'string' ? value.split(',') : value);
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        if (!selectedFile) {
            toast.error('Please select a file to upload');
            return;
        }

        uploadMutation.mutate({
            file: selectedFile,
            collectionIds: selectedCollections,
        });
    };

    const handlePageChange = (event, value) => {
        setPage(value);
    };

    const handleViewDetails = (importJobId) => {
        navigate(`/imports/${importJobId}`);
    };

    const getStatusChip = (status) => {
        const statusConfig = {
            pending: {
                label: 'Pending',
                color: 'default',
                icon: <HourglassIcon fontSize="small"/>,
            },
            processing: {
                label: 'Processing',
                color: 'info',
                icon: <HourglassIcon fontSize="small"/>,
            },
            completed: {
                label: 'Completed',
                color: 'success',
                icon: <CheckCircleIcon fontSize="small"/>,
            },
            failed: {
                label: 'Failed',
                color: 'error',
                icon: <ErrorIcon fontSize="small"/>,
            },
        };

        const config = statusConfig[status] || statusConfig.pending;

        return (
            <Chip
                label={config.label}
                color={config.color}
                size="small"
                icon={config.icon}
            />
        );
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleString();
    };

    const imports = data?.data || [];
    const pagination = data?.meta?.pagination || {};

    return (
        <Container maxWidth="lg">
            <Box py={4}>
                {/* Header */}
                <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
                    <Typography variant="h4" component="h1" fontWeight="bold">
                        Product Imports
                    </Typography>
                    <Button
                        variant="outlined"
                        startIcon={showUpload ? <ExpandLessIcon/> : <ExpandMoreIcon/>}
                        onClick={() => setShowUpload(!showUpload)}
                    >
                        {showUpload ? 'Hide' : 'Show'} Upload
                    </Button>
                </Box>

                {/* Upload Section */}
                <Collapse in={showUpload}>
                    <Card sx={{mb: 4, borderLeft: 4, borderColor: 'primary.main'}}>
                        <CardContent>
                            <Box display="flex" alignItems="center" mb={3}>
                                <CloudUploadIcon sx={{fontSize: 32, color: 'primary.main', mr: 2}}/>
                                <Typography variant="h6" fontWeight="bold">
                                    Upload Product CSV
                                </Typography>
                            </Box>

                            <Alert severity="info" sx={{mb: 3}}>
                                <Typography variant="body2" gutterBottom>
                                    <strong>CSV Format Requirements:</strong>
                                </Typography>
                                <Typography variant="body2" component="div">
                                    Required columns: <code>name</code>, <code>sku</code>, <code>price</code>
                                    <br/>
                                    Optional
                                    columns: <code>description</code>, <code>stock</code>, <code>is_active</code>
                                </Typography>
                            </Alert>

                            <form onSubmit={handleSubmit}>
                                <Box mb={3}>
                                    <Button
                                        variant="outlined"
                                        component="label"
                                        fullWidth
                                        sx={{
                                            height: 100,
                                            borderStyle: 'dashed',
                                            borderWidth: 2,
                                            '&:hover': {
                                                borderWidth: 2,
                                                borderStyle: 'dashed',
                                                backgroundColor: 'action.hover',
                                            },
                                        }}
                                    >
                                        <Box textAlign="center">
                                            <CloudUploadIcon sx={{fontSize: 40, color: 'text.secondary', mb: 1}}/>
                                            <Typography variant="body1" color="text.secondary">
                                                {selectedFile ? selectedFile.name : 'Click to select CSV file'}
                                            </Typography>
                                            {selectedFile && (
                                                <Chip
                                                    icon={<CheckCircleIcon/>}
                                                    label={`${(selectedFile.size / 1024).toFixed(2)} KB`}
                                                    color="success"
                                                    size="small"
                                                    sx={{mt: 1}}
                                                />
                                            )}
                                        </Box>
                                        <input
                                            type="file"
                                            hidden
                                            accept=".csv"
                                            onChange={handleFileChange}
                                        />
                                    </Button>
                                </Box>


                                {uploadMutation.isLoading && (
                                    <Box mb={3}>
                                        <LinearProgress/>
                                        <Typography variant="body2" color="text.secondary" align="center" sx={{mt: 1}}>
                                            Uploading and processing file...
                                        </Typography>
                                    </Box>
                                )}

                                <Button
                                    type="submit"
                                    variant="contained"
                                    fullWidth
                                    disabled={!selectedFile || uploadMutation.isLoading}
                                    startIcon={<CloudUploadIcon/>}
                                    size="large"
                                >
                                    {uploadMutation.isLoading ? 'Uploading...' : 'Upload and Import'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </Collapse>

                {/* Import List Section */}
                <Typography variant="h6" fontWeight="bold" mb={2}>
                    Import History
                </Typography>

                {isLoading ? (
                    <Box display="flex" justifyContent="center" py={8}>
                        <CircularProgress/>
                    </Box>
                ) : isError ? (
                    <Alert severity="error">
                        Error loading imports: {error?.message}
                    </Alert>
                ) : imports.length === 0 ? (
                    <Paper sx={{p: 6, textAlign: 'center'}}>
                        <UploadIcon sx={{fontSize: 64, color: 'text.secondary', mb: 2}}/>
                        <Typography variant="h6" color="text.secondary" gutterBottom>
                            No imports yet
                        </Typography>
                        <Typography variant="body2" color="text.secondary" mb={3}>
                            Upload a CSV file above to start importing products
                        </Typography>
                    </Paper>
                ) : (
                    <>
                        <TableContainer component={Paper}>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableCell><strong>File Name</strong></TableCell>
                                        <TableCell><strong>Status</strong></TableCell>
                                        <TableCell align="right"><strong>Total Rows</strong></TableCell>
                                        <TableCell align="right"><strong>Processed</strong></TableCell>
                                        <TableCell align="right"><strong>Successful</strong></TableCell>
                                        <TableCell align="right"><strong>Failed</strong></TableCell>
                                        <TableCell><strong>Started At</strong></TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {imports.map((importJob, index) => (
                                        <TableRow key={importJob.id || index} hover>
                                            <TableCell>{importJob.filename || importJob.file_name || 'Unknown'}</TableCell>
                                            <TableCell>{getStatusChip(importJob.status || 'pending')}</TableCell>
                                            <TableCell align="right">{importJob.total_rows || 0}</TableCell>
                                            <TableCell align="right">{importJob.processed_rows || 0}</TableCell>
                                            <TableCell align="right">
                                                <Typography color="success.main" fontWeight="medium">
                                                    {importJob.successful_rows || 0}
                                                </Typography>
                                            </TableCell>
                                            <TableCell align="right">
                                                <Typography color="error.main" fontWeight="medium">
                                                    {importJob.failed_rows || 0}
                                                </Typography>
                                            </TableCell>
                                            <TableCell>
                                                <Typography variant="body2">
                                                    {importJob.created_at ? formatDate(importJob.created_at) : 'N/A'}
                                                </Typography>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </TableContainer>

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
        </Container>
    );
}

export default ImportsPage;
