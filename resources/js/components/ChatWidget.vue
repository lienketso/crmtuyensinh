<template>
  <div class="fixed bottom-4 right-4 z-50">
    <!-- Chat Button -->
    <button
      v-if="!isOpen"
      @click="isOpen = true"
      class="bg-blue-600 text-white rounded-full p-4 shadow-lg hover:bg-blue-700 transition transform hover:scale-110"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
      </svg>
    </button>

    <!-- Chat Window -->
    <div
      v-else
      class="bg-white rounded-lg shadow-2xl w-96 h-[600px] flex flex-col"
    >
      <!-- Header -->
      <div class="bg-blue-600 text-white p-4 rounded-t-lg flex items-center justify-between">
        <div>
          <h3 class="font-semibold">AI Tư vấn Tuyển sinh</h3>
          <p class="text-xs text-blue-100">Chúng tôi sẵn sàng hỗ trợ bạn</p>
        </div>
        <button
          @click="isOpen = false"
          class="text-white hover:text-blue-200 transition"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Messages -->
      <div
        ref="messagesContainer"
        class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50"
      >
        <div
          v-for="(message, index) in messages"
          :key="index"
          class="flex"
          :class="message.sender === 'user' ? 'justify-end' : 'justify-start'"
        >
          <div
            class="max-w-[80%] px-4 py-2 rounded-lg"
            :class="message.sender === 'user'
              ? 'bg-blue-600 text-white'
              : 'bg-white text-gray-900 border border-gray-200'"
          >
            <p class="text-sm whitespace-pre-wrap">{{ message.content }}</p>
            <p class="text-xs mt-1 opacity-75">
              {{ formatTime(message.timestamp) }}
            </p>
          </div>
        </div>
        <div v-if="loading" class="flex justify-start">
          <div class="bg-white border border-gray-200 rounded-lg px-4 py-2">
            <div class="flex space-x-1">
              <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0s"></div>
              <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
              <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Input Form -->
      <div class="p-4 border-t bg-white rounded-b-lg">
        <form @submit.prevent="sendMessage" class="flex gap-2">
          <input
            v-model="inputMessage"
            type="text"
            placeholder="Nhập câu hỏi của bạn..."
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            :disabled="loading"
          />
          <button
            type="submit"
            :disabled="!inputMessage.trim() || loading"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </form>
        <p class="text-xs text-gray-500 mt-2 text-center">
          Powered by AI
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import { useAuthStore } from '../stores/auth';
import api from '@/utils/axios';
const authStore = useAuthStore();

const isOpen = ref(false);
const messages = ref([]);
const inputMessage = ref('');
const loading = ref(false);
const messagesContainer = ref(null);

const formatTime = (timestamp) => {
  return new Date(timestamp).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
};

const scrollToBottom = async () => {
  await nextTick();
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
};

const sendMessage = async () => {
  if (!inputMessage.value.trim() || loading.value) return;

  const userMessage = inputMessage.value.trim();
  inputMessage.value = '';

  // Add user message
  messages.value.push({
    sender: 'user',
    content: userMessage,
    timestamp: new Date()
  });

  await scrollToBottom();

  // Send to API
  loading.value = true;
  try {
    const response = await api.post('/chat', {
      message: userMessage,
      channel: 'web'
    });

    // Add AI response
    messages.value.push({
      sender: 'ai',
      content: response.data.response,
      timestamp: new Date()
    });
  } catch (error) {
    console.error('Error sending message:', error);
    messages.value.push({
      sender: 'ai',
      content: 'Xin lỗi, có lỗi xảy ra. Vui lòng thử lại sau.',
      timestamp: new Date()
    });
  } finally {
    loading.value = false;
    await scrollToBottom();
  }
};

// Initialize welcome message
onMounted(() => {
  messages.value.push({
    sender: 'ai',
    content: 'Xin chào! Tôi là AI tư vấn tuyển sinh. Tôi có thể giúp gì cho bạn?',
    timestamp: new Date()
  });
});

watch(isOpen, () => {
  if (isOpen) {
    scrollToBottom();
  }
});
</script>

<style scoped>
/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
