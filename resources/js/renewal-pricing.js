const numberOrNull = (value) => {
    if (value === '' || value === null || value === undefined) return null;
    const number = Number(value);
    return Number.isFinite(number) ? number : null;
};

const roundedSale = (value) => (Math.round((value + Number.EPSILON) * 100) / 100).toFixed(2);

// Uses the same cost-based percentage as the product and quote forms.
export default function renewalPricing(config) {
    return {
        cost: config.cost ?? '',
        sale: config.sale ?? '',
        rate: '',
        multiplier: null,
        originalCost: numberOrNull(config.originalCost),
        originalSale: numberOrNull(config.originalSale),
        init() {
            const cost = numberOrNull(this.cost);
            const sale = numberOrNull(this.sale);
            this.sale = sale === null ? '' : roundedSale(sale);
            this.multiplier = cost > 0 && sale !== null ? sale / cost : this.originalMultiplier;
            this.syncRate();
        },
        get originalMultiplier() {
            return this.originalCost > 0 && this.originalSale !== null ? this.originalSale / this.originalCost : null;
        },
        get suggestion() {
            const cost = numberOrNull(this.cost);
            return cost !== null && cost >= 0 && this.originalMultiplier !== null
                ? roundedSale(cost * this.originalMultiplier) : null;
        },
        syncRate() {
            this.rate = this.multiplier === null ? '' : ((this.multiplier - 1) * 100).toFixed(2);
        },
        calculateSale() {
            const cost = numberOrNull(this.cost);
            this.sale = cost !== null && cost >= 0 && this.multiplier !== null
                ? roundedSale(cost * this.multiplier) : '';
        },
        rateChanged() {
            const rate = numberOrNull(this.rate);
            this.multiplier = rate !== null && rate >= -100 ? 1 + rate / 100 : null;
            this.calculateSale();
        },
        saleChanged() {
            const cost = numberOrNull(this.cost);
            const sale = numberOrNull(this.sale);
            this.multiplier = cost > 0 && sale !== null && sale >= 0 ? sale / cost : null;
            this.syncRate();
        },
        roundSale() {
            const sale = numberOrNull(this.sale);
            this.sale = sale === null ? '' : roundedSale(sale);
            this.saleChanged();
        },
        useOriginalRate() {
            this.multiplier = this.originalMultiplier;
            this.syncRate();
            this.calculateSale();
        },
        format(value) {
            return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value));
        },
    };
}
