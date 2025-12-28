import {useNavigate, useParams} from 'react-router-dom';
import {useQuery} from '@tanstack/react-query';
import {
    Alert,
    Box,
    Button,
    Card,
    CardContent,
    Chip,
    CircularProgress,
    Container,
    Grid,
    LinearProgress,
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
    ArrowBack as ArrowBackIcon,
    CheckCircle as CheckCircleIcon,
    Error as ErrorIcon,
    HourglassEmpty as HourglassIcon,
    Info as InfoIcon,
} from '@mui/icons-material';
import importService from '../services/importService';

function ImportDetailPage() {
    const {importJobId} = useParams();
    const navigate = useNavigate();

    // Fetch import status
    const {data: statusData, isLoading: isLoadingStatus} = useQuery({
        queryKey: ['import-status', importJobId],
        queryFn: () => importService.getImportStatus(importJobId),
        refetchInterval: (data) => {
            // Stop polling if completed or failed
            const status = data?.data?.status;
            return status === 'completed' || status === 'failed' ? false : 2000;
        },
    });

    // Fetch import errors
    const {data: errorsData, isLoading: isLoadingErrors} = useQuery({
        queryKey: ['import-errors', importJobId],
        queryFn: () => importService.getImportErrors(importJobId),
        enabled: statusData?.data?.failed_rows > 0,
    });

    const handleBack = () => {
        navigate('/imports');
    };

    if (isLoadingStatus) {
        return (
            <Box display="flex" justifyContent="center" alignItems="center" minHeight="80vh">
                <CircularProgress/>
            </Box>
        );
    }

    const importJob = statusData?.data;
    const errors = errorsData?.data || [];

    if (!importJob) {
        return (
            <Container>
                <Box py={4}>
                    <Typography color="error">Import job not found</Typography>
                </Box>
            </Container>
        );
    }

    const getStatusConfig = (status) => {
        const configs = {
            pending: {
                label: 'Pending',
                color: 'default',
                icon: <HourglassIcon/>,
            },
            processing: {
                label: 'Processing',
                color: 'info',
                icon: <HourglassIcon/>,
            },
            completed: {
                label: 'Completed',
                color: 'success',
                icon: <CheckCircleIcon/>,
            },
            failed: {
                label: 'Failed',
                color: 'error',
                icon: <ErrorIcon/>,
            },
        };
        return configs[status] || configs.pending;
    };

    const statusConfig = getStatusConfig(importJob.status);
    const totalRows = importJob.total_rows || 0;
    const processedRows = importJob.processed_rows || 0;
    const successfulRows = importJob.successful_rows || 0;
    const failedRows = importJob.failed_rows || 0;
    const progress = totalRows > 0 ? (processedRows / totalRows) * 100 : 0;

    return (
        <Container maxWidth="lg">
            <Box py={4}>
                <Button
                    startIcon={<ArrowBackIcon/>}
                    onClick={handleBack}
                    sx={{mb: 3}}
                >
                    Back to Imports
                </Button>

                {/* Header */}
                <Paper sx={{p: 3, mb: 3}}>
                    <Box display="flex" justifyContent="space-between" alignItems="center">
                        <Box>
                            <Typography variant="h4" component="h1" fontWeight="bold" gutterBottom>
                                Import Details
                            </Typography>
                            <Typography variant="body2" color="text.secondary">
                                Import ID: {importJob.id}
                            </Typography>
                            <Typography variant="body2" color="text.secondary">
                                File: {importJob.file_name}
                            </Typography>
                        </Box>
                        <Chip
                            label={statusConfig.label}
                            color={statusConfig.color}
                            icon={statusConfig.icon}
                            sx={{fontSize: '1rem', px: 2, py: 3}}
                        />
                    </Box>
                </Paper>

                {/* Progress */}
                {importJob.status === 'processing' && (
                    <Alert severity="info" icon={<InfoIcon/>} sx={{mb: 3}}>
                        <Typography variant="body2" gutterBottom>
                            Import is being processed. This page will update automatically.
                        </Typography>
                        <Box mt={2}>
                            <Box display="flex" justifyContent="space-between" mb={1}>
                                <Typography variant="body2" fontWeight="medium">
                                    Progress: {processedRows} / {totalRows} rows
                                </Typography>
                                <Typography variant="body2" fontWeight="medium">
                                    {progress.toFixed(1)}%
                                </Typography>
                            </Box>
                            <LinearProgress variant="determinate" value={progress} sx={{height: 8, borderRadius: 4}}/>
                        </Box>
                    </Alert>
                )}

                {/* Statistics */}
                <Grid container spacing={3} mb={3}>
                    <Grid item xs={12} sm={6} md={3}>
                        <Card>
                            <CardContent>
                                <Typography color="text.secondary" gutterBottom variant="body2">
                                    Total Rows
                                </Typography>
                                <Typography variant="h4" fontWeight="bold">
                                    {totalRows}
                                </Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                    <Grid item xs={12} sm={6} md={3}>
                        <Card>
                            <CardContent>
                                <Typography color="text.secondary" gutterBottom variant="body2">
                                    Processed
                                </Typography>
                                <Typography variant="h4" fontWeight="bold" color="info.main">
                                    {processedRows}
                                </Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                    <Grid item xs={12} sm={6} md={3}>
                        <Card sx={{borderLeft: 4, borderColor: 'success.main'}}>
                            <CardContent>
                                <Typography color="text.secondary" gutterBottom variant="body2">
                                    Successful
                                </Typography>
                                <Typography variant="h4" fontWeight="bold" color="success.main">
                                    {successfulRows}
                                </Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                    <Grid item xs={12} sm={6} md={3}>
                        <Card sx={{borderLeft: 4, borderColor: 'error.main'}}>
                            <CardContent>
                                <Typography color="text.secondary" gutterBottom variant="body2">
                                    Failed
                                </Typography>
                                <Typography variant="h4" fontWeight="bold" color="error.main">
                                    {failedRows}
                                </Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                </Grid>

                {/* Error Messages */}
                {failedRows > 0 && (
                    <Paper sx={{p: 3}}>
                        <Typography variant="h6" fontWeight="bold" gutterBottom>
                            Error Details
                        </Typography>
                        {isLoadingErrors ? (
                            <Box display="flex" justifyContent="center" py={3}>
                                <CircularProgress/>
                            </Box>
                        ) : errors.length > 0 ? (
                            <TableContainer>
                                <Table>
                                    <TableHead>
                                        <TableRow>
                                            <TableCell><strong>Row</strong></TableCell>
                                            <TableCell><strong>Error Message</strong></TableCell>
                                            <TableCell><strong>Data</strong></TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {errors.map((error, index) => (
                                            <TableRow key={index}>
                                                <TableCell>
                                                    <Chip label={error.row_number} size="small"/>
                                                </TableCell>
                                                <TableCell>
                                                    <Typography variant="body2" color="error">
                                                        {error.error_message}
                                                    </Typography>
                                                </TableCell>
                                                <TableCell>
                                                    <Typography
                                                        variant="body2"
                                                        fontFamily="monospace"
                                                        sx={{
                                                            maxWidth: 400,
                                                            overflow: 'hidden',
                                                            textOverflow: 'ellipsis',
                                                            whiteSpace: 'nowrap',
                                                        }}
                                                    >
                                                        {JSON.stringify(error.row_data)}
                                                    </Typography>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                        ) : (
                            <Typography color="text.secondary">No error details available</Typography>
                        )}
                    </Paper>
                )}

                {/* Completion Message */}
                {importJob.status === 'completed' && failedRows === 0 && (
                    <Alert severity="success" icon={<CheckCircleIcon/>}>
                        <Typography variant="body2">
                            Import completed successfully! All {totalRows} rows were imported without errors.
                        </Typography>
                    </Alert>
                )}

                {importJob.status === 'failed' && (
                    <Alert severity="error" icon={<ErrorIcon/>}>
                        <Typography variant="body2">
                            Import failed. Please check the error details above and try again.
                        </Typography>
                    </Alert>
                )}
            </Box>
        </Container>
    );
}

export default ImportDetailPage;
