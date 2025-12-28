import React, {useState} from 'react';
import {
    Alert,
    Box,
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    FormControl,
    FormControlLabel,
    FormLabel,
    LinearProgress,
    Paper,
    Radio,
    RadioGroup,
    Typography,
} from '@mui/material';
import {CloudUpload, Upload} from '@mui/icons-material';
import {useMutation} from '@tanstack/react-query';
import {toast} from 'react-toastify';
import collectionService from '../../services/collectionService';

const CollectionCsvUpload = ({collectionId, open, onClose, onSuccess}) => {
    const [selectedFile, setSelectedFile] = useState(null);
    const [operationType, setOperationType] = useState('add');
    const [dragActive, setDragActive] = useState(false);

    const uploadMutation = useMutation({
        mutationFn: ({file, operationType}) =>
            collectionService.uploadCsv(collectionId, file, operationType),
        onSuccess: (data) => {
            toast.success('CSV uploaded successfully! Processing started.');
            setSelectedFile(null);
            setOperationType('add');
            onSuccess && onSuccess(data);
            onClose();
        },
        onError: (error) => {
            toast.error(
                error.response?.data?.message || 'Failed to upload CSV file'
            );
        },
    });

    const handleDrag = (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === 'dragenter' || e.type === 'dragover') {
            setDragActive(true);
        } else if (e.type === 'dragleave') {
            setDragActive(false);
        }
    };

    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);

        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            handleFileSelect(e.dataTransfer.files[0]);
        }
    };

    const handleFileInput = (e) => {
        if (e.target.files && e.target.files[0]) {
            handleFileSelect(e.target.files[0]);
        }
    };

    const handleFileSelect = (file) => {
        if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
            toast.error('Please select a CSV file');
            return;
        }

        if (file.size > 100 * 1024 * 1024) {
            // 100MB
            toast.error('File size must not exceed 100MB');
            return;
        }

        setSelectedFile(file);
    };

    const handleUpload = () => {
        if (!selectedFile) {
            toast.error('Please select a file to upload');
            return;
        }

        uploadMutation.mutate({file: selectedFile, operationType});
    };

    const handleClose = () => {
        if (!uploadMutation.isPending) {
            setSelectedFile(null);
            setOperationType('add');
            onClose();
        }
    };

    return (
        <Dialog open={open} onClose={handleClose} maxWidth="sm" fullWidth>
            <DialogTitle>Upload CSV for Collection Operation</DialogTitle>
            <DialogContent>
                <Box sx={{pt: 2}}>
                    {/* Operation Type Selection */}
                    <FormControl component="fieldset" sx={{mb: 3}}>
                        <FormLabel component="legend">Operation Type</FormLabel>
                        <RadioGroup
                            value={operationType}
                            onChange={(e) => setOperationType(e.target.value)}
                            row
                        >
                            <FormControlLabel
                                value="add"
                                control={<Radio/>}
                                label="Add Products & Increase Stock"
                            />
                            <FormControlLabel
                                value="remove"
                                control={<Radio/>}
                                label="Remove Products & Decrease Stock"
                            />
                        </RadioGroup>
                    </FormControl>

                    {/* CSV Format Information */}
                    <Alert severity="info" sx={{mb: 3}}>
                        <Typography variant="body2" sx={{fontWeight: 'bold', mb: 1}}>
                            CSV Format Requirements:
                        </Typography>
                        <Typography variant="body2" component="div">
                            • Required: <strong>stock</strong> (positive integer)
                            <br/>
                            • Required: <strong>product_id</strong> OR <strong>sku</strong> (at least one)
                            <br/>
                            <br/>
                            Example:
                            <br/>
                            <code>product_id,sku,stock</code>
                            <br/>
                            <code>1,LAPTOP-001,50</code>
                            <br/>
                            <code>,PHONE-SKU,100</code>
                        </Typography>
                    </Alert>

                    {/* File Upload Area */}
                    <Paper
                        variant="outlined"
                        onDragEnter={handleDrag}
                        onDragLeave={handleDrag}
                        onDragOver={handleDrag}
                        onDrop={handleDrop}
                        sx={{
                            p: 4,
                            textAlign: 'center',
                            border: dragActive ? '2px dashed #1976d2' : '2px dashed #ccc',
                            backgroundColor: dragActive ? '#f0f7ff' : '#fafafa',
                            cursor: 'pointer',
                            transition: 'all 0.3s',
                        }}
                    >
                        <input
                            type="file"
                            accept=".csv"
                            onChange={handleFileInput}
                            style={{display: 'none'}}
                            id="csv-file-input"
                        />
                        <label htmlFor="csv-file-input" style={{cursor: 'pointer'}}>
                            <CloudUpload sx={{fontSize: 48, color: '#1976d2', mb: 2}}/>
                            <Typography variant="h6" gutterBottom>
                                {selectedFile ? selectedFile.name : 'Drop CSV file here or click to browse'}
                            </Typography>
                            <Typography variant="body2" color="textSecondary">
                                {selectedFile
                                    ? `Size: ${(selectedFile.size / 1024).toFixed(2)} KB`
                                    : 'Maximum file size: 100MB'}
                            </Typography>
                        </label>
                    </Paper>

                    {/* Upload Progress */}
                    {uploadMutation.isPending && (
                        <Box sx={{mt: 2}}>
                            <Typography variant="body2" gutterBottom>
                                Uploading file...
                            </Typography>
                            <LinearProgress/>
                        </Box>
                    )}
                </Box>
            </DialogContent>
            <DialogActions>
                <Button onClick={handleClose} disabled={uploadMutation.isPending}>
                    Cancel
                </Button>
                <Button
                    onClick={handleUpload}
                    variant="contained"
                    startIcon={<Upload/>}
                    disabled={!selectedFile || uploadMutation.isPending}
                >
                    {uploadMutation.isPending ? 'Uploading...' : 'Upload & Process'}
                </Button>
            </DialogActions>
        </Dialog>
    );
};

export default CollectionCsvUpload;
