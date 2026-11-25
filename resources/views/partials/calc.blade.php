{{-- Live calculator. Needs $rates (ExchangeRate::board()) --}}
@php($corridors = $rates->map(fn ($r) => ['local' => $r->local_currency, 'foreign' => $r->foreign_currency])->values())
<div class="calc" x-data="rateCalc({ url: '{{ route('public.quote') }}', corridors: @js($corridors), local: 'NZD', foreign: 'TOP', amount: 200 })" x-cloak>
    <h2><span>How much will they get?</span><span class="muted small" x-show="loading">Updating…</span></h2>
    <div class="amount-box">
        <label for="calc-amt" x-text="mode === 'send' ? 'You send' : 'They receive'"></label>
        <div class="amount-row">
            <input id="calc-amt" type="number" min="1" step="1" inputmode="decimal" x-model.number="amount">
            <template x-if="mode === 'send'">
                <select x-model="local" aria-label="Currency you send"><template x-for="c in locals()" :key="c"><option :value="c" x-text="c" :selected="c === local"></option></template></select>
            </template>
            <template x-if="mode === 'receive'"><span class="ccy" x-text="foreign"></span></template>
        </div>
    </div>
    <div class="swap-line">
        <button type="button" class="btn btn-ghost btn-sm" @click="flip()" title="Switch between send and receive amounts"><i class="icon-arrow-down-up"></i></button>
        <span class="rate-pill" x-show="quote" x-text="'1 ' + local + ' = ' + (quote ? quote.rate : '') + ' ' + foreign"></span>
    </div>
    <div class="amount-box" style="background:var(--moana-50)">
        <label x-text="mode === 'send' ? 'They receive' : 'You send'"></label>
        <div class="amount-row">
            <input type="text" readonly tabindex="-1" :value="quote ? fmt(mode === 'send' ? quote.converted_amount : quote.transfer_amount) : '—'" aria-live="polite">
            <template x-if="mode === 'send'">
                <select x-model="foreign" aria-label="Currency they receive"><template x-for="c in foreigns()" :key="c"><option :value="c" x-text="c" :selected="c === foreign"></option></template></select>
            </template>
            <template x-if="mode === 'receive'"><span class="ccy" x-text="local"></span></template>
        </div>
    </div>
    <p class="error-text" x-show="error" x-text="error" style="margin:.6rem 0 0"></p>
    <div class="calc-foot" x-show="quote">
        <span>Service fee <strong x-text="quote ? fmt(quote.service_fee, local) : ''"></strong></span>
        <span>You pay <strong x-text="quote ? fmt(quote.total_payable, local) : ''"></strong></span>
    </div>
    <a href="{{ route('portal.login') }}" class="btn btn-red btn-lg btn-block" style="margin-top:1rem"><i class="icon-send"></i>Send now</a>
</div>
