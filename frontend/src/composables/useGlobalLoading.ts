import { computed, ref } from "vue"

// ⚠️ ASEGÚRATE DE QUE useAxios ESTÉ DISPONIBLE
// Si usas auto-imports, esto funciona. Si no, descomenta la siguiente línea:
// import { useAxios } from '@/composables/useAxios' 

declare const echo: any

interface ImportProcess {
  batch_id: string
  progress: number
  current_element: string
  current_action: string
  status: "active" | "completed" | "error" | "queued" | "failed" | "completed_with_errors"
  started_at?: string
  completed_at?: string
  websocket_channel?: any
  last_heartbeat?: number
  is_polling?: boolean
  polling_interval?: any
  metadata?: {
    total_records?: number
    processed_records?: number
    processed_records_in_chunk?: number
    current_sheet?: number
    total_sheets?: number
    errors_count?: number
    processing_speed?: number
    estimated_time_remaining?: number
    file_size?: number
    file_name: string,
    connection_status?: "connected" | "connecting" | "reconnecting" | "error" | "disconnected" | "polling"
    last_activity?: string
    memory_usage?: number
    cpu_usage?: number
    processing_start_time?: string
  }
}

// Variables reactivas globales (SINGLETON)
const allProcesses = ref<ImportProcess[]>([])
const isMinimized = ref(false)
const showProcessList = ref(false)

// Configuración Smart Polling
const HEARTBEAT_TIMEOUT = 6000;
const BASE_POLLING_INTERVAL = 3000;
const MAX_POLLING_INTERVAL = 15000;
const VISIBILITY_CHECK = true;

const callbacks = {
  started: [] as Array<(batchId: string) => void>,
  completed: [] as Array<(batchId: string) => void>,
  error: [] as Array<(batchId: string, error: any) => void>,
  progressUpdated: [] as Array<(batchId: string, progress: number) => void>,
}

const getEcho = () => {
  if (typeof echo !== "undefined") return echo
  if (typeof window !== "undefined" && (window as any).Echo) return (window as any).Echo
  console.error("❌ Echo no está disponible")
  return null
}

// ==========================================
// 🛡️ LÓGICA DE SMART POLLING
// ==========================================

const startPolling = (process: ImportProcess) => {
  if (process.is_polling) return;

  console.warn(`⚠️ [WATCHDOG] WebSocket silencioso para ${process.batch_id}. Activando Polling.`);
  process.is_polling = true;
  
  if (process.metadata) process.metadata.connection_status = "polling";

  let currentInterval = BASE_POLLING_INTERVAL;

  const executePoll = async () => {
    if (!process.is_polling) return;
    if (["completed", "error", "failed", "completed_with_errors"].includes(process.status)) {
        stopPolling(process);
        return;
    }

    if (VISIBILITY_CHECK && document.hidden) {
      process.polling_interval = setTimeout(executePoll, 5000); 
      return;
    }

    try {
      // ✅ CORRECCIÓN: Usamos useAxios directamente (asumiendo auto-import)
      // Si te da error de 'useAxios is not defined', impórtalo arriba.
      // @ts-ignore
      const { data } = await useAxios(`/processBatch/status/${process.batch_id}`);
      
      const pollingData = {
        batch_id: process.batch_id,
        progress: data.progress,
        status: data.status,
        current_action: data.current_action,
        current_element: data.current_element,
        metadata: data.metadata
      };

      handleProgressUpdate(process.batch_id, pollingData, true);
      currentInterval = BASE_POLLING_INTERVAL; 

    } catch (error) {
      console.error(`❌ [POLLING] Error consultando estado:`, error);
      currentInterval = Math.min(currentInterval * 1.5, MAX_POLLING_INTERVAL);
    }

    if (process.is_polling) {
        process.polling_interval = setTimeout(executePoll, currentInterval);
    }
  };

  executePoll();
}

const stopPolling = (process: ImportProcess) => {
  if (process.polling_interval) {
    clearTimeout(process.polling_interval);
    process.polling_interval = null;
  }
  process.is_polling = false;
}

