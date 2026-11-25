{{--
    Send money / shopping wizard shared by the staff counter screen and the customer portal.
    Expects: $cfg (array passed to the Alpine component), $mode ('staff' | 'portal')
--}}
@php($purposes = \App\Models\Transaction::PURPOSES)
@php($incomes = \App\Models\Transaction::INCOME_SOURCES)
<div x-data="sendWizard(@js($cfg))" x-cloak style="scroll-margin-top:90px">
    <ol class="steps" aria-label="Progress">
        <template x-for="s in flow()" :key="s">
            <li :class="{ 'is-current': s === step, 'is-done': isDone(s) }" @click="go(s)" x-text="labels[s]" :aria-current="s === step ? 'step' : null"></li>
        </template>
    </ol>

    <div class="alert alert-error mb-1" x-show="banner" role="alert"><i class="icon-circle-alert"></i><div x-text="banner"></div></div>

    <div class="grid grid-main-side">
        <div class="card">
            {{-- ───────────── 1. Amount ───────────── --}}
            <section x-show="step === 'amount'">
                <div class="card-head"><h2>How much is being sent?</h2></div>
                <div class="card-body">
                    @if ($mode === 'staff')
                        <div class="field">
                            <label>Sender</label>
                            <template x-if="sender">
                                <div class="pick is-selected" style="cursor:default">
                                    <span class="avatar" x-text="initials(sender)"></span>
                                    <div class="grow"><strong x-text="sender.full_name"></strong><br><small class="muted" x-text="sender.customer_code + (sender.phone ? ', ' + sender.phone : '')"></small></div>
                                    <span class="badge tone-green plain" x-show="sender.is_verified">ID verified</span>
                                    <span class="badge tone-amber plain" x-show="!sender.is_verified">ID not verified</span>
                                    <button type="button" class="btn btn-sm btn-ghost" @click="sender = null; receivers = []; receiverId = null">Change</button>
                                </div>
                            </template>
                            <div x-show="!sender">
                                <input class="input" type="search" x-model="senderQuery" @input="searchSenders()" placeholder="Search by name, customer code, phone or email" autofocus>
                                <div class="picker-list" style="margin-top:.5rem" x-show="senderResults.length">
                                    <template x-for="c in senderResults" :key="c.id">
                                        <button type="button" class="pick" @click="chooseSender(c)">
                                            <span class="avatar" x-text="initials(c)"></span>
                                            <div class="grow"><strong x-text="c.full_name"></strong><br><small class="muted" x-text="c.customer_code + ', ' + (c.city || c.country || '')"></small></div>
                                            <i class="icon-chevron-right muted"></i>
                                        </button>
                                    </template>
                                </div>
                                <p class="hint" x-show="senderQuery.length > 1 && !senderSearching && !senderResults.length">
                                    No match. <a href="{{ route('staff.customers.create') }}">Register a new customer</a>
                                </p>
                            </div>
                        </div>
                    @endif

                    <div class="row-between" style="margin-bottom:.6rem">
                        <span class="label">Enter the amount in</span>
                        <div class="segmented" role="radiogroup" aria-label="Amount entered as">
                            <button type="button" :class="{ 'is-active': amountMode === 'send' }" @click="amountMode !== 'send' && flipMode()" x-text="local + ' (you pay)'"></button>
                            <button type="button" :class="{ 'is-active': amountMode === 'receive' }" @click="amountMode !== 'receive' && flipMode()" x-text="foreign + ' (they get)'"></button>
                        </div>
                    </div>

                    <div class="amount-box">
                        <label for="amt" x-text="amountMode === 'send' ? 'Sending' : 'Receiver gets'"></label>
                        <div class="amount-row">
                            <input id="amt" type="number" min="1" step="0.01" inputmode="decimal" placeholder="0.00" x-model="amount">
                            <template x-if="amountMode === 'send'">
                                <select x-model="local" aria-label="Sending currency"><template x-for="c in locals()" :key="c"><option :value="c" x-text="c" :selected="c === local"></option></template></select>
                            </template>
                            <template x-if="amountMode === 'receive'">
                                <select x-model="foreign" aria-label="Receiving currency"><template x-for="c in foreigns()" :key="c"><option :value="c" x-text="c" :selected="c === foreign"></option></template></select>
                            </template>
                        </div>
                    </div>
                    <div class="swap-line">
                        <i class="icon-arrow-down"></i>
                        <span class="rate-pill" x-show="quote" x-text="'1 ' + local + ' = ' + (quote?.rate ?? '') + ' ' + foreign"></span>
                        <span class="muted" x-show="quoting">Checking today's rate…</span>
                    </div>
                    <div class="amount-box" style="background:var(--moana-50)">
                        <label x-text="amountMode === 'send' ? 'Receiver gets' : 'Sending'"></label>
                        <div class="amount-row">
                            <input type="text" readonly tabindex="-1" :value="quote ? fmt(amountMode === 'send' ? quote.converted_amount : quote.transfer_amount) : '—'">
                            <template x-if="amountMode === 'send'">
                                <select x-model="foreign" aria-label="Receiving currency"><template x-for="c in foreigns()" :key="c"><option :value="c" x-text="c" :selected="c === foreign"></option></template></select>
                            </template>
                            <template x-if="amountMode === 'receive'">
                                <select x-model="local" aria-label="Sending currency"><template x-for="c in locals()" :key="c"><option :value="c" x-text="c" :selected="c === local"></option></template></select>
                            </template>
                        </div>
                    </div>
                    <p class="error-text" x-show="quoteError" x-text="quoteError" style="margin-top:.6rem"></p>

                    @if ($mode === 'staff')
                        <div class="form-grid" style="margin-top:1rem">
                            <div class="field mb-0">
                                <label for="discount">Discount on fee</label>
                                <div class="input-group"><span class="addon" x-text="local"></span><input id="discount" class="input" type="number" min="0" step="0.01" x-model.number="discount"></div>
                                <span class="hint">Can waive part or all of the fee, never the amount sent.</span>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-foot">
                    @if ($mode === 'portal')
                        <button type="button" class="btn btn-ghost" @click="submit('draft')" :disabled="submitting || !quote"><i class="icon-bookmark"></i>Save for later</button>
                    @endif
                    <button type="button" class="btn btn-primary" @click="next()">Continue <i class="icon-chevron-right"></i></button>
                </div>
            </section>

            {{-- ───────────── 2. Money or shopping ───────────── --}}
            <section x-show="step === 'delivery'">
                <div class="card-head"><h2>What will the receiver collect?</h2></div>
                <div class="card-body">
                    <div class="choice-grid">
                        <label class="choice" :class="{ 'is-selected': type === 'money' }">
                            <input type="radio" value="money" x-model="type">
                            <span class="choice-icon"><i class="icon-banknote"></i></span>
                            <span><strong>Money</strong><small x-text="'Cash in ' + foreign + ' at an MML office'"></small></span>
                        </label>
                        <label class="choice" :class="{ 'is-selected': type === 'goods' }" x-show="stores().length">
                            <input type="radio" value="goods" x-model="type">
                            <span class="choice-icon"><i class="icon-shopping-basket"></i></span>
                            <span><strong>Shopping</strong><small>Goods from an MML store. Anything left over is paid in cash.</small></span>
                        </label>
                    </div>
                </div>
                <div class="card-foot">
                    <button type="button" class="btn btn-ghost" @click="back()"><i class="icon-chevron-left"></i>Back</button>
                    @if ($mode === 'portal')
                        <button type="button" class="btn btn-ghost" @click="submit('draft')" :disabled="submitting"><i class="icon-bookmark"></i>Save for later</button>
                    @endif
                    <button type="button" class="btn btn-primary" @click="next()">Continue <i class="icon-chevron-right"></i></button>
                </div>
            </section>

            {{-- ───────────── 3. Shopping ───────────── --}}
            <section x-show="step === 'shopping'">
                <div class="card-head">
                    <h2>Shopping list</h2>
                    <select class="select" style="width:auto;min-height:36px" :value="storeId" @change="changeStore($event.target.value)" aria-label="Store">
                        <template x-for="s in stores()" :key="s.id"><option :value="s.id" x-text="s.name" :selected="s.id === Number(storeId)"></option></template>
                    </select>
                </div>
                <div class="card-body stack-s">
                    <div class="budget" :class="{ 'is-over': overBudget() }">
                        <div class="row-between">
                            <strong x-text="fmt(cartTotal(), foreign) + ' of ' + fmt(budget(), foreign)"></strong>
                            <button type="button" class="btn btn-sm" @click="matchToCart()" :disabled="!cartCount()" title="Set the amount sent to exactly the shopping total"><i class="icon-equal"></i>Match amount to shopping</button>
                        </div>
                        <div class="meter"><span :style="'width:' + budgetPct() + '%'"></span></div>
                        <small x-show="!overBudget()" class="ink-2" x-text="'Cash paid with the shopping: ' + fmt(Math.max(0, budget() - cartTotal()), foreign)"></small>
                        <small x-show="overBudget()" class="error-text" x-text="'Over by ' + fmt(cartTotal() - budget(), foreign)"></small>
                    </div>
                    <div class="row wrap">
                        <input class="input" style="flex:1;min-width:180px" type="search" placeholder="Search items" x-model="itemQuery">
                        <div class="segmented" style="overflow-x:auto;max-width:100%">
                            <button type="button" :class="{ 'is-active': !category }" @click="category = ''">All</button>
                            <template x-for="c in categories" :key="c"><button type="button" :class="{ 'is-active': category === c }" @click="category = c" x-text="c"></button></template>
                        </div>
                    </div>
                    <p class="muted" x-show="loadingItems">Loading the store…</p>
                    <div class="products">
                        <template x-for="item in visibleItems()" :key="item.id">
                            <div class="product" :class="{ 'is-in': qty(item.id) }">
                                <div class="p-img">
                                    <template x-if="item.image_url"><img :src="item.image_url" alt=""></template>
                                    <template x-if="!item.image_url"><i class="icon-package"></i></template>
                                    <span class="tag" x-text="item.category"></span>
                                </div>
                                <div class="p-body">
                                    <span class="p-name" x-text="item.name"></span>
                                    <span class="p-price"><span x-text="fmt(item.price, item.currency)"></span> <small x-text="'/ ' + item.unit"></small></span>
                                    <small class="muted" x-text="item.in_stock > 0 ? item.in_stock + ' in stock' : 'Out of stock'"></small>
                                    <div class="qty" x-show="qty(item.id)">
                                        <button type="button" @click="sub(item)" aria-label="Remove one">−</button>
                                        <span x-text="qty(item.id)"></span>
                                        <button type="button" @click="add(item)" aria-label="Add one">+</button>
                                    </div>
                                    <button type="button" class="btn btn-sm" x-show="!qty(item.id)" @click="add(item)" :disabled="item.in_stock < 1" style="margin-top:auto;align-self:flex-start"><i class="icon-plus"></i>Add</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="card-foot">
                    <button type="button" class="btn btn-ghost" @click="back()"><i class="icon-chevron-left"></i>Back</button>
                    @if ($mode === 'portal')
                        <button type="button" class="btn btn-ghost" @click="submit('draft')" :disabled="submitting"><i class="icon-bookmark"></i>Save for later</button>
                    @endif
                    <button type="button" class="btn btn-primary" @click="next()">Continue <i class="icon-chevron-right"></i></button>
                </div>
            </section>

            {{-- ───────────── 4. Receiver ───────────── --}}
            <section x-show="step === 'receiver'">
                <div class="card-head">
                    <h2>Who is collecting?</h2>
                    <a :href="newReceiverUrl()" class="btn btn-sm"><i class="icon-user-plus"></i>Register new receiver</a>
                </div>
                <div class="card-body">
                    <div class="field" x-show="receivers.length">
                        <label>{{ $mode === 'staff' ? "Sender's saved receivers" : 'My receivers' }}</label>
                        <div class="picker-list">
                            <template x-for="r in receivers" :key="r.id">
                                <button type="button" class="pick" :class="{ 'is-selected': receiverId === r.id }" @click="receiverId = r.id">
                                    <span class="avatar" x-text="initials(r)"></span>
                                    <div class="grow"><strong x-text="r.full_name"></strong><br><small class="muted" x-text="[r.relationship, r.city || r.country, r.customer_code].filter(Boolean).join(', ')"></small></div>
                                    <i class="icon-circle-check" style="color:var(--moana-700);font-size:1.2rem" x-show="receiverId === r.id"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                    <template x-if="picked && receiverId === picked.id">
                        <div class="pick is-selected" style="margin-bottom:1rem;cursor:default">
                            <span class="avatar" x-text="initials(picked)"></span>
                            <div class="grow"><strong x-text="picked.full_name"></strong><br><small class="muted" x-text="(picked.city || picked.country || '') + ', ' + picked.customer_code"></small></div>
                            <span class="badge tone-blue plain">Will be saved to receivers</span>
                        </div>
                    </template>
                    <div class="field">
                        <label for="rsearch">Find someone already registered with MML</label>
                        <input id="rsearch" class="input" type="search" x-model="receiverQuery" @input="searchReceivers()" placeholder="Customer code, phone number or full name">
                        <span class="hint">At least 3 characters. Phone numbers are partly hidden for privacy.</span>
                        <div class="picker-list" x-show="receiverResults.length">
                            <template x-for="r in receiverResults" :key="r.id">
                                <button type="button" class="pick" @click="chooseReceiver(r)">
                                    <span class="avatar" x-text="initials(r)"></span>
                                    <div class="grow"><strong x-text="r.full_name"></strong><br><small class="muted" x-text="[r.city || r.country, r.phone, r.customer_code].filter(Boolean).join(', ')"></small></div>
                                    <i class="icon-plus muted"></i>
                                </button>
                            </template>
                        </div>
                        <p class="hint" x-show="receiverQuery.length > 2 && !receiverSearching && !receiverResults.length">No one found. Register them as a new receiver.</p>
                    </div>
                    <div class="field mb-0" x-show="type !== 'goods'">
                        <label for="roffice">Collect at</label>
                        <select id="roffice" class="select" x-model.number="receiverOfficeId">
                            <template x-for="o in collectOffices()" :key="o.id"><option :value="o.id" x-text="o.name + (o.city ? ', ' + o.city : '')" :selected="o.id === Number(receiverOfficeId)"></option></template>
                        </select>
                    </div>
                    <div class="panel-soft" x-show="type === 'goods'">
                        <i class="icon-store"></i> Shopping is collected at <strong x-text="officeName(receiverOfficeId)"></strong>.
                    </div>
                </div>
                <div class="card-foot">
                    <button type="button" class="btn btn-ghost" @click="back()"><i class="icon-chevron-left"></i>Back</button>
                    @if ($mode === 'portal')
                        <button type="button" class="btn btn-ghost" @click="submit('draft')" :disabled="submitting"><i class="icon-bookmark"></i>Save for later</button>
                    @endif
                    <button type="button" class="btn btn-primary" @click="next()">Continue <i class="icon-chevron-right"></i></button>
                </div>
            </section>

            {{-- ───────────── 5. Review & pay ───────────── --}}
            <section x-show="step === 'review'">
                <div class="card-head"><h2>Review and pay</h2></div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="field">
                            <label for="purpose">Purpose of transfer</label>
                            <select id="purpose" class="select" x-model="purpose" :class="{ 'is-invalid': errors.purpose }">
                                <option value="">Choose…</option>
                                @foreach ($purposes as $p)<option>{{ $p }}</option>@endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="income">Source of funds</label>
                            <select id="income" class="select" x-model="income" :class="{ 'is-invalid': errors.sender_income_source }">
                                <option value="">Choose…</option>
                                @foreach ($incomes as $p)<option>{{ $p }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <span class="label">How {{ $mode === 'staff' ? 'is the sender paying' : 'will you pay' }}?</span>
                        <div class="choice-grid">
                            <label class="choice" :class="{ 'is-selected': paymentMethod === 'internet_banking' }">
                                <input type="radio" value="internet_banking" x-model="paymentMethod">
                                <span class="choice-icon"><i class="icon-landmark"></i></span>
                                <span><strong>Internet banking</strong><small>Pay into the MML account using the reference number</small></span>
                            </label>
                            <label class="choice" :class="{ 'is-selected': paymentMethod === 'cash' }">
                                <input type="radio" value="cash" x-model="paymentMethod">
                                <span class="choice-icon"><i class="icon-wallet"></i></span>
                                <span><strong>Cash at the office</strong><small>{{ $mode === 'staff' ? 'Take payment at this counter' : 'Pay at your MML office' }}</small></span>
                            </label>
                        </div>
                    </div>
                    @if ($mode === 'staff')
                        <div class="panel" style="margin-bottom:1rem">
                            <label class="check"><input type="checkbox" x-model="paidNow"><span><strong>Payment received now</strong><br><small class="muted">The receiver can collect straight away. Leave unticked if you're waiting for a bank transfer.</small></span></label>
                            <div class="field mb-0" style="margin-top:.8rem" x-show="paidNow && paymentMethod === 'internet_banking'">
                                <label for="pref">Bank reference / receipt number</label>
                                <input id="pref" class="input" x-model="paymentReference" placeholder="Optional">
                            </div>
                        </div>
                    @endif
                    <div class="field mb-0">
                        <label for="notes">Note for the office <span class="muted">(optional)</span></label>
                        <textarea id="notes" class="textarea" x-model="notes" maxlength="500" placeholder="e.g. Her son Semisi may collect on her behalf"></textarea>
                    </div>
                </div>
                <div class="card-foot">
                    <button type="button" class="btn btn-ghost" @click="back()"><i class="icon-chevron-left"></i>Back</button>
                    @if ($mode === 'portal')
                        <button type="button" class="btn" @click="submit('draft')" :disabled="submitting"><i class="icon-bookmark"></i>Save for later</button>
                        <button type="button" class="btn btn-red btn-lg" @click="submit('send')" :disabled="submitting"><i class="icon-send"></i><span x-text="submitting ? 'Sending…' : 'Send ' + (quote ? fmt(quote.total_payable, local) : '')"></span></button>
                    @else
                        <button type="button" class="btn btn-red btn-lg" @click="submit('send')" :disabled="submitting"><i class="icon-send"></i><span x-text="submitting ? 'Processing…' : (paidNow ? 'Process transfer' : 'Create, awaiting payment')"></span></button>
                    @endif
                </div>
            </section>
        </div>

        {{-- ───────────── Summary (always visible) ───────────── --}}
        <aside class="sticky-side stack">
            <div class="quote">
                <template x-if="!quote"><p style="margin:0;color:#bfd6e0">Enter an amount to see the rate and fee.</p></template>
                <template x-if="quote">
                    <div>
                        <div class="q-row"><span>Amount sent</span><strong x-text="fmt(quote.transfer_amount, local)"></strong></div>
                        <div class="q-row"><span>Exchange rate</span><strong x-text="'1 ' + local + ' = ' + quote.rate + ' ' + foreign"></strong></div>
                        <div class="q-row"><span x-text="'Service fee (' + quote.fee_description + ')'"></span><strong x-text="fmt(quote.service_fee, local)"></strong></div>
                        <div class="q-row" x-show="quote.discount > 0"><span>Discount</span><strong x-text="'− ' + fmt(quote.discount, local)"></strong></div>
                        <div class="q-row q-total"><span>Total to pay</span><strong x-text="fmt(quote.total_payable, local)"></strong></div>
                        <div class="q-receive"><span x-text="type === 'goods' ? 'Shopping + cash' : 'Receiver gets'"></span><strong x-text="fmt(quote.converted_amount, foreign)"></strong></div>
                        <div class="q-band"></div>
                    </div>
                </template>
            </div>
            <div class="card card-pad" x-show="currentReceiver() || cartCount()">
                <template x-if="currentReceiver()">
                    <div class="summary-line"><span>Receiver</span><span x-text="currentReceiver().full_name"></span></div>
                </template>
                <div class="summary-line" x-show="receiverOfficeId && step !== 'amount'"><span>Collect at</span><span x-text="officeName(receiverOfficeId)"></span></div>
                <template x-for="l in cartLines()" :key="l.id">
                    <div class="summary-line"><span x-text="l.qty + ' × ' + l.name"></span><span x-text="fmt(l.total)"></span></div>
                </template>
                <div class="summary-line" x-show="cartCount()"><span>Cash with shopping</span><span x-text="fmt(Math.max(0, budget() - cartTotal()), foreign)"></span></div>
            </div>
        </aside>
    </div>
</div>
