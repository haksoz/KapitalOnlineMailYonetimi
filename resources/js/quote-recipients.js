export default function quoteRecipients(config) {
    return {
        addresses: config.addresses ?? [],
        selected: config.selected ?? [],
        newEmail: '',
        error: '',
        sending: false,
        addEmail() {
            const email = this.newEmail.trim();
            if (!email || !this.$refs.newEmail.checkValidity()) {
                this.error = 'Geçerli bir e-posta adresi girin.';
                return false;
            }
            const existing = this.addresses.find((address) => address.toLowerCase() === email.toLowerCase());
            const address = existing ?? email;
            if (!existing) this.addresses.push(address);
            if (!this.selected.includes(address)) this.selected.push(address);
            this.newEmail = '';
            this.error = '';
            return true;
        },
        async send() {
            if (this.sending) return;
            if (this.newEmail.trim() && !this.addEmail()) return;
            if (!this.selected.length) {
                this.error = 'En az bir e-posta adresi seçin.';
                return;
            }
            await this.$nextTick();
            if (!this.$el.reportValidity()) return;
            this.sending = true;
            this.$el.submit();
        },
    };
}