// ==========================================
// 🐶 WATCHDOG GLOBAL
// ==========================================
const startGlobalWatchdog = () => {
  setInterval(() => {
    const now = Date.now();
    allProcesses.value.forEach(p => {
      if (p.status === 'active' || p.status === 'queued') {
        const lastHeartbeat = p.last_heartbeat || Date.now();
        if ((now - lastHeartbeat > HEARTBEAT_TIMEOUT) && !p.is_polling) {
          startPolling(p);
        }
      }
    });
  }, 2000); 
}
startGlobalWatchdog();

// ==========================================
// 🔌 WEBSOCKET LOGIC
// ==========================================

const startWebSocket = (process: ImportProcess) => {
  console.log(`🔌 [WEBSOCKET] Iniciando para batch: ${process.batch_id}`)
  const echoInstance = getEcho()
  
  process.last_heartbeat = Date.now();

  if (!echoInstance) {
    console.error(`❌ [WEBSOCKET] Echo no disponible. Usando Polling.`);
    startPolling(process);
    return
  }

  try {
    const channelName = `import.progress.${process.batch_id}`
    const channel = echoInstance.channel(channelName)
    process.websocket_channel = channel

    if (process.metadata) process.metadata.connection_status = "connecting"

    channel.listen(".progress.update", (data: any) => {
      process.last_heartbeat = Date.now();
      if (process.is_polling) {
          console.log(`⚡ [WATCHDOG] WebSocket recuperado. Deteniendo Polling.`);
          stopPolling(process);
          if (process.metadata) process.metadata.connection_status = "connected";
      }
      handleProgressUpdate(process.batch_id, data, false)
    })

    channel.subscribed(() => {
      console.log(`✅ [WEBSOCKET] Conectado al canal ${channelName}`)
      if (process.metadata) {
        process.metadata.connection_status = "connected"
        process.metadata.last_activity = new Date().toISOString()
      }
    })

    channel.error((error: any) => {
      console.error(`❌ [WEBSOCKET] Error canal:`, error)
      if (process.metadata) process.metadata.connection_status = "error"
      startPolling(process);
    })
  } catch (error) {
    console.error(`❌ [WEBSOCKET] Excepción canal:`, error)
    startPolling(process);
  }
}

const stopWebSocket = (process: ImportProcess) => {
  stopPolling(process);
  if (!process || !process.websocket_channel) return
  try {
    const echoInstance = getEcho()
    if (echoInstance) {
      echoInstance.leaveChannel(`import.progress.${process.batch_id}`)
    }
    process.websocket_channel = undefined
    if (process.metadata) process.metadata.connection_status = "disconnected"
  } catch (error) {
    console.error(`❌ [WEBSOCKET] Error deteniendo:`, error)
  }
}

const handleProgressUpdate = (batchId: string, data: any, isFromPolling: boolean = false) => {
  const process = allProcesses.value.find((p) => p.batch_id === batchId)
  if (!process) return

  if (!isFromPolling) console.log(`📊 [UPDATE] Datos recibidos:`, data)

  let progress = 0
  if (data.metadata?.general_progress !== undefined) {
    progress = data.metadata.general_progress
  } else if (data.progress !== undefined) {
    progress = data.progress
  }
  process.progress = Math.min(100, Math.max(0, progress))

  const defaultAction = isFromPolling ? "Sincronizando..." : "Procesando...";
  process.current_element = data.current_element || defaultAction
  process.current_action = data.current_action || "Importando datos"
  process.status = data.status

  if (!process.metadata) process.metadata = {}
  
  if (data.metadata) {
    process.metadata = {
      ...process.metadata,
      ...data.metadata,
      connection_status: isFromPolling ? "polling" : "connected",
      last_activity: new Date().toISOString()
    }
  }

  callbacks.progressUpdated.forEach((callback) => callback(batchId, process.progress))

  if (["completed", "error", "failed", "completed_with_errors"].includes(process.status)) {
    process.completed_at = new Date().toISOString()
    stopWebSocket(process)
    setTimeout(() => {
      callbacks.completed.forEach((callback) => callback(batchId))
    }, 500)
  }
}

// ... formatFileSize y formatTime (iguales) ...
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return "0 Bytes"
  const k = 1024
  const sizes = ["Bytes", "KB", "MB", "GB"]
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i]
}

const formatTime = (seconds: number): string => {
  if (seconds < 60) return `${seconds}s`
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  return `${minutes}m ${remainingSeconds}s`
}

