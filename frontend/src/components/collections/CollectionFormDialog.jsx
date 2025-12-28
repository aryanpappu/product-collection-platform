import {useMutation} from '@tanstack/react-query';
import {
    Box,
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    FormControlLabel,
    Switch,
    TextField,
} from '@mui/material';
import {Form, Formik} from 'formik';
import * as Yup from 'yup';
import {toast} from 'react-toastify';
import collectionService from '../../services/collectionService';

// Validation schema
const validationSchema = Yup.object({
    name: Yup.string()
        .required('Name is required')
        .max(255, 'Name must be less than 255 characters'),
    description: Yup.string()
        .nullable(),
    is_active: Yup.boolean(),
});

function CollectionFormDialog({open, onClose, collection, onSuccess}) {
    const isEdit = Boolean(collection);

    const initialValues = {
        name: collection?.name || '',
        description: collection?.description || '',
        is_active: collection?.is_active ?? true,
    };

    // Create mutation
    const createMutation = useMutation({
        mutationFn: (data) => collectionService.createCollection(data),
        onSuccess: () => {
            toast.success('Collection created successfully!');
            onSuccess();
        },
    });

    // Update mutation
    const updateMutation = useMutation({
        mutationFn: (data) => collectionService.updateCollection(collection.id, data),
        onSuccess: () => {
            toast.success('Collection updated successfully!');
            onSuccess();
        },
    });

    const handleSubmit = async (values, {setSubmitting}) => {
        try {
            if (isEdit) {
                await updateMutation.mutateAsync(values);
            } else {
                await createMutation.mutateAsync(values);
            }
        } catch (error) {
            // Error is handled by axios interceptor
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth>
            <DialogTitle>
                {isEdit ? 'Edit Collection' : 'Create Collection'}
            </DialogTitle>
            <Formik
                initialValues={initialValues}
                validationSchema={validationSchema}
                onSubmit={handleSubmit}
                enableReinitialize
            >
                {({values, errors, touched, handleChange, handleBlur, isSubmitting}) => (
                    <Form>
                        <DialogContent>
                            <Box display="flex" flexDirection="column" gap={2}>
                                <TextField
                                    fullWidth
                                    name="name"
                                    label="Collection Name"
                                    value={values.name}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={touched.name && Boolean(errors.name)}
                                    helperText={touched.name && errors.name}
                                    required
                                />

                                <TextField
                                    fullWidth
                                    name="description"
                                    label="Description"
                                    value={values.description}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={touched.description && Boolean(errors.description)}
                                    helperText={touched.description && errors.description}
                                    multiline
                                    rows={4}
                                />

                                <FormControlLabel
                                    control={
                                        <Switch
                                            name="is_active"
                                            checked={values.is_active}
                                            onChange={handleChange}
                                        />
                                    }
                                    label="Active"
                                />
                            </Box>
                        </DialogContent>
                        <DialogActions>
                            <Button onClick={onClose} disabled={isSubmitting}>
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                variant="contained"
                                disabled={isSubmitting}
                            >
                                {isSubmitting ? 'Saving...' : isEdit ? 'Update' : 'Create'}
                            </Button>
                        </DialogActions>
                    </Form>
                )}
            </Formik>
        </Dialog>
    );
}

export default CollectionFormDialog;
