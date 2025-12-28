import {Navigate} from 'react-router-dom';
import {Box, CircularProgress} from '@mui/material';
import {useMerchant} from '../../context/MerchantContext';

function ProtectedRoute({children}) {
    const {selectedMerchant, isLoading} = useMerchant();

    // Show loading while checking localStorage
    if (isLoading) {
        return (
            <Box display="flex" justifyContent="center" alignItems="center" minHeight="100vh">
                <CircularProgress/>
            </Box>
        );
    }

    // Redirect to merchant selection if no merchant is selected
    if (!selectedMerchant) {
        return <Navigate to="/" replace/>;
    }

    return children;
}

export default ProtectedRoute;
