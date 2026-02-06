<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Header -->
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <h1 class="text-2xl font-bold text-blue-600">InfraMind</h1>
          <button 
            @click="handleLogout"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
          >
            Sign Out
          </button>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Welcome, {{ user?.name }}</h2>
        <p class="text-gray-600 mt-2">Role: {{ user?.role }}</p>
      </div>

      <!-- Dashboard Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Backend Status</h3>
          <p class="text-2xl font-bold text-green-600">✓ Connected</p>
          <p class="text-sm text-gray-600 mt-2">PHP 8.2.30 on localhost:8000</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Database</h3>
          <p class="text-2xl font-bold text-green-600">✓ MySQL</p>
          <p class="text-sm text-gray-600 mt-2">8.0.45 (Docker)</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Frontend</h3>
          <p class="text-2xl font-bold text-green-600">✓ Vue 3</p>
          <p class="text-sm text-gray-600 mt-2">Vite + Tailwind CSS</p>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Quick Links</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <a 
            href="http://localhost:8000" 
            target="_blank"
            class="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            <p class="font-semibold text-gray-900">Backend API</p>
            <p class="text-sm text-gray-600">http://localhost:8000</p>
          </a>
          <a 
            href="http://localhost:8080" 
            target="_blank"
            class="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            <p class="font-semibold text-gray-900">phpMyAdmin</p>
            <p class="text-sm text-gray-600">http://localhost:8080</p>
          </a>
          <a 
            href="http://localhost:8000/db-manager.php" 
            target="_blank"
            class="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            <p class="font-semibold text-gray-900">DB Manager</p>
            <p class="text-sm text-gray-600">password: admin123</p>
          </a>
          <a 
            href="http://localhost:3000" 
            target="_blank"
            class="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            <p class="font-semibold text-gray-900">Frontend (Vue 3)</p>
            <p class="text-sm text-gray-600">http://localhost:3000</p>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const user = computed(() => authStore.user)

const handleLogout = () => {
  authStore.logout()
  router.push('/login')
}
</script>
