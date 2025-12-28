import {useState} from 'react';
import {Outlet, useLocation, useNavigate} from 'react-router-dom';
import {
    AppBar,
    Box,
    Divider,
    Drawer,
    IconButton,
    List,
    ListItem,
    ListItemButton,
    ListItemIcon,
    ListItemText,
    Toolbar,
    Typography,
    useMediaQuery,
    useTheme,
} from '@mui/material';
import {
    Close as CloseIcon,
    Collections as CollectionsIcon,
    Dashboard as DashboardIcon,
    Menu as MenuIcon,
    Upload as UploadIcon,
} from '@mui/icons-material';
import MerchantSwitcher from '../common/MerchantSwitcher';

const drawerWidth = 260;

const menuItems = [
    {
        title: 'Dashboard',
        icon: <DashboardIcon/>,
        path: '/dashboard',
    },
    {
        title: 'Collections',
        icon: <CollectionsIcon/>,
        path: '/collections',
    },
    {
        title: 'Import Products',
        icon: <UploadIcon/>,
        path: '/imports',
    },
];

function MainLayout() {
    const theme = useTheme();
    const isMobile = useMediaQuery(theme.breakpoints.down('md'));
    const [mobileOpen, setMobileOpen] = useState(false);
    const navigate = useNavigate();
    const location = useLocation();

    const handleDrawerToggle = () => {
        setMobileOpen(!mobileOpen);
    };

    const handleNavigation = (path) => {
        navigate(path);
        if (isMobile) {
            setMobileOpen(false);
        }
    };

    const drawer = (
        <Box sx={{height: '100%', display: 'flex', flexDirection: 'column'}}>
            {/* Drawer Header */}
            <Box
                sx={{
                    p: 2,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    background: `linear-gradient(135deg, ${theme.palette.primary.main} 0%, ${theme.palette.primary.dark} 100%)`,
                    color: 'white',
                }}
            >
                <Typography variant="h6" fontWeight="bold">
                    Product Platform
                </Typography>
                {isMobile && (
                    <IconButton
                        color="inherit"
                        onClick={handleDrawerToggle}
                        sx={{ml: 1}}
                    >
                        <CloseIcon/>
                    </IconButton>
                )}
            </Box>

            <Divider/>

            {/* Merchant Switcher */}
            <Box sx={{p: 2}}>
                <MerchantSwitcher/>
            </Box>

            <Divider/>

            {/* Navigation Menu */}
            <List sx={{flexGrow: 1, pt: 2}}>
                {menuItems.map((item) => {
                    const isActive = location.pathname.startsWith(item.path);
                    return (
                        <ListItem key={item.path} disablePadding sx={{px: 2, mb: 0.5}}>
                            <ListItemButton
                                onClick={() => handleNavigation(item.path)}
                                sx={{
                                    borderRadius: 2,
                                    backgroundColor: isActive ? theme.palette.primary.main : 'transparent',
                                    color: isActive ? 'white' : 'inherit',
                                    '&:hover': {
                                        backgroundColor: isActive
                                            ? theme.palette.primary.dark
                                            : theme.palette.action.hover,
                                    },
                                    '& .MuiListItemIcon-root': {
                                        color: isActive ? 'white' : theme.palette.text.secondary,
                                    },
                                }}
                            >
                                <ListItemIcon>{item.icon}</ListItemIcon>
                                <ListItemText
                                    primary={item.title}
                                    primaryTypographyProps={{
                                        fontWeight: isActive ? 600 : 400,
                                    }}
                                />
                            </ListItemButton>
                        </ListItem>
                    );
                })}
            </List>

            <Divider/>

        </Box>
    );

    return (
        <Box sx={{display: 'flex', minHeight: '100vh'}}>
            {/* App Bar - Only on mobile */}
            {isMobile && (
                <AppBar
                    position="fixed"
                    sx={{
                        backgroundColor: 'white',
                        color: 'text.primary',
                        boxShadow: '0 1px 3px 0 rgb(0 0 0 / 0.1)',
                    }}
                >
                    <Toolbar>
                        <IconButton
                            color="inherit"
                            edge="start"
                            onClick={handleDrawerToggle}
                            sx={{mr: 2}}
                        >
                            <MenuIcon/>
                        </IconButton>
                        <Typography variant="h6" noWrap component="div" fontWeight="bold">
                            Product Platform
                        </Typography>
                    </Toolbar>
                </AppBar>
            )}

            {/* Drawer */}
            <Box
                component="nav"
                sx={{width: {md: drawerWidth}, flexShrink: {md: 0}}}
            >
                {/* Mobile Drawer */}
                <Drawer
                    variant="temporary"
                    open={mobileOpen}
                    onClose={handleDrawerToggle}
                    ModalProps={{
                        keepMounted: true, // Better mobile performance
                    }}
                    sx={{
                        display: {xs: 'block', md: 'none'},
                        '& .MuiDrawer-paper': {
                            boxSizing: 'border-box',
                            width: drawerWidth,
                        },
                    }}
                >
                    {drawer}
                </Drawer>

                {/* Desktop Drawer */}
                <Drawer
                    variant="permanent"
                    sx={{
                        display: {xs: 'none', md: 'block'},
                        '& .MuiDrawer-paper': {
                            boxSizing: 'border-box',
                            width: drawerWidth,
                        },
                    }}
                    open
                >
                    {drawer}
                </Drawer>
            </Box>

            {/* Main Content */}
            <Box
                component="main"
                sx={{
                    flexGrow: 1,
                    width: {md: `calc(100% - ${drawerWidth}px)`},
                    minHeight: '100vh',
                    backgroundColor: theme.palette.background.default,
                    pt: {xs: 8, md: 0},
                }}
            >
                <Outlet/>
            </Box>
        </Box>
    );
}

export default MainLayout;
