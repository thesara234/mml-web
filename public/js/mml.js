/* MML web — Alpine components shared by the staff console, customer portal and public site. */
(function () {
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

  async function api(url, { method = 'GET', body = null } = {}) {
    const res = await fetch(url, {
      method,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(body ? { 'Content-Type': 'application/json' } : {}),
        'X-CSRF-TOKEN': csrf(),
      },
      body: body ? JSON.stringify(body) : null,
      credentials: 'same-origin',
    });
    let data = null;
    try { data = await res.json(); } catch (_) { /* empty */ }
    if (!res.ok) {
      const err = new Error(data?.message || 'Something went wrong. Please try again.');
      err.status = res.status;
      err.errors = data?.errors || {};
      throw err;
    }
    return data;
  }

  const fmt = (n, ccy) => {
    const v = Number(n || 0).toLocaleString('en-NZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return ccy ? `${ccy} ${v}` : v;
  };
  const debounce = (fn, ms) => { let t; return function (...a) { clearTimeout(t); t = setTimeout(() => fn.apply(this, a), ms); }; };

  window.MML = { api, fmt };

  document.addEventListener('alpine:init', () => {
    /* ------------------------------------------------------------------
       Public-site calculator (hero)
       ------------------------------------------------------------------ */
    Alpine.data('rateCalc', (cfg) => ({
      corridors: cfg.corridors, local: cfg.local || 'NZD', foreign: cfg.foreign || 'TOP',
      amount: cfg.amount || 200, mode: 'send', quote: null, error: null, loading: false,
      init() {
        this.refresh = debounce(() => this.load(), 280);
        this.$watch('local', () => { if (!this.foreigns().includes(this.foreign)) this.foreign = this.foreigns()[0]; this.refresh(); });
        ['foreign', 'amount', 'mode'].forEach((k) => this.$watch(k, () => this.refresh()));
        this.load();
      },
      locals() { return [...new Set(this.corridors.map((c) => c.local))]; },
      foreigns() { return this.corridors.filter((c) => c.local === this.local).map((c) => c.foreign); },
      flip() {
        if (!this.quote) return;
        this.amount = this.mode === 'send' ? this.quote.converted_amount : this.quote.transfer_amount;
        this.mode = this.mode === 'send' ? 'receive' : 'send';
      },
      async load() {
        if (!(Number(this.amount) > 0)) { this.quote = null; return; }
        this.loading = true; this.error = null;
        try {
          this.quote = await api(cfg.url, { method: 'POST', body: { amount: this.amount, local_currency: this.local, foreign_currency: this.foreign, amount_mode: this.mode } });
        } catch (e) { this.error = Object.values(e.errors || {})[0]?.[0] || e.message; this.quote = null; }
        this.loading = false;
      },
      fmt,
    }));

    /* ------------------------------------------------------------------
       Send wizard — staff counter and customer portal
       ------------------------------------------------------------------ */
    Alpine.data('sendWizard', (cfg) => ({
      cfg,
      isStaff: cfg.mode === 'staff',
      step: 'amount',
      // sender (staff only)
      sender: cfg.sender || null, senderQuery: '', senderResults: [], senderSearching: false,
      // amount
      local: cfg.draft?.local_currency || cfg.defaultLocal || 'NZD',
      foreign: cfg.draft?.foreign_currency || 'TOP',
      amountMode: 'send',
      amount: cfg.draft?.transfer_amount || '',
      discount: 0,
      quote: null, quoteError: null, quoting: false,
      // delivery
      type: cfg.draft?.transfer_type || null,
      // shopping
      storeId: null, store: null, items: [], categories: [], category: '', itemQuery: '', cart: {}, loadingItems: false,
      // receiver
      receivers: cfg.receivers || [], receiverId: cfg.draft?.receiver_customer_id || cfg.preselect || null,
      receiverQuery: '', receiverResults: [], receiverSearching: false, picked: null,
      receiverOfficeId: cfg.draft?.receiver_office_id || null,
      // review
      purpose: cfg.draft?.purpose || '', income: cfg.draft?.sender_income_source || '',
      paymentMethod: cfg.draft?.payment_method || (cfg.mode === 'staff' ? 'cash' : 'internet_banking'),
      paidNow: true, paymentReference: '', notes: '',
      // ui
      errors: {}, submitting: false, banner: null,

      init() {
        this.requote = debounce(() => this.loadQuote(), 300);
        this.$watch('local', () => { if (!this.foreigns().includes(this.foreign)) this.foreign = this.foreigns()[0]; this.officeDefaults(); this.requote(); });
        this.$watch('foreign', () => { this.officeDefaults(); this.requote(); this.cart = {}; this.storeId = null; });
        ['amount', 'amountMode', 'discount'].forEach((k) => this.$watch(k, () => this.requote()));
        this.$watch('type', () => this.officeDefaults());
        this.$watch('receiverId', () => this.officeDefaults(true));
        if (cfg.draft?.items?.length) {
          cfg.draft.items.forEach((i) => { this.cart[i.stock_item_id] = i.quantity; });
          this.storeId = cfg.draft.receiver_office_id;
        }
        if (this.sender && this.isStaff) this.loadSenderReceivers();
        if (this.amount) this.loadQuote();
        this.officeDefaults();
      },

      // ── navigation ──────────────────────────────────────────────
      flow() { return this.type === 'goods' ? ['amount', 'delivery', 'shopping', 'receiver', 'review'] : ['amount', 'delivery', 'receiver', 'review']; },
      labels: { amount: 'Amount', delivery: 'Money or shopping', shopping: 'Shopping', receiver: 'Receiver', review: 'Review & pay' },
      idx() { return this.flow().indexOf(this.step); },
      isDone(s) { return this.flow().indexOf(s) < this.idx(); },
      go(s) { if (this.isDone(s)) { this.step = s; this.scrollTop(); } },
      scrollTop() { this.$nextTick(() => this.$root.scrollIntoView({ behavior: 'smooth', block: 'start' })); },
      next() {
        const problem = this.check(this.step);
        if (problem) { this.banner = problem; return; }
        this.banner = null;
        const f = this.flow();
        this.step = f[Math.min(f.indexOf(this.step) + 1, f.length - 1)];
        if (this.step === 'shopping') this.ensureStore();
        this.scrollTop();
      },
      back() { const f = this.flow(); this.step = f[Math.max(f.indexOf(this.step) - 1, 0)]; this.banner = null; this.scrollTop(); },
      check(s) {
        if (s === 'amount') {
          if (this.isStaff && !this.sender) return 'Choose who is sending first.';
          if (!(Number(this.amount) > 0)) return 'Enter an amount to send.';
          if (this.quoteError) return this.quoteError;
          if (!this.quote) return 'Getting the latest rate… try again in a moment.';
        }
        if (s === 'delivery' && !this.type) return 'Choose money or shopping.';
        if (s === 'shopping') {
          if (!this.cartCount()) return 'Add at least one item to the shopping list.';
          if (this.overBudget()) return `The shopping is ${this.fmt(this.cartTotal() - this.budget(), this.foreign)} over the amount being sent. Remove items or use “Match amount to shopping”.`;
        }
        if (s === 'receiver') {
          if (!this.receiverId) return 'Choose who will collect.';
          if (!this.receiverOfficeId) return 'Choose the collection office.';
        }
        return null;
      },

      // ── corridors & offices ─────────────────────────────────────
      locals() { return [...new Set(cfg.corridors.map((c) => c.local))]; },
      foreigns() { return cfg.corridors.filter((c) => c.local === this.local).map((c) => c.foreign); },
      collectOffices() { return cfg.receivingOffices.filter((o) => o.currency === this.foreign && (this.type !== 'goods' || o.has_store)); },
      stores() { return cfg.receivingOffices.filter((o) => o.currency === this.foreign && o.has_store); },
      officeName(id) { return cfg.receivingOffices.find((o) => o.id === Number(id))?.name || '—'; },
      officeDefaults(fromReceiver = false) {
        const list = this.collectOffices();
        if (this.type === 'goods' && this.storeId) { this.receiverOfficeId = this.storeId; return; }
        const pref = this.currentReceiver()?.preferred_office_id;
        if (fromReceiver && pref && list.some((o) => o.id === pref)) { this.receiverOfficeId = pref; return; }
        if (!list.some((o) => o.id === Number(this.receiverOfficeId))) this.receiverOfficeId = list[0]?.id || null;
      },

      // ── quote ───────────────────────────────────────────────────
      async loadQuote() {
        if (!(Number(this.amount) > 0)) { this.quote = null; this.quoteError = null; return; }
        this.quoting = true;
        try {
          this.quote = await api(cfg.urls.quote, { method: 'POST', body: { amount: this.amount, local_currency: this.local, foreign_currency: this.foreign, amount_mode: this.amountMode, discount: this.isStaff ? this.discount : 0 } });
          this.quoteError = null;
        } catch (e) { this.quote = null; this.quoteError = Object.values(e.errors || {})[0]?.[0] || e.message; }
        this.quoting = false;
      },
      flipMode() {
        if (this.quote) this.amount = this.amountMode === 'send' ? this.quote.converted_amount : this.quote.transfer_amount;
        this.amountMode = this.amountMode === 'send' ? 'receive' : 'send';
      },

      // ── sender (staff) ──────────────────────────────────────────
      searchSenders: debounce(async function () {
        if (this.senderQuery.trim().length < 2) { this.senderResults = []; return; }
        this.senderSearching = true;
        try { this.senderResults = await api(cfg.urls.customerSearch + '?q=' + encodeURIComponent(this.senderQuery)); } catch (_) { this.senderResults = []; }
        this.senderSearching = false;
      }, 250),
      chooseSender(c) { this.sender = c; this.senderResults = []; this.senderQuery = ''; this.receiverId = null; this.loadSenderReceivers(); },
      async loadSenderReceivers() {
        if (!this.sender) return;
        try { this.receivers = await api(cfg.urls.senderReceivers.replace('__ID__', this.sender.id)); } catch (_) { this.receivers = []; }
      },

      // ── receivers ───────────────────────────────────────────────
      searchReceivers: debounce(async function () {
        if (this.receiverQuery.trim().length < 3) { this.receiverResults = []; return; }
        this.receiverSearching = true;
        try {
          const rows = await api(cfg.urls.receiverSearch + '?q=' + encodeURIComponent(this.receiverQuery));
          this.receiverResults = rows.filter((r) => !this.sender || r.id !== this.sender.id);
        } catch (_) { this.receiverResults = []; }
        this.receiverSearching = false;
      }, 280),
      chooseReceiver(r) {
        if (!this.receivers.some((x) => x.id === r.id)) { this.picked = r; }
        this.receiverId = r.id; this.receiverResults = []; this.receiverQuery = '';
      },
      currentReceiver() { return this.receivers.find((r) => r.id === Number(this.receiverId)) || (this.picked?.id === Number(this.receiverId) ? this.picked : null); },
      newReceiverUrl() { return this.isStaff ? cfg.urls.newReceiver.replace('__ID__', this.sender?.id || '') : cfg.urls.newReceiver; },

      // ── shopping ────────────────────────────────────────────────
      ensureStore() {
        const stores = this.stores();
        if (!stores.some((s) => s.id === Number(this.storeId))) this.storeId = stores[0]?.id || null;
        this.loadItems();
      },
      async loadItems() {
        if (!this.storeId) { this.items = []; return; }
        this.loadingItems = true;
        try {
          const d = await api(cfg.urls.storeItems.replace('__ID__', this.storeId));
          this.items = d.items; this.categories = d.categories; this.store = d.office;
          this.receiverOfficeId = Number(this.storeId);
        } catch (_) { this.items = []; }
        this.loadingItems = false;
      },
      changeStore(id) {
        if (Number(id) === Number(this.storeId)) return;
        if (this.cartCount() && !confirm('Changing store empties the shopping list. Continue?')) return;
        this.cart = {}; this.storeId = Number(id); this.loadItems();
      },
      visibleItems() {
        const q = this.itemQuery.trim().toLowerCase();
        return this.items.filter((i) => (!this.category || i.category === this.category) && (!q || i.name.toLowerCase().includes(q)));
      },
      qty(id) { return this.cart[id] || 0; },
      add(item) { const q = this.qty(item.id); if (q < item.in_stock) this.cart = { ...this.cart, [item.id]: q + 1 }; },
      sub(item) { const q = this.qty(item.id); const c = { ...this.cart }; if (q <= 1) delete c[item.id]; else c[item.id] = q - 1; this.cart = c; },
      cartLines() { return this.items.filter((i) => this.cart[i.id]).map((i) => ({ ...i, qty: this.cart[i.id], total: i.price * this.cart[i.id] })); },
      cartCount() { return Object.values(this.cart).reduce((a, b) => a + b, 0); },
      cartTotal() { return Math.round(this.cartLines().reduce((a, l) => a + l.total, 0) * 100) / 100; },
      budget() { return this.quote ? this.quote.converted_amount : 0; },
      overBudget() { return this.cartTotal() > this.budget() + 0.001; },
      budgetPct() { return this.budget() ? Math.min(100, (this.cartTotal() / this.budget()) * 100) : 0; },
      matchToCart() { if (!this.cartTotal()) return; this.amountMode = 'receive'; this.amount = this.cartTotal(); },

      // ── submit ──────────────────────────────────────────────────
      payload(action) {
        return {
          action,
          draft_id: cfg.draft?.id || null,
          sender_customer_id: this.sender?.id,
          transfer_type: this.type || 'money',
          amount: this.amount, amount_mode: this.amountMode,
          local_currency: this.local, foreign_currency: this.foreign,
          receiver_customer_id: this.receiverId || null,
          receiver_office_id: this.receiverOfficeId || null,
          items: this.type === 'goods' ? Object.entries(this.cart).map(([id, q]) => ({ stock_item_id: Number(id), quantity: q })) : [],
          purpose: this.purpose || null, sender_income_source: this.income || null,
          payment_method: this.paymentMethod || null,
          discount: this.isStaff ? this.discount : undefined,
          paid_now: this.isStaff ? this.paidNow : undefined,
          payment_reference: this.isStaff ? this.paymentReference : undefined,
          notes: this.notes || null,
        };
      },
      async submit(action = 'send') {
        if (action === 'send') {
          if (!this.purpose || !this.income || !this.paymentMethod) { this.banner = 'Fill in the purpose, source of funds and payment method.'; return; }
        } else if (!this.quote) { this.banner = 'Enter an amount before saving.'; return; }
        this.submitting = true; this.errors = {}; this.banner = null;
        try {
          const res = await api(cfg.urls.submit, { method: 'POST', body: this.payload(action) });
          window.location.href = res.redirect;
        } catch (e) {
          this.errors = e.errors || {};
          const first = Object.keys(this.errors)[0];
          this.banner = first ? this.errors[first][0] : e.message;
          const map = { amount: 'amount', transfer_amount: 'amount', local_currency: 'amount', foreign_currency: 'amount', sender_customer_id: 'amount', items: 'shopping', receiver_customer_id: 'receiver', receiver_office_id: 'receiver' };
          if (first && map[first] && this.flow().includes(map[first])) this.step = map[first];
          this.submitting = false;
        }
      },
      fmt,
      initials(p) { return ((p?.given_name || p?.full_name || '?')[0] + (p?.family_name?.[0] || '')).toUpperCase(); },
    }));

    /* ------------------------------------------------------------------
       Chat (staff ⇄ customer), polling
       ------------------------------------------------------------------ */
    Alpine.data('chatBox', (cfg) => ({
      messages: [], body: '', sending: false, lastId: 0, timer: null, error: null,
      init() {
        this.poll();
        this.timer = setInterval(() => { if (!document.hidden) this.poll(); }, cfg.pollMs || 5000);
      },
      destroy() { clearInterval(this.timer); },
      mine(m) { return m.sender_type === cfg.me; },
      async poll() {
        try {
          const d = await api(cfg.pollUrl + '?after_id=' + this.lastId);
          if (d.messages.length) {
            const nearBottom = this.atBottom();
            this.messages.push(...d.messages);
            this.lastId = d.messages[d.messages.length - 1].id;
            if (nearBottom || this.messages.length === d.messages.length) this.toBottom();
          }
          this.error = null;
        } catch (e) { this.error = 'Reconnecting…'; }
      },
      atBottom() { const el = this.$refs.list; return !el || el.scrollHeight - el.scrollTop - el.clientHeight < 80; },
      toBottom() { this.$nextTick(() => { const el = this.$refs.list; if (el) el.scrollTop = el.scrollHeight; }); },
      async send() {
        const text = this.body.trim();
        if (!text || this.sending) return;
        this.sending = true;
        try {
          const m = await api(cfg.sendUrl, { method: 'POST', body: { body: text } });
          this.body = '';
          if (!this.messages.some((x) => x.id === m.id)) { this.messages.push(m); this.lastId = Math.max(this.lastId, m.id); }
          this.toBottom();
        } catch (e) { this.error = e.message; }
        this.sending = false;
      },
      keydown(e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); this.send(); } },
    }));
  });

  // Small global helpers
  document.addEventListener('click', (e) => {
    const open = e.target.closest('[data-open-dialog]');
    if (open) { document.getElementById(open.dataset.openDialog)?.showModal(); }
    const close = e.target.closest('[data-close-dialog]');
    if (close) { close.closest('dialog')?.close(); }
    const copy = e.target.closest('[data-copy]');
    if (copy) { navigator.clipboard?.writeText(copy.dataset.copy); copy.textContent = 'Copied'; setTimeout(() => (copy.textContent = 'Copy'), 1500); }
    const row = e.target.closest('tr[data-href]');
    if (row && !e.target.closest('a,button,form')) { window.location.href = row.dataset.href; }
    const nav = e.target.closest('[data-toggle-nav]');
    if (nav) { document.querySelector(nav.dataset.toggleNav)?.classList.toggle('is-open'); }
  });
})();
