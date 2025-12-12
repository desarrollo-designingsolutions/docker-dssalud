<template>
  <div v-if="isLoading" class="loading-overlay">
    <div class="loading-spinner"></div>
    <div class="loading-text">Cargando...</div>
    <ProgressBar :progress="progress" />
  </div>
</template>

<script setup lang="ts">
import { defineProps, ref } from 'vue';

const props = defineProps({
  progress: {
    type: Number,
    default: 0
  },
  isLoading: {
    type: Boolean,
    default: true
  }
});

// Estado de carga
const isLoading = ref(false);

// Función para activar el estado de carga
const startLoading = () => {
  isLoading.value = true;
};

// Función para desactivar el estado de carga
const stopLoading = () => {
  isLoading.value = false;
};

// Retornamos los estados y funciones para usar en el template o en el componente padre
defineExpose({
  isLoading,
  startLoading,
  stopLoading
})
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.7);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 99999;
}

.loading-text {
  font-size: 24px;
  margin-bottom: 20px;
  color: #3498db;
}

.loading-spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}
</style>
