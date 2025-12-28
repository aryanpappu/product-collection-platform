import {useNavigate} from 'react-router-dom';
import {Box, Chip, IconButton, Tooltip, Typography,} from '@mui/material';
import {Store as StoreIcon, SwapHoriz as SwapIcon,} from '@mui/icons-material';
import {useMerchant} from '../../context/MerchantContext';
import {toast} from 'react-toastify';

function MerchantSwitcher() {
    const navigate = useNavigate();
    const {selectedMerchant, clearMerchant} = useMerchant();

    const handleSwitchMerchant = () => {
        clearMerchant();
        toast.info('Please select a merchant');
        navigate('/');
    };

    if (!selectedMerchant) {
        return null;
    }

    return (
        <Box display="flex" alignItems="center" gap={1}>
            <StoreIcon fontSize="small" color="primary"/>
            <Typography variant="body2" color="text.secondary" sx={{mr: 1}}>
                Merchant:
            </Typography>
            <Chip
                label={selectedMerchant.name}
                color="primary"
                variant="outlined"
                size="small"
            />
            <Tooltip title="Switch Merchant">
                <IconButton
                    size="small"
                    onClick={handleSwitchMerchant}
                    color="primary"
                >
                    <SwapIcon fontSize="small"/>
                </IconButton>
            </Tooltip>
        </Box>
    );
}

export default MerchantSwitcher;
