export function useCurrencyFormat() {
    const formatCurrency = (value: number | string | null | undefined): string => {
        if (value === null || value === undefined || isNaN(Number(value))) {
            return 'R$ 0,00';
        }
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
        }).format(Number(value));
    };

    const formatNumber = (value: number | string | null | undefined): string => {
        if (value === null || value === undefined || isNaN(Number(value))) {
            return '0,00';
        }
        return new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(Number(value));
    };

    return {
        formatCurrency,
        formatNumber,
    };
}
