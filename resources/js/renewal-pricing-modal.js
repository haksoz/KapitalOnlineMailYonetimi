import renewalPricing from './renewal-pricing';

export default function renewalPricingModal(config) {
    return Object.assign(renewalPricing(config), {
        saving: false,
        error: '',
        async save() {
            if (this.saving) return;
            this.saving = true;
            this.error = '';
            try {
                const response = await fetch(this.$el.getAttribute('action'), {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: new FormData(this.$el),
                });
                const data = await response.json();
                if (!response.ok) {
                    this.error = Object.values(data.errors ?? {}).flat().join(' ') || data.message || 'Fiyat kontrolü kaydedilemedi.';
                    return;
                }
                this.$dispatch('pricing-saved', { id: config.id });
                this.$dispatch('close-modal', `pricing-${config.id}`);
            } catch {
                this.error = 'Kaydetme sonucu alınamadı. Bağlantınızı kontrol edip tekrar deneyin.';
            } finally {
                this.saving = false;
            }
        },
    });
}
