<template>
  <div class="chat-page">
    <!-- Header -->
    <div class="chat-header">
      <div class="bot-avatar">🌱</div>
      <div>
        <div class="bot-name">희망이</div>
        <div class="bot-status">
          <span class="status-dot" :class="{ streaming: isStreaming }"></span>
          {{ isStreaming ? '답변 작성 중...' : '온라인' }}
        </div>
      </div>
      <button class="clear-btn" @click="clearHistory" title="대화 초기화">↺</button>
    </div>

    <!-- Messages -->
    <div class="messages-wrap" ref="messagesEl">
      <div v-if="messages.length === 0" class="empty-state">
        <div class="empty-icon">🌿</div>
        <p>안녕하세요! 취업 고민, 법적 권리, 커리어 설계 — 무엇이든 편하게 말씀해 주세요.</p>
        <div class="quick-chips">
          <button
            v-for="q in quickPrompts"
            :key="q"
            class="chip"
            @click="send(q)"
          >{{ q }}</button>
        </div>
      </div>

      <template v-for="(msg, i) in messages" :key="i">
        <div class="msg" :class="msg.role">
          <div v-if="msg.role === 'assistant'" class="avatar">🌱</div>
          <div class="bubble" v-html="renderMarkdown(msg.content)"></div>
        </div>
      </template>

      <!-- Streaming bubble -->
      <div v-if="isStreaming" class="msg assistant">
        <div class="avatar">🌱</div>
        <div class="bubble streaming-bubble" v-html="renderMarkdown(streamBuffer) + '<span class=\'cursor\'>▌</span>'"></div>
      </div>

      <div ref="bottomEl"></div>
    </div>

    <!-- Quick chips (mid-conversation) -->
    <div v-if="messages.length > 0 && !isStreaming" class="mid-chips">
      <button v-for="q in quickPrompts" :key="q" class="chip" @click="send(q)">{{ q }}</button>
    </div>

    <!-- Input -->
    <div class="input-row">
      <textarea
        v-model="draft"
        ref="inputEl"
        placeholder="고민을 편하게 말씀해 주세요..."
        rows="1"
        :disabled="isStreaming"
        @keydown.enter.exact.prevent="send()"
        @input="autoResize"
      />
      <button
        class="send-btn"
        :disabled="!draft.trim() || isStreaming"
        @click="send()"
      >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <path d="M22 2L11 13" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
          <path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>
    <p class="disclaimer">의학적 진단은 하지 않습니다 · 중요한 결정은 전문의와 상담하세요</p>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { marked } from 'marked'
import DOMPurify from 'dompurify'
import api, { streamChat } from '@/services/api'

const messages    = ref([])
const draft       = ref('')
const streamBuffer = ref('')
const isStreaming  = ref(false)
const messagesEl   = ref(null)
const bottomEl     = ref(null)
const inputEl      = ref(null)

const quickPrompts = [
  '재택근무 일자리 추천해 주세요',
  '복직 준비 어떻게 하나요?',
  '장애인 고용 지원제도 알려주세요',
  '비슷한 경험자와 연결해 주세요',
]

onMounted(async () => {
  const { data } = await api.get('/chat/history')
  messages.value = data.messages
  scrollToBottom()
})

async function send(text) {
  const content = (text ?? draft.value).trim()
  if (!content || isStreaming.value) return

  draft.value   = ''
  streamBuffer.value = ''
  messages.value.push({ role: 'user', content })
  isStreaming.value = true
  await nextTick(); scrollToBottom()

  await streamChat(
    content,
    token => {
      streamBuffer.value += token
      scrollToBottom()
    },
    () => {
      messages.value.push({ role: 'assistant', content: streamBuffer.value })
      streamBuffer.value = ''
      isStreaming.value   = false
      nextTick(() => { scrollToBottom(); inputEl.value?.focus() })
    }
  )
}

async function clearHistory() {
  if (!confirm('대화 기록을 모두 지울까요?')) return
  await api.delete('/chat/clear')
  messages.value = []
}

function renderMarkdown(text) {
  return DOMPurify.sanitize(marked.parse(text ?? ''))
}

