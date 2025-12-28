import {createContext, useContext, useEffect, useState} from 'react';

const MerchantContext = createContext();

// localStorage key
const MERCHANT_STORAGE_KEY = 'selected_merchant';

export const MerchantProvider = ({children}) => {
    const [selectedMerchant, setSelectedMerchant] = useState(null);
    const [isLoading, setIsLoading] = useState(true);

    // Load merchant from localStorage on mount
    useEffect(() => {
        const storedMerchant = localStorage.getItem(MERCHANT_STORAGE_KEY);
        if (storedMerchant) {
            try {
                const merchant = JSON.parse(storedMerchant);
                setSelectedMerchant(merchant);
            } catch (error) {
                console.error('Failed to parse stored merchant:', error);
                localStorage.removeItem(MERCHANT_STORAGE_KEY);
            }
        }
        setIsLoading(false);
    }, []);

    // Save merchant to localStorage and state
    const selectMerchant = (merchant) => {
        setSelectedMerchant(merchant);
        localStorage.setItem(MERCHANT_STORAGE_KEY, JSON.stringify(merchant));
    };

    // Clear merchant from localStorage and state
    const clearMerchant = () => {
        setSelectedMerchant(null);
        localStorage.removeItem(MERCHANT_STORAGE_KEY);
    };

    const value = {
        selectedMerchant,
        selectMerchant,
        clearMerchant,
        isLoading,
    };

    return (
        <MerchantContext.Provider value={value}>
            {children}
        </MerchantContext.Provider>
    );
};

// Custom hook to use merchant context
export const useMerchant = () => {
    const context = useContext(MerchantContext);
    if (!context) {
        throw new Error('useMerchant must be used within MerchantProvider');
    }
    return context;
};
