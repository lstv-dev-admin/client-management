import './bootstrap';
import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.store('confirm', {
        open: false,
        message: '',
        danger: false,
        form: null,
        ask(event, message, danger = false) {
            const form = event.target.closest('form');

            if (form?.dataset.confirmed === '1') {
                delete form.dataset.confirmed;

                return;
            }

            event.preventDefault();
            this.message = message;
            this.danger = danger;
            this.form = form;
            this.open = true;
            Alpine.nextTick(() => document.getElementById('confirm-accept')?.focus());
        },
        prompt(form, message, danger = false) {
            this.message = message;
            this.danger = danger;
            this.form = form;
            this.open = true;
            Alpine.nextTick(() => document.getElementById('confirm-accept')?.focus());
        },
        accept() {
            const form = this.form;
            this.open = false;
            this.form = null;

            if (! form) {
                return;
            }

            form.dataset.confirmed = '1';
            form.requestSubmit();
        },
        cancel() {
            this.open = false;
            this.form = null;
        },
    });

    Alpine.data('mergeDialog', (config) => ({
        open: Boolean(config.reopen),
        query: '',
        results: [],
        target: null,
        searching: false,
        productIds: (config.oldProducts || []).map(String),
        contactIds: (config.oldContacts || []).map(String),
        allProductIds: (config.products || []).map(String),
        allContactIds: (config.contacts || []).map(String),
        init() {
            if (config.oldTarget) {
                this.choose({ recid: config.oldTarget });
            }
        },
        get ready() {
            return Boolean(this.target) && (this.productIds.length + this.contactIds.length) > 0;
        },
        async search() {
            const term = this.query.trim();

            if (term === '') {
                this.results = [];

                return;
            }

            this.searching = true;

            try {
                const url = new URL(config.searchUrl, window.location.origin);
                url.searchParams.set('q', term);
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                });
                this.results = response.ok ? await response.json() : [];
            } finally {
                this.searching = false;
            }
        },
        async choose(client) {
            const response = await fetch(String(config.summaryUrl).replace('999999999', client.recid), {
                headers: { Accept: 'application/json' },
            });

            if (! response.ok) {
                return;
            }

            this.target = await response.json();
            this.results = [];
            this.query = '';
        },
        clearTarget() {
            this.target = null;
        },
        highlight(value) {
            const words = this.query.trim().split(/\s+/).filter(Boolean);
            let safe = String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');

            words.forEach((word) => {
                const pattern = new RegExp(`(${word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'ig');
                safe = safe.replace(pattern, '<mark class="rounded bg-yellow-200 px-0.5">$1</mark>');
            });

            return safe;
        },
    }));
});

window.Alpine = Alpine;
Alpine.start();
