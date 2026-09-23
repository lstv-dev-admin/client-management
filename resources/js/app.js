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
        combining: false,
        collisions: [],
        combineIds: (config.oldCombineProducts || []).map(String),
        productIds: (config.oldProducts || []).map(String),
        contactIds: (config.oldContacts || []).map(String),
        sourceProducts: config.products || [],
        allProductIds: (config.products || []).map((product) => String(product.recid ?? product)),
        allContactIds: (config.contacts || []).map(String),
        init() {
            if (config.oldTarget) {
                this.choose({ recid: config.oldTarget });
            }
        },
        get ready() {
            return Boolean(this.target) && (this.productIds.length + this.contactIds.length) > 0;
        },
        productNameKey(name) {
            return String(name ?? '').trim().toLowerCase();
        },
        findCollisions() {
            if (! this.target) {
                return [];
            }

            const targetByName = new Map();

            (this.target.products || []).forEach((product) => {
                const key = this.productNameKey(product.prdname);

                if (key !== '' && ! targetByName.has(key)) {
                    targetByName.set(key, product);
                }
            });

            return this.sourceProducts
                .filter((product) => this.productIds.includes(String(product.recid)))
                .map((product) => {
                    const match = targetByName.get(this.productNameKey(product.prdname));

                    if (! match) {
                        return null;
                    }

                    return { source: product, target: match };
                })
                .filter(Boolean);
        },
        requestMerge(event) {
            if (this.combining || event.target.dataset.combineReady === '1') {
                delete event.target.dataset.combineReady;

                return;
            }

            this.collisions = this.findCollisions();

            if (this.collisions.length === 0) {
                return;
            }

            event.preventDefault();
            this.combineIds = this.collisions.map((row) => String(row.source.recid));
            this.combining = true;
        },
        cancelCombine() {
            this.combining = false;
            this.collisions = [];
            this.combineIds = [];
        },
        confirmCombine(event) {
            const form = event.target.closest('form');

            this.combining = false;
            form.dataset.combineReady = '1';
            form.requestSubmit();
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
            this.cancelCombine();
        },
        clearTarget() {
            this.target = null;
            this.cancelCombine();
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
