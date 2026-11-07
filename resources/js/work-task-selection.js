export default function workTaskSelection(tasks) {
    return {
        selected: [],
        get selectedCustomerId() {
            return this.selected.length ? tasks[this.selected[0]]?.customer : null;
        },
        get customerName() {
            return this.selected.length ? tasks[this.selected[0]]?.name : '';
        },
        matchesCustomer(customerId) {
            return this.selected.length === 0 || String(this.selectedCustomerId) === String(customerId);
        },
    };
}
