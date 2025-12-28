import {useQuery} from '@tanstack/react-query';
import {useNavigate} from 'react-router-dom';
import {
    Avatar,
    Box,
    Card,
    CardActionArea,
    CardContent,
    CircularProgress,
    Container,
    Grid,
    Typography,
} from '@mui/material';
import {Store as StoreIcon} from '@mui/icons-material';
import {toast} from 'react-toastify';
import merchantService from '../services/merchantService';
import {useMerchant} from '../context/MerchantContext';

function MerchantSelectionPage() {
    const navigate = useNavigate();
    const {selectMerchant} = useMerchant();

    // Fetch merchants
    const {data, isLoading, isError, error} = useQuery({
        queryKey: ['merchants'],
        queryFn: merchantService.getMerchants,
    });

    const handleSelectMerchant = (merchant) => {
        selectMerchant(merchant);
        toast.success(`Selected merchant: ${merchant.name}`);
        navigate('/dashboard');
    };

    if (isLoading) {
        return (
            <Box display="flex" justifyContent="center" alignItems="center" minHeight="100vh">
                <CircularProgress size={60}/>
            </Box>
        );
    }

    if (isError) {
        return (
            <Container>
                <Box py={4}>
                    <Typography color="error" variant="h6">
                        Error loading merchants: {error?.message}
                    </Typography>
                    <Typography color="text.secondary" sx={{mt: 2}}>
                        Please make sure the backend is running and try again.
                    </Typography>
                </Box>
            </Container>
        );
    }

    const merchants = data?.data || [];

    return (
        <Container maxWidth="md">
            <Box py={6}>
                <Box textAlign="center" mb={6}>
                    <StoreIcon sx={{fontSize: 80, color: 'primary.main', mb: 2}}/>
                    <Typography variant="h3" component="h1" fontWeight="bold" gutterBottom>
                        Select Merchant
                    </Typography>
                    <Typography variant="body1" color="text.secondary">
                        Choose a merchant to manage their collections and products
                    </Typography>
                </Box>

                {merchants.length === 0 ? (
                    <Box textAlign="center" py={8}>
                        <Typography variant="h6" color="text.secondary">
                            No merchants found
                        </Typography>
                        <Typography variant="body2" color="text.secondary" sx={{mt: 1}}>
                            Please run the merchant seeder to create merchants
                        </Typography>
                        <Typography variant="caption" color="text.secondary" sx={{mt: 2, display: 'block'}}>
                            Run: php artisan db:seed --class=MerchantSeeder
                        </Typography>
                    </Box>
                ) : (
                    <Grid container spacing={3}>
                        {merchants.map((merchant) => (
                            <Grid item xs={12} sm={6} key={merchant.id}>
                                <Card
                                    elevation={3}
                                    sx={{
                                        transition: 'transform 0.2s, box-shadow 0.2s',
                                        '&:hover': {
                                            transform: 'translateY(-4px)',
                                            boxShadow: 6,
                                        },
                                    }}
                                >
                                    <CardActionArea
                                        onClick={() => handleSelectMerchant(merchant)}
                                        sx={{p: 2}}
                                    >
                                        <CardContent>
                                            <Box display="flex" alignItems="center" gap={2}>
                                                <Avatar
                                                    sx={{
                                                        bgcolor: 'primary.main',
                                                        width: 56,
                                                        height: 56,
                                                    }}
                                                >
                                                    {merchant.name.charAt(0).toUpperCase()}
                                                </Avatar>
                                                <Box flexGrow={1}>
                                                    <Typography variant="h6" gutterBottom>
                                                        {merchant.name}
                                                    </Typography>
                                                    <Typography variant="body2" color="text.secondary">
                                                        {merchant.email}
                                                    </Typography>
                                                    <Typography variant="caption" color="text.secondary" display="block"
                                                                sx={{mt: 1}}>
                                                        ID: {merchant.id}
                                                    </Typography>
                                                </Box>
                                            </Box>
                                        </CardContent>
                                    </CardActionArea>
                                </Card>
                            </Grid>
                        ))}
                    </Grid>
                )}
            </Box>
        </Container>
    );
}

export default MerchantSelectionPage;
