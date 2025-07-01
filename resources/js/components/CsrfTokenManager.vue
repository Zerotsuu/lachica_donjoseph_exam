<template>
  <div class="csrf-token-manager p-6 bg-gray-50 rounded-lg border">
    <h3 class="text-lg font-semibold mb-4">CSRF Token Manager</h3>
    
    <div class="space-y-4">
      <!-- Current Token Display -->
      <div class="bg-white p-4 rounded border">
        <h4 class="font-medium text-gray-700 mb-2">Current CSRF Token:</h4>
        <code class="text-xs bg-gray-100 p-2 rounded block break-all">
          {{ currentToken || 'No token found' }}
        </code>
      </div>

      <!-- Actions -->
      <div class="flex gap-2 flex-wrap">
        <button 
          @click="refreshFromMeta"
          class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
        >
          Refresh from Meta
        </button>
        
        
        <button 
          @click="testRequest"
          :disabled="isLoading"
          class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 disabled:opacity-50"
        >
          Test CSRF Request
        </button>
        
        <button 
          @click="testInvalidToken"
          class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
        >
          Test Invalid Token (419)
        </button>
      </div>

      <!-- Status Display -->
      <div v-if="status" class="p-3 rounded" :class="statusClass">
        {{ status }}
      </div>

      <!-- Log Display -->
      <div v-if="logs.length > 0" class="bg-gray-800 text-green-400 p-4 rounded max-h-64 overflow-y-auto">
        <h4 class="text-white mb-2">Activity Log:</h4>
        <div v-for="(log, index) in logs" :key="index" class="text-xs mb-1">
          {{ log }}
        </div>
        <button 
          @click="clearLogs"
          class="mt-2 px-2 py-1 bg-gray-600 text-white text-xs rounded"
        >
          Clear Logs
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { refreshCsrfToken, updateCsrfTokenInMeta } from '@/plugins/sanctum'

const currentToken = ref<string>('')
const isLoading = ref(false)
const status = ref<string>('')
const statusType = ref<'success' | 'error' | 'info'>('info')
const logs = ref<string[]>([])

const statusClass = computed(() => {
  switch (statusType.value) {
    case 'success': return 'bg-green-100 text-green-800 border border-green-200'
    case 'error': return 'bg-red-100 text-red-800 border border-red-200'
    default: return 'bg-blue-100 text-blue-800 border border-blue-200'
  }
})

const addLog = (message: string) => {
  const timestamp = new Date().toLocaleTimeString()
  logs.value.unshift(`[${timestamp}] ${message}`)
  
  // Keep only last 20 logs
  if (logs.value.length > 20) {
    logs.value = logs.value.slice(0, 20)
  }
}

const updateCurrentToken = () => {
  const token = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  currentToken.value = token || ''
  addLog(`Token updated: ${token ? token.substring(0, 16) + '...' : 'none'}`)
}

const refreshFromMeta = () => {
  try {
    refreshCsrfToken()
    updateCurrentToken()
    setStatus('Token refreshed from meta tag', 'success')
  } catch (error) {
    setStatus('Failed to refresh from meta tag', 'error')
    addLog(`Error: ${error}`)
  }
}

const testRequest = async () => {
  isLoading.value = true
  try {
    // Make a test POST request to see if CSRF works
    await axios.post('/api/test-csrf', { test: 'data' })
    setStatus('CSRF test request successful', 'success')
    addLog('Test request successful')
  } catch (error: any) {
    if (error.response?.status === 419) {
      setStatus('CSRF token mismatch detected (419)', 'error')
      addLog('419 error - token will be auto-refreshed')
    } else {
      setStatus(`Test request failed: ${error.message}`, 'error')
      addLog(`Test error: ${error.response?.status} ${error.message}`)
    }
  } finally {
    isLoading.value = false
  }
}

const testInvalidToken = () => {
  // Deliberately set an invalid token to test 419 handling
  const invalidToken = 'invalid-token-' + Math.random().toString(36).substring(7)
  updateCsrfTokenInMeta(invalidToken)
  updateCurrentToken()
  setStatus('Invalid token set - next request will trigger 419', 'info')
  addLog('Invalid token deliberately set for testing')
}

const setStatus = (message: string, type: 'success' | 'error' | 'info' = 'info') => {
  status.value = message
  statusType.value = type
  addLog(`Status: ${message}`)
  
  // Clear status after 5 seconds
  setTimeout(() => {
    status.value = ''
  }, 5000)
}

const clearLogs = () => {
  logs.value = []
}

onMounted(() => {
  updateCurrentToken()
  addLog('CSRF Token Manager initialized')
})
</script>

<style scoped>
code {
  font-family: 'Courier New', monospace;
}
</style> 