function scrollToBottom() {
  nextTick(() => bottomEl.value?.scrollIntoView({ behavior: 'smooth' }))
}

function autoResize(e) {
  e.target.style.height = 'auto'
  e.target.style.height = Math.min(e.target.scrollHeight, 120) + 'px'
}
</script>

<style scoped>
.chat-page {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #f8faf9;
  font-family: 'Noto Sans KR', sans-serif;
}

.chat-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  background: #fff;
  border-bottom: 1px solid #eef5f1;
}
.bot-avatar { font-size: 28px; }
.bot-name   { font-weight: 700; font-size: 15px; color: #1a1a1a; }
.bot-status { font-size: 12px; color: #888; display: flex; align-items: center; gap: 5px; }
.status-dot {
  width: 7px; height: 7px; border-radius: 50%; background: #34c77b;
  &.streaming { animation: pulse 1s infinite; }
}
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.3} }
.clear-btn {
  margin-left: auto; background: none; border: none; font-size: 18px;
  color: #aaa; cursor: pointer; padding: 4px 8px;
  &:hover { color: #555; }
}

.messages-wrap {
  flex: 1; overflow-y: auto; padding: 20px 16px 8px;
  display: flex; flex-direction: column; gap: 16px;
}
.empty-state { text-align: center; margin: auto; color: #666; }
.empty-icon  { font-size: 40px; margin-bottom: 12px; }
.empty-state p { font-size: 14px; line-height: 1.7; margin-bottom: 16px; }

.quick-chips, .mid-chips {
  display: flex; flex-wrap: wrap; gap: 8px; justify-content: center;
}
.mid-chips {
  padding: 8px 16px; background: #fff; border-top: 1px solid #f0f0f0;
  justify-content: flex-start; overflow-x: auto;
  flex-wrap: nowrap;
}
.chip {
  white-space: nowrap; background: #f4fdf8; border: 1px solid #d4f0e2;
  border-radius: 20px; padding: 6px 14px; font-size: 12px; color: #1a7a45;
  cursor: pointer; font-weight: 500;
  &:hover { background: #e4f7ee; }
}

.msg {
  display: flex; align-items: flex-end; gap: 8px;
  &.user { flex-direction: row-reverse; }
}
.avatar { font-size: 20px; flex-shrink: 0; width: 30px; text-align: center; }
.bubble {
  max-width: 78%; padding: 12px 16px; font-size: 14px;
  line-height: 1.7; border-radius: 18px;
  :deep(p) { margin: 0 0 8px; &:last-child { margin: 0; } }
  :deep(ul) { margin: 6px 0; padding-left: 18px; }
  :deep(strong) { font-weight: 600; }
  :deep(code) { background: rgba(0,0,0,.06); border-radius: 4px; padding: 1px 5px; font-size: 13px; }
}
.user .bubble {
  background: linear-gradient(135deg, #34c77b, #1a9e5a);
  color: #fff; border-radius: 18px 18px 4px 18px;
}
.assistant .bubble {
  background: #fff; color: #1a1a1a;
  border: 1px solid #eef5f1; border-radius: 18px 18px 18px 4px;
  box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.streaming-bubble :deep(.cursor) { animation: blink .7s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }

.input-row {
  display: flex; align-items: flex-end; gap: 10px;
  padding: 12px 16px; background: #fff;
  border-top: 1px solid #f0f0f0;
}
textarea {
  flex: 1; border: 1.5px solid #e8f5ee; border-radius: 16px;
  padding: 10px 14px; font-size: 14px; line-height: 1.5;
  resize: none; background: #f8faf9; outline: none;
  font-family: inherit; transition: border-color .2s;
  &:focus { border-color: #34c77b; }
}
.send-btn {
  width: 40px; height: 40px; border-radius: 50%; border: none;
  background: linear-gradient(135deg, #34c77b, #1a9e5a);
  color: #fff; cursor: pointer; display: flex;
  align-items: center; justify-content: center; flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(52,199,123,.35); transition: opacity .2s;
  &:disabled { background: #e0e0e0; box-shadow: none; cursor: default; }
}
.disclaimer {
  text-align: center; font-size: 11px; color: #bbb;
  padding: 4px 0 10px; margin: 0;
}
</style>
