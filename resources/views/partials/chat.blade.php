{{-- Chat thread. Needs: $pollUrl, $sendUrl, $me ('staff' | 'customer'), $title, optional $subtitle, $headerActions --}}
<div class="conversation" x-data="chatBox({ pollUrl: @js($pollUrl), sendUrl: @js($sendUrl), me: @js($me), pollMs: {{ (int) config('mml.chat_poll_ms') }} })" style="height:100%">
    <div class="c-head">
        <div><strong>{{ $title }}</strong>@isset($subtitle)<br><small class="muted">{{ $subtitle }}</small>@endisset</div>
        <div class="row gap-s"><span class="muted small" x-show="error" x-text="error"></span>{!! $headerActions ?? '' !!}</div>
    </div>
    <div class="messages" x-ref="list" aria-live="polite">
        <template x-if="!messages.length"><div class="empty"><i class="icon-messages-square"></i><strong>No messages yet</strong>Say mālō e lelei.</div></template>
        <template x-for="m in messages" :key="m.id">
            <div class="bubble" :class="mine(m) ? 'me' : 'them'">
                <span class="b-body" x-text="m.body"></span>
                <span class="b-meta" x-text="(mine(m) ? '' : (m.sender_name || '') + ', ') + m.time"></span>
            </div>
        </template>
    </div>
    <form class="composer" @submit.prevent="send()">
        <textarea class="textarea" x-model="body" @keydown="keydown($event)" placeholder="Write a message" aria-label="Message" maxlength="2000"></textarea>
        <button class="btn btn-primary" :disabled="sending || !body.trim()"><i class="icon-send-horizontal"></i><span class="sr-only">Send</span></button>
    </form>
</div>
