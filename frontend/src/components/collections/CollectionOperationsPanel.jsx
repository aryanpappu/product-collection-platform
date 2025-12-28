import React, {useState} from 'react';
import {
    Alert,
    Box,
    Button,
    Card,
    CardContent,
    Chip,
    Collapse,
    Grid,
    IconButton,
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
    Assessment,
    CheckCircle,
    Error,
    HourglassEmpty,
    KeyboardArrowDown,
    KeyboardArrowUp,
    Refresh,
    TrendingDown,
    TrendingUp,
} from '@mui/icons-material';
import {useQuery} from '@tanstack/react-query';
import collectionService from '../../services/collectionService';

const OperationRow = ({operation, collectionId}) => {
    const [open, setOpen] = useState(false);
    const [errorsPage, setErrorsPage] = useState(1);

    const {
        data: errorsData,
        isLoading: errorsLoading,
    } = useQuery({
        queryKey: ['collection-operation-errors', operation.id, errorsPage],
        queryFn: () => collectionService.getOperationErrors(collectionId, operation.id, errorsPage),
        enabled: open && operation.failed_rows > 0,
    });

    const getStatusColor = (status) => {
        switch (status) {
            case 'completed':
                return 'success';
            case 'processing':
                return 'primary';
            case 'failed':
                return 'error';
            case 'queued':
                return 'default';
            default:
                return 'default';
        }
    };

    const getStatusIcon = (status) => {
        switch (status) {
            case 'completed':
                return <CheckCircle fontSize="small"/>;
            case 'processing':
                return <HourglassEmpty fontSize="small"/>;
            case 'failed':
                return <Error fontSize="small"/>;
            default:
                return null;
        }
    };

    return (
        <>
            <TableRow hover sx={{'&:hover': {backgroundColor: 'action.hover'}}}>
                <TableCell>
                    {operation.failed_rows > 0 && (
                        <IconButton size="small" onClick={() => setOpen(!open)}>
                            {open ? <KeyboardArrowUp/> : <KeyboardArrowDown/>}
                        </IconButton>
                    )}
                </TableCell>
                <TableCell>
                    <Typography variant="body2" fontWeight="medium">
                        {operation.filename}
                    </Typography>
                </TableCell>
                <TableCell>
                    <Chip
                        label={operation.operation_type.toUpperCase()}
                        color={operation.operation_type === 'add' ? 'success' : 'warning'}
                        size="small"
                        sx={{fontWeight: 'bold'}}
                    />
                </TableCell>
                <TableCell>
                    <Chip
                        label={operation.status.toUpperCase()}
                        color={getStatusColor(operation.status)}
                        size="small"
                        icon={getStatusIcon(operation.status)}
                    />
                </TableCell>
                <TableCell>
                    {operation.status === 'processing' && (
                        <Box sx={{width: '100%', minWidth: 150}}>
                            <Box sx={{display: 'flex', alignItems: 'center', mb: 0.5}}>
                                <Typography variant="caption" fontWeight="bold" color="primary">
                                    {operation.progress_percentage}%
                                </Typography>
                            </Box>
                            <LinearProgress
                                variant="determinate"
                                value={operation.progress_percentage}
                                sx={{height: 8, borderRadius: 1}}
                            />
                        </Box>
                    )}
                    {operation.status === 'completed' && (
                        <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                            <CheckCircle color="success" fontSize="small"/>
                            <Typography variant="body2" fontWeight="bold" color="success.main">
                                100%
                            </Typography>
                        </Box>
                    )}
                    {operation.status === 'failed' && (
                        <Typography variant="body2" color="error" fontWeight="bold">
                            Failed
                        </Typography>
                    )}
                    {operation.status === 'queued' && (
                        <Typography variant="body2" color="text.secondary">
                            Queued
                        </Typography>
                    )}
                </TableCell>
                <TableCell align="right">
                    <Typography variant="body2" fontWeight="medium">
                        {operation.total_rows.toLocaleString()}
                    </Typography>
                </TableCell>
                <TableCell align="right">
                    <Typography variant="body2" fontWeight="medium">
                        {operation.processed_rows.toLocaleString()}
                    </Typography>
                </TableCell>
                <TableCell align="right">
                    <Typography variant="body2" fontWeight="bold" color="success.main">
                        {operation.successful_rows.toLocaleString()}
                    </Typography>
                </TableCell>
                <TableCell align="right">
                    <Typography
                        variant="body2"
                        fontWeight="bold"
                        color={operation.failed_rows > 0 ? 'error' : 'text.secondary'}
                    >
                        {operation.failed_rows.toLocaleString()}
                    </Typography>
                </TableCell>
                <TableCell align="right">
                    {operation.stock_added > 0 && (
                        <Box sx={{display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: 0.5}}>
                            <TrendingUp fontSize="small" color="success"/>
                            <Typography variant="body2" fontWeight="bold" color="success.main">
                                +{operation.stock_added.toLocaleString()}
                            </Typography>
                        </Box>
                    )}
                    {operation.stock_removed > 0 && (
                        <Box sx={{
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'flex-end',
                            gap: 0.5,
                            mt: operation.stock_added > 0 ? 0.5 : 0
                        }}>
                            <TrendingDown fontSize="small" color="error"/>
                            <Typography variant="body2" fontWeight="bold" color="error.main">
                                -{operation.stock_removed.toLocaleString()}
                            </Typography>
                        </Box>
                    )}
                    {operation.stock_added === 0 && operation.stock_removed === 0 && (
                        <Typography variant="body2" color="text.secondary">
                            -
                        </Typography>
                    )}
                </TableCell>
                <TableCell>
                    <Typography variant="caption" color="text.secondary">
                        {new Date(operation.created_at).toLocaleString()}
                    </Typography>
                </TableCell>
            </TableRow>

            {/* Errors Expandable Row */}
            {operation.failed_rows > 0 && (
                <TableRow>
                    <TableCell colSpan={10} sx={{py: 0}}>
                        <Collapse in={open} timeout="auto" unmountOnExit>
                            <Box sx={{margin: 2}}>
                                <Typography variant="h6" gutterBottom>
                                    Error Details ({operation.failed_rows} errors)
                                </Typography>
                                {errorsLoading ? (
                                    <LinearProgress/>
                                ) : errorsData?.data?.length > 0 ? (
                                    <>
                                        <TableContainer component={Paper} variant="outlined">
                                            <Table size="small">
                                                <TableHead>
                                                    <TableRow>
                                                        <TableCell>Row #</TableCell>
                                                        <TableCell>Error Type</TableCell>
                                                        <TableCell>Error Message</TableCell>
                                                        <TableCell>Row Data</TableCell>
                                                    </TableRow>
                                                </TableHead>
                                                <TableBody>
                                                    {errorsData.data.map((error) => (
                                                        <TableRow key={error.id}>
                                                            <TableCell>{error.row_number}</TableCell>
                                                            <TableCell>
                                                                <Chip label={error.error_type} size="small"
                                                                      color="error"/>
                                                            </TableCell>
                                                            <TableCell>{error.error_message}</TableCell>
                                                            <TableCell>
                                                                <code>{JSON.stringify(error.row_data)}</code>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </TableContainer>
                                        {errorsData.pagination && errorsData.pagination.last_page > 1 && (
                                            <Box sx={{mt: 2, display: 'flex', justifyContent: 'center'}}>
                                                <Pagination
                                                    count={errorsData.pagination.last_page}
                                                    page={errorsPage}
                                                    onChange={(e, page) => setErrorsPage(page)}
                                                    color="primary"
                                                />
                                            </Box>
                                        )}
                                    </>
                                ) : (
                                    <Alert severity="info">No error details available</Alert>
                                )}
                            </Box>
                        </Collapse>
                    </TableCell>
                </TableRow>
            )}
        </>
    );
};

const CollectionOperationsPanel = ({collectionId}) => {
    const [page, setPage] = useState(1);

    const {data, isLoading, refetch} = useQuery({
        queryKey: ['collection-operations', collectionId, page],
        queryFn: () => collectionService.getOperations(collectionId, page),
        refetchInterval: (data) => {
            // Auto-refresh every 3 seconds if there are processing operations
            const hasProcessing = data?.data?.some(
                (op) => op.status === 'processing' || op.status === 'queued'
            );
            return hasProcessing ? 3000 : false;
        },
    });

    if (isLoading) {
        return <LinearProgress/>;
    }

    const operations = data?.data || [];
    const meta = data?.pagination;

    if (operations.length === 0) {
        return (
            <Alert severity="info" sx={{mt: 2}}>
                No CSV operations yet. Upload a CSV file to get started.
            </Alert>
        );
    }

    // Calculate totals
    const totalOperations = operations.length;
    const processingOps = operations.filter(op => op.status === 'processing' || op.status === 'queued').length;
    const completedOps = operations.filter(op => op.status === 'completed').length;
    const totalStockAdded = operations.reduce((sum, op) => sum + (op.stock_added || 0), 0);
    const totalStockRemoved = operations.reduce((sum, op) => sum + (op.stock_removed || 0), 0);
    const totalRowsProcessed = operations.reduce((sum, op) => sum + (op.successful_rows || 0), 0);

    return (
        <Box>
            {/* Statistics Cards */}
            <Grid container spacing={2} sx={{mb: 3}}>
                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{bgcolor: 'primary.light', color: 'primary.contrastText'}}>
                        <CardContent>
                            <Box sx={{display: 'flex', alignItems: 'center', justifyContent: 'space-between'}}>
                                <Box>
                                    <Typography variant="caption">Total Operations</Typography>
                                    <Typography variant="h4" fontWeight="bold">
                                        {totalOperations}
                                    </Typography>
                                </Box>
                                <Assessment sx={{fontSize: 40, opacity: 0.3}}/>
                            </Box>
                        </CardContent>
                    </Card>
                </Grid>

                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{
                        bgcolor: processingOps > 0 ? 'warning.light' : 'success.light',
                        color: processingOps > 0 ? 'warning.contrastText' : 'success.contrastText'
                    }}>
                        <CardContent>
                            <Box sx={{display: 'flex', alignItems: 'center', justifyContent: 'space-between'}}>
                                <Box>
                                    <Typography variant="caption">
                                        {processingOps > 0 ? 'Processing' : 'Completed'}
                                    </Typography>
                                    <Typography variant="h4" fontWeight="bold">
                                        {processingOps > 0 ? processingOps : completedOps}
                                    </Typography>
                                </Box>
                                {processingOps > 0 ? (
                                    <HourglassEmpty sx={{fontSize: 40, opacity: 0.3}}/>
                                ) : (
                                    <CheckCircle sx={{fontSize: 40, opacity: 0.3}}/>
                                )}
                            </Box>
                        </CardContent>
                    </Card>
                </Grid>

                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{bgcolor: 'success.main', color: 'success.contrastText'}}>
                        <CardContent>
                            <Box sx={{display: 'flex', alignItems: 'center', justifyContent: 'space-between'}}>
                                <Box>
                                    <Typography variant="caption">Stock Added</Typography>
                                    <Typography variant="h4" fontWeight="bold">
                                        +{totalStockAdded.toLocaleString()}
                                    </Typography>
                                </Box>
                                <TrendingUp sx={{fontSize: 40, opacity: 0.3}}/>
                            </Box>
                        </CardContent>
                    </Card>
                </Grid>

                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{bgcolor: 'error.main', color: 'error.contrastText'}}>
                        <CardContent>
                            <Box sx={{display: 'flex', alignItems: 'center', justifyContent: 'space-between'}}>
                                <Box>
                                    <Typography variant="caption">Stock Removed</Typography>
                                    <Typography variant="h4" fontWeight="bold">
                                        -{totalStockRemoved.toLocaleString()}
                                    </Typography>
                                </Box>
                                <TrendingDown sx={{fontSize: 40, opacity: 0.3}}/>
                            </Box>
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>

            <Box sx={{display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2}}>
                <Typography variant="h6" fontWeight="bold">Operations History</Typography>
                <Button
                    startIcon={<Refresh/>}
                    onClick={() => refetch()}
                    size="small"
                    variant="outlined"
                >
                    Refresh
                </Button>
            </Box>

            <TableContainer component={Paper} elevation={2}>
                <Table>
                    <TableHead sx={{bgcolor: 'grey.100'}}>
                        <TableRow>
                            <TableCell/>
                            <TableCell><strong>File Name</strong></TableCell>
                            <TableCell><strong>Operation</strong></TableCell>
                            <TableCell><strong>Status</strong></TableCell>
                            <TableCell><strong>Progress</strong></TableCell>
                            <TableCell align="right"><strong>Total Rows</strong></TableCell>
                            <TableCell align="right"><strong>Processed</strong></TableCell>
                            <TableCell align="right"><strong>Successful</strong></TableCell>
                            <TableCell align="right"><strong>Failed</strong></TableCell>
                            <TableCell align="right"><strong>Stock Change</strong></TableCell>
                            <TableCell><strong>Created At</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {operations.map((operation) => (
                            <OperationRow
                                key={operation.id}
                                operation={operation}
                                collectionId={collectionId}
                            />
                        ))}
                    </TableBody>
                </Table>
            </TableContainer>

            {meta && meta.last_page > 1 && (
                <Box sx={{mt: 2, display: 'flex', justifyContent: 'center'}}>
                    <Pagination
                        count={meta.last_page}
                        page={page}
                        onChange={(e, newPage) => setPage(newPage)}
                        color="primary"
                    />
                </Box>
            )}
        </Box>
    );
};

export default CollectionOperationsPanel;