export function useGlobalLoading() {
  const activeProcesses = computed(() => allProcesses.value.filter((p) => p.status === "active"))
  const queuedProcesses = computed(() => allProcesses.value.filter((p) => p.status === "queued"))
  const completedProcesses = computed(() => allProcesses.value.filter((p) => ["completed", "error", "failed", "completed_with_errors"].includes(p.status)))

  const sortedProcesses = computed(() => {
    const active = activeProcesses.value.sort((a, b) => new Date(a.started_at || "").getTime() - new Date(b.started_at || "").getTime())
    const queued = queuedProcesses.value.sort((a, b) => new Date(a.started_at || "").getTime() - new Date(b.started_at || "").getTime())
    const completed = completedProcesses.value.sort((a, b) => new Date(b.completed_at || "").getTime() - new Date(a.completed_at || "").getTime())
    return [...active, ...queued, ...completed]
  })

  const currentProcess = computed<ImportProcess | null>(() => {
    return activeProcesses.value[0] || queuedProcesses.value[0] || completedProcesses.value[0] || null
  })

  const isLoading = computed(() => {
    return allProcesses.value.some((p) => !["completed", "error", "failed", "completed_with_errors"].includes(p.status))
  })

  const hasMultipleProcesses = computed(() => allProcesses.value.length > 1)
  const currentProgress = computed(() => currentProcess.value?.progress || 0)

  const debugInfo = computed(() => {
    const process = currentProcess.value
    if (!process) return {}
    return {
      batchId: process.batch_id,
      progress: process.progress,
      status: process.status,
      connection: process.metadata?.connection_status,
      isPolling: process.is_polling,
      currentElement: process.current_element,
      errors_count: process.metadata?.errors_count || 0,
      processingSpeedFormatted: process.metadata?.processing_speed ? `${process.metadata.processing_speed} reg/s` : "N/A",
      estimatedTimeFormatted: process.metadata?.estimated_time_remaining ? formatTime(process.metadata.estimated_time_remaining) : "N/A",
    }
  })

  const startLoading = (batchId: string, fileName?: string, autoMaximize: boolean = false) => {
    if (allProcesses.value.some(p => p.batch_id === batchId)) return false;

    const file_name = fileName || "Archivo"
    const newProcess: ImportProcess = {
      batch_id: batchId,
      progress: 0,
      current_element: "Iniciando proceso...",
      current_action: "Preparando importación",
      status: "active",
      started_at: new Date().toISOString(),
      is_polling: false,
      last_heartbeat: Date.now(),
      metadata: {
        total_records: 0, processed_records: 0, processed_records_in_chunk: 0, current_sheet: 1, total_sheets: 1, errors_count: 0, processing_speed: 0, estimated_time_remaining: 0, file_size: 0, file_name: file_name, connection_status: "connecting", last_activity: new Date().toISOString(), processing_start_time: new Date().toISOString(), memory_usage: 0, cpu_usage: 0,
      },
    }
    allProcesses.value.push(newProcess)

    if (allProcesses.value.length === 1) isMinimized.value = !autoMaximize;

    startWebSocket(newProcess)
    callbacks.started.forEach((callback) => callback(batchId))
    return true
  }

  const stopLoading = () => {
    allProcesses.value.forEach((p) => {
      if (["active", "queued"].includes(p.status)) {
        stopWebSocket(p)
        p.status = "error"
        p.completed_at = new Date().toISOString()
      }
    })
  }

  const minimize = () => { isMinimized.value = true }
  const restore = () => { isMinimized.value = false }
  const showProcessListModal = () => { showProcessList.value = true }
  const hideProcessListModal = () => { showProcessList.value = false }

  const removeProcess = (batchId: string) => {
    const index = allProcesses.value.findIndex((p) => p.batch_id === batchId)
    if (index !== -1) {
      stopWebSocket(allProcesses.value[index])
      allProcesses.value.splice(index, 1)
    }
  }

  const showDataProcess = async (batchId: string) => { /* ... */ }

  const clearCompletedProcesses = () => {
    allProcesses.value = allProcesses.value.filter((p) => !["completed", "error", "failed", "completed_with_errors"].includes(p.status))
  }

  const cleanup = () => {
    allProcesses.value.forEach((p) => stopWebSocket(p))
    allProcesses.value = []
    isMinimized.value = false
    showProcessList.value = false
  }

  const onStarted = (callback: (batchId: string) => void) => callbacks.started.push(callback)
  const onCompleted = (callback: (batchId: string) => void) => callbacks.completed.push(callback)
  const onError = (callback: (batchId: string, error: any) => void) => callbacks.error.push(callback)
  const onProgressUpdated = (callback: (batchId: string, progress: number) => void) => callbacks.progressUpdated.push(callback)

  // ==========================================
  // 🔄 RECUPERACIÓN AL RECARGAR (F5)
  // ==========================================
  const getUserProcesses = async (user_id: string) => {
    console.log("📡 [LOAD] Cargando procesos del usuario")
    try {
      // ✅ CORRECCIÓN CRÍTICA: Usar useAxios directamente, no window.useAxios
      // @ts-ignore
      const { response, data } = await useAxios(`/processBatch/getUserProcesses/${user_id}`).get();

      if (response.status === 200 && data.processes) {
        console.log(`✅ [LOAD] ${data.processes.length} procesos cargados`)

        const loadedProcessesMap = new Map(data.processes.map((p: any) => [p.batch_id, p]))
        const updatedAllProcesses: ImportProcess[] = []
        const activeOrQueuedProcessesToStartWs: ImportProcess[] = []

        // 1. Actualizar existentes
        allProcesses.value.forEach((existingProcess) => {
          if (loadedProcessesMap.has(existingProcess.batch_id)) {
            const backendProcess = loadedProcessesMap.get(existingProcess.batch_id)
            Object.assign(existingProcess, {
              ...existingProcess,
              status: backendProcess.status,
              progress: (existingProcess.status === 'active') ? existingProcess.progress : backendProcess.progress,
              metadata: { ...existingProcess.metadata, ...backendProcess.metadata }
            })

            if (["active", "queued"].includes(existingProcess.status)) {
              activeOrQueuedProcessesToStartWs.push(existingProcess)
            }
            updatedAllProcesses.push(existingProcess)
            loadedProcessesMap.delete(existingProcess.batch_id)
          } else {
            if (!["completed", "error"].includes(existingProcess.status)) updatedAllProcesses.push(existingProcess)
          }
        })

        // 2. Agregar nuevos (desde BD)
        loadedProcessesMap.forEach((newBackendProcess: any) => {
          const newProcess: ImportProcess = {
            batch_id: newBackendProcess.batch_id,
            file_name: newBackendProcess.metadata?.file_name || 'Archivo',
            progress: newBackendProcess.progress || 0,
            current_element: newBackendProcess.current_element || '',
            current_action: newBackendProcess.current_action || '',
            status: newBackendProcess.status,
            started_at: newBackendProcess.started_at,
            completed_at: newBackendProcess.completed_at,
            is_polling: false, // Iniciar limpio
            last_heartbeat: Date.now(),
            metadata: newBackendProcess.metadata || {},
          }
          updatedAllProcesses.push(newProcess)
          if (["active", "queued"].includes(newProcess.status)) {
            activeOrQueuedProcessesToStartWs.push(newProcess)
          }
        })

        allProcesses.value = updatedAllProcesses

        // ✅ UX: Si recuperamos procesos activos al recargar, los mostramos MINIMIZADOS
        if (activeOrQueuedProcessesToStartWs.length > 0) {
            isMinimized.value = true;
        }

        // Reiniciar WebSockets/Watchdogs
        activeOrQueuedProcessesToStartWs.forEach((p) => {
          if (!p.websocket_channel && !p.is_polling) {
            startWebSocket(p)
          }
        })
      }
    } catch (error) {
      console.error("❌ [LOAD] Error cargando procesos:", error)
    }
  }

  return {
    isLoading, currentProcess, currentProgress, debugInfo, isMinimized, showProcessList,
    allProcesses, activeProcesses, queuedProcesses, completedProcesses, sortedProcesses, hasMultipleProcesses,
    startLoading, stopLoading, minimize, restore, cleanup,
    showProcessListModal, hideProcessListModal, removeProcess, clearCompletedProcesses,
    getUserProcesses, showDataProcess,
    onStarted, onCompleted, onError, onProgressUpdated
  }
}
