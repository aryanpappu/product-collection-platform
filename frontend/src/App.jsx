import {BrowserRouter as Router, Route, Routes} from 'react-router-dom';
import {QueryClient, QueryClientProvider} from '@tanstack/react-query';
import {ThemeProvider} from '@mui/material/styles';
import CssBaseline from '@mui/material/CssBaseline';
import {ToastContainer} from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

// Theme
import theme from './theme/theme';

// Context
import {MerchantProvider} from './context/MerchantContext';

// Layout
import MainLayout from './components/layout/MainLayout';

// Pages
import MerchantSelectionPage from './pages/MerchantSelectionPage';
import DashboardPage from './pages/DashboardPage';
import CollectionListPage from './pages/CollectionListPage';
import CollectionDetailPage from './pages/CollectionDetailPage';
import ImportsPage from './pages/ImportsPage';
import ImportDetailPage from './pages/ImportDetailPage';

// Components
import ProtectedRoute from './components/common/ProtectedRoute';

// Create a query client
const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            refetchOnWindowFocus: false,
            retry: 1,
            staleTime: 5 * 60 * 1000, // 5 minutes
        },
    },
});

function App() {
    return (
        <QueryClientProvider client={queryClient}>
            <MerchantProvider>
                <ThemeProvider theme={theme}>
                    <CssBaseline/>
                    <Router>
                        <Routes>
                            <Route path="/" element={<MerchantSelectionPage/>}/>

                            <Route
                                element={
                                    <ProtectedRoute>
                                        <MainLayout/>
                                    </ProtectedRoute>
                                }
                            >
                                {/* Dashboard */}
                                <Route path="/dashboard" element={<DashboardPage/>}/>

                                {/* Collections */}
                                <Route path="/collections" element={<CollectionListPage/>}/>
                                <Route path="/collections/:id" element={<CollectionDetailPage/>}/>

                                {/* Imports */}
                                <Route path="/imports" element={<ImportsPage/>}/>
                                <Route path="/imports/:importJobId" element={<ImportDetailPage/>}/>
                            </Route>
                        </Routes>
                    </Router>
                    <ToastContainer
                        position="top-right"
                        autoClose={3000}
                        hideProgressBar={false}
                        newestOnTop={true}
                        closeOnClick
                        rtl={false}
                        pauseOnFocusLoss
                        draggable
                        pauseOnHover
                    />
                </ThemeProvider>
            </MerchantProvider>
        </QueryClientProvider>
    );
}

export default App;
