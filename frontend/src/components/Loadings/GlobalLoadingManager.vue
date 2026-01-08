<template>
  <LoadingV2Enhanced
    v-if="globalLoading.isLoading.value && globalLoading.currentProcess.value && !globalLoading.isMinimized.value && !globalLoading.showProcessList.value"
    :is-loading="true"
    :progress="globalLoading.currentProgress.value"
    :title="title"
    :subtitle="subtitle"
    @minimized="handleMinimize"
    @completed="handleCompleted"
    :show-multiple-button="globalLoading.hasMultipleProcesses.value"
    @show-multiple="handleShowMultiple"
    :debug-data="globalLoading.debugInfo.value"
  />

  <div
    v-else-if="globalLoading.isLoading.value && globalLoading.currentProcess.value && globalLoading.isMinimized.value"
    class="minimized-overlay"
  >
    <v-card class="minimized-card" @click="handleRestore">
      <v-progress-circular :model-value="globalLoading.currentProgress.value" color="primary" size="40" width="3">
        {{ Math.round(globalLoading.currentProgress.value) }}%
      </v-progress-circular>
      <div class="minimized-info ms-3">
        <div class="text-caption font-weight-medium">
          {{ globalLoading.activeProcesses.value.length }} activo{{ globalLoading.activeProcesses.value.length !== 1 ?
            's' : '' }}
          {{ globalLoading.queuedProcesses.value.length > 0 ? `, ${globalLoading.queuedProcesses.value.length} en cola`
            : '' }}
        </div>
        <div class="text-caption text-medium-emphasis">
          {{ Math.round(globalLoading.currentProgress.value) }}% completado
        </div>
      </div>
      <v-btn icon size="small" variant="text" @click.stop="handleShowMultiple" class="ms-2">
        <v-icon icon="tabler-list" />
      </v-btn>
      <v-icon class="minimize-icon ms-2" icon="tabler-chevron-up" @click.stop="handleRestore" />
    </v-card>
  </div>

  <ProcessListModal
    v-if="globalLoading.showProcessList.value"
    @remove-process="handleRemoveProcess"
    @clear-completed="handleClearCompleted"
    @close="handleCloseProcessList"
    @showDataProcess="handleShowDataProcess"
  />

  <ModalListErrors ref="refModalListErrors" />

  <v-snackbar v-model="showStartNotification" :timeout="4000" color="info" location="top right" variant="tonal">
    <div class="d-flex align-center">
      <v-icon start icon="tabler-loader-2" class="spin-animation me-2" />
      <div>
        <div class="font-weight-bold">Importación Iniciada</div>
        <div class="text-caption">El proceso continúa en segundo plano.</div>
      </div>
    </div>
    <template v-slot:actions>
      <v-btn variant="text" size="small" @click="handleRestore">Ver</v-btn>
      <v-btn icon="tabler-x" variant="text" size="small" @click="showStartNotification = false" />
    </template>
  </v-snackbar>

  <v-snackbar v-model="showCompletionNotification" :timeout="5000" color="success" location="top">
    <v-icon start icon="tabler-check" />
    ¡Importación completada exitosamente!
    <template v-slot:actions>
      <v-btn variant="text" @click="showCompletionNotification = false">
        Cerrar
      </v-btn>
    </template>
  </v-snackbar>
</template>

<script setup lang="ts">
import { useGlobalLoading } from '@/composables/useGlobalLoading';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import LoadingV2Enhanced from './LoadingV2Enhanced.vue';
import ModalListErrors from './ModalListErrors.vue';
import ProcessListModal from './ProcessListModal.vue';

const authenticationStore = useAuthenticationStore();
const globalLoading = useGlobalLoading();
const showCompletionNotification = ref(false);
const showStartNotification = ref(false); // ✅ Nueva ref

const refModalListErrors = ref();
const openModalListErrors = (batchId: string) => {
  refModalListErrors.value.openModal(batchId);
};

const title = computed(() => {
  const process = globalLoading.currentProcess.value;
  const progress = globalLoading.currentProgress.value;
  if (!process) return 'Sin proceso activo';
  if (progress >= 100 && process.status === 'completed') return '¡Importación Completada!';
  if (progress === 0) return 'Procesando importación';
  return process.current_action || 'Procesando importación';
});

const subtitle = computed(() => {
  const process = globalLoading.currentProcess.value;
  const progress = globalLoading.currentProgress.value;
  if (!process) return 'Sin proceso activo';
  if (progress >= 100 && process.status === 'completed') return '¡El archivo se ha procesado exitosamente!';
  if (progress === 0) return 'Iniciando proceso de importación...';
  let subtitleText = `${Math.round(progress)}% completado - ${process.current_element}`;
  if (globalLoading.queuedProcesses.value.length > 0) {
    subtitleText += ` • ${globalLoading.queuedProcesses.value.length} en cola`;
  }
  return subtitleText;
});

const handleCompleted = () => { console.log('✅ [MANAGER] Loading completed'); };
const handleMinimize = () => { globalLoading.minimize(); };
const handleRestore = () => { 
    globalLoading.restore(); 
    showStartNotification.value = false; // Cerrar notificación al abrir
};
const handleShowMultiple = () => { globalLoading.showProcessListModal(); };
const handleRemoveProcess = (batchId: string) => { globalLoading.removeProcess(batchId); };
const handleShowDataProcess = (batchId: string) => { openModalListErrors(batchId); };
const handleClearCompleted = () => { globalLoading.clearCompletedProcesses(); };
const handleCloseProcessList = () => { globalLoading.hideProcessListModal(); };

const setupCallbacks = () => {
  // ✅ Callback de inicio
  globalLoading.onStarted((batchId: string) => {
    console.log(`🚀 [MANAGER] Notificación inicio para ${batchId}`);
    showStartNotification.value = true;
  });

  globalLoading.onCompleted((batchId: string) => {
    console.log(`🎉 [MANAGER] Completado ${batchId}`);
    if (globalLoading.currentProcess.value?.batch_id === batchId || globalLoading.isMinimized.value) {
      showCompletionNotification.value = true;
    }
  });
  
  globalLoading.onError((batchId: string, error: any) => {
    console.error(`❌ [MANAGER] Error en batch ${batchId}:`, error);
  });
  
  globalLoading.onProgressUpdated((batchId: string, progress: number) => { });

  if (authenticationStore.user?.id) {
    globalLoading.getUserProcesses(authenticationStore.user.id);
  } else {
    watch(() => authenticationStore.user?.id, (newId) => {
      if (newId) globalLoading.getUserProcesses(newId);
    }, { immediate: true });
  }
};

onMounted(() => {
  console.log('🚀 [MANAGER] Mounted');
  setupCallbacks();
});

onBeforeUnmount(() => {
  console.log('🧹 [MANAGER] Unmounting');
  globalLoading.cleanup();
});
</script>

<style scoped>
.spin-animation {
  animation: spin 2s linear infinite !important;
}
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.minimized-overlay {
  position: fixed;
  z-index: 9999;
  bottom: 20px;
  right: 20px;
}

.minimized-card {
  display: flex;
  align-items: center;
  border-radius: 24px;
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 15%);
  cursor: pointer;
  padding: 8px 16px;
  transition: all 0.3s ease;
  min-width: 250px;
}

.minimized-card:hover {
  box-shadow: 0 6px 16px rgba(0, 0, 0, 20%);
  transform: translateY(-3px);
}

.minimize-icon { transition: transform 0.3s ease; }
.minimized-info { flex: 1; }
</style>
