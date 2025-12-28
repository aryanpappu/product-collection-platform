import {useNavigate} from 'react-router-dom';
import {Box, Card, CardActionArea, CardContent, Container, Grid, Typography,} from '@mui/material';
import {Collections as CollectionsIcon, TrendingUp as TrendingUpIcon, Upload as UploadIcon,} from '@mui/icons-material';

function DashboardPage() {
    const navigate = useNavigate();
    return (
        <Container maxWidth="lg">
            <Box py={4}>
                <Typography variant="h4" component="h1" fontWeight="bold" gutterBottom>
                    Dashboard
                </Typography>
                <Box mt={6}>
                    <Grid container spacing={2} mt={1}>
                        <Grid item xs={12} sm={6} md={4}>
                            <Card>
                                <CardActionArea onClick={() => navigate('/collections')}>
                                    <CardContent>
                                        <Box display="flex" alignItems="center">
                                            <CollectionsIcon sx={{mr: 2, color: 'primary.main'}}/>
                                            <Typography variant="body1" fontWeight="medium">
                                                Manage Collections
                                            </Typography>
                                        </Box>
                                    </CardContent>
                                </CardActionArea>
                            </Card>
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <Card>
                                <CardActionArea onClick={() => navigate('/imports')}>
                                    <CardContent>
                                        <Box display="flex" alignItems="center">
                                            <UploadIcon sx={{mr: 2, color: 'secondary.main'}}/>
                                            <Typography variant="body1" fontWeight="medium">
                                                Upload CSV
                                            </Typography>
                                        </Box>
                                    </CardContent>
                                </CardActionArea>
                            </Card>
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <Card>
                                <CardActionArea onClick={() => navigate('/imports')}>
                                    <CardContent>
                                        <Box display="flex" alignItems="center">
                                            <TrendingUpIcon sx={{mr: 2, color: 'info.main'}}/>
                                            <Typography variant="body1" fontWeight="medium">
                                                View Import History
                                            </Typography>
                                        </Box>
                                    </CardContent>
                                </CardActionArea>
                            </Card>
                        </Grid>
                    </Grid>
                </Box>
            </Box>
        </Container>
    );
}

export default DashboardPage;
