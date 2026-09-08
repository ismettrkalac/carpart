import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Debounced, AJAX-driven quantity control for one cart line. A full-page
 * form submit on every quantity nudge (the previous behaviour) reloads
 * the whole page and resets scroll position on every adjustment, which
 * reads as "bouncy" — this keeps a short debounce (mainly to collapse a
 * rapid double-click/keystroke) but replaces the page reload with an
 * in-place update via fetch(), a pulse while the request is in flight,
 * and a brief highlight when the new total lands.
 */
Alpine.data('cartQuantity', ({ quantity, max, updateUrl, removeUrl }) => ({
    quantity,
    max,
    updating: false,
    justUpdated: false,
    error: null,
    recalculate: null,

    init() {
        this.recalculate = Alpine.debounce(() => this.submit(), 200);
    },

    increment() {
        this.quantity = Math.min(this.max, this.quantity + 1);
        this.recalculate();
    },

    decrement() {
        this.quantity = Math.max(1, this.quantity - 1);
        this.recalculate();
    },

    csrfToken() {
        return this.$root.querySelector('input[name="_token"]').value;
    },

    async submit() {
        this.updating = true;
        this.error = null;

        try {
            const response = await fetch(updateUrl, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                body: JSON.stringify({ quantity: this.quantity }),
            });

            const data = await response.json();

            if (!response.ok) {
                this.error = data.message ?? 'Could not update this item.';

                return;
            }

            this.$root.querySelector('[data-line-total]').textContent = data.item.lineTotalFormatted;
            this.$dispatch('cart-line-updated', data);
            this.flash();
        } catch {
            this.error = 'Could not update this item. Please check your connection.';
        } finally {
            this.updating = false;
        }
    },

    async remove() {
        this.updating = true;

        try {
            const response = await fetch(removeUrl, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
            });

            const data = await response.json();

            if (!response.ok) {
                window.location.reload();

                return;
            }

            // Dispatch while still attached so the event bubbles to the
            // summary card's listener (which reveals the empty-cart
            // state if this was the last line), then remove this line.
            this.$dispatch('cart-line-updated', data);
            this.$root.remove();
        } catch {
            window.location.reload();
        }
    },

    /**
     * Briefly highlights the line total so a successful update is
     * unmistakable even though nothing else visibly moves.
     */
    flash() {
        this.justUpdated = true;
        setTimeout(() => {
            this.justUpdated = false;
        }, 600);
    },
}));

Alpine.start();
