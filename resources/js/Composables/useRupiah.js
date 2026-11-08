export function useRupiah() {
    const formatRupiah = (value, prefix = true) => {
        if (value === null || value === undefined || isNaN(value)) {
            return prefix ? 'Rp 0' : '0';
        }
        const num = Math.round(Number(value));
        const formatted = new Intl.NumberFormat('id-ID').format(num);
        return prefix ? `Rp ${formatted}` : formatted;
    };

    const parseRupiah = (value) => {
        if (typeof value === 'number') return Math.round(value);
        if (!value) return 0;
        const cleaned = value.toString().replace(/[^0-9]/g, '');
        return cleaned ? parseInt(cleaned, 10) : 0;
    };

    return {
        formatRupiah,
        parseRupiah,
    };
}

