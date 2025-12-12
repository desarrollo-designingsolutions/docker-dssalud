import { computed, ref } from "vue"

declare const echo: any

interface ImportProcess {
  batch_id: string
  file_name: string
  progress: number
  current_element: string
  current_action: string
  status: "active" | "completed" | "error" | "paused"
  started_at: string
  completed_at?: string
  connection_status: "connected" | "connecting" | "reconnecting" | "error" | "disconnected"
  is_active: boolean
  websocket_channel?: any
  details?: {
    current_element: string
    processed_records: number
    total_records: number
    sheet: number
    last_update: string
  }
}

// Variables reactivas globales
const processes = ref<ImportProcess[]>([])
const activeProcessId = ref<string | null>(null)
const showProcessList = ref(false)
const isMinimized = ref(false)

// Callbacks
const callbacks = {
  completed: [] as Array<(batchId: string) => void>,
  error: [] as Array<(batchId: string, error: any) => void>,
  progressUpdated: [] as Array<(batchId: string, progress: number) => void>,
}

const getEcho = () => {
  if (typeof echo !== "undefined") {
    return echo
  }
  if (typeof window !== "undefined" && (window as any).Echo) {
    return (window as any).Echo
  }
  console.error("❌ Echo no está disponible")
  return null
}

const startWebSocket = (batchId: string) => {
  console.log(`🔌 [WEBSOCKET] Iniciando para batch: ${batchId}`)

  const echoInstance = getEcho()
  if (!echoInstance) {
    console.error(`❌ [WEBSOCKET] Echo no disponible`)
    return
  }

  const process = processes.value.find((p) => p.batch_id === batchId)
  if (!process) return

  try {
    const channelName = `import.progress.${batchId}`
    console.log(`🔌 [WEBSOCKET] Creando canal: ${channelName}`)

    const channel = echoInstance.channel(channelName)
    process.websocket_channel = channel
    process.connection_status = "connecting"

    // Escuchar eventos de progreso
    channel.listen("progress.update", (data: any) => {
      console.log(`🔥 [WEBSOCKET] EVENTO RECIBIDO para ${batchId}:`, data)
      handleProgressUpdate(batchId, data)
    })

    channel.subscribed(() => {
      console.log(`✅ [WEBSOCKET] Conectado al canal ${channelName}`)
      if (process) {
        process.connection_status = "connected"
      }
    })

    channel.error((error: any) => {
      console.error(`❌ [WEBSOCKET] Error en canal ${batchId}:`, error)
      if (process) {
        process.connection_status = "error"
      }
      callbacks.error.forEach((callback) => callback(batchId, error))
    })
  } catch (error) {
    console.error(`❌ [WEBSOCKET] Error creando canal para ${batchId}:`, error)
    if (process) {
      process.connection_status = "error"
    }
  }
}

const handleProgressUpdate = (batchId: string, data: any) => {
  const process = processes.value.find((p) => p.batch_id === batchId)
  if (!process) {
    console.warn(`⚠️ [UPDATE] Proceso no encontrado: ${batchId}`)
    return
  }

  console.log(`📊 [UPDATE] Datos recibidos para ${batchId}:`, data)

  // Extraer progreso
  let progress = 0
  if (data.metadata?.general_progress !== undefined) {
    progress = data.metadata.general_progress
  } else if (data.progress !== undefined) {
    progress = data.progress
  }

  progress = Math.min(100, Math.max(0, progress))

  // Actualizar proceso
  process.progress = progress
  process.current_element = data.current_element || "Procesando..."
  process.current_action = data.current_action || "Importando datos"

  // Actualizar detalles
  process.details = {
    current_element: data.current_element || "Procesando...",
    processed_records: data.processed_records || 0,
    total_records: data.total_records || 0,
    sheet: data.sheet || 1,
    last_update: new Date().toLocaleTimeString(),
  }

  console.log(`✅ [UPDATE] Progreso actualizado para ${batchId}: ${progress}%`)

  // Ejecutar callbacks
  callbacks.progressUpdated.forEach((callback) => callback(batchId, progress))

  // Verificar si completó
  if (progress >= 100) {
    console.log(`🎉 [UPDATE] ¡COMPLETADO! ${batchId}`)
    process.status = "completed"
    process.completed_at = new Date().toISOString()
    process.is_active = false
    stopWebSocket(batchId)

    setTimeout(() => {
      callbacks.completed.forEach((callback) => callback(batchId))
    }, 500)
  }
}

const stopWebSocket = (batchId: string) => {
  const process = processes.value.find((p) => p.batch_id === batchId)
  if (!process || !process.websocket_channel) return

  console.log(`🛑 [WEBSOCKET] Deteniendo para ${batchId}`)

  try {
    const echoInstance = getEcho()
    if (echoInstance) {
      echoInstance.leaveChannel(`import.progress.${batchId}`)
    }
    process.websocket_channel = undefined
    process.connection_status = "disconnected"
  } catch (error) {
    console.error(`❌ [WEBSOCKET] Error deteniendo ${batchId}:`, error)
  }
}

// Computed properties
const activeProcesses = computed(() => {
  return processes.value.filter((p) => p.status === "active")
})

const completedProcesses = computed(() => {
  return processes.value.filter((p) => p.status === "completed")
})

const currentActiveProcess = computed(() => {
  // Primero buscar el proceso marcado como activo
  const activeProcess = processes.value.find((p) => p.is_active && p.status === "active")
  if (activeProcess) return activeProcess

  // Si no hay ninguno marcado como activo, tomar el primero activo
  const firstActive = activeProcesses.value[0]
  if (firstActive) {
    // Marcarlo como activo
    processes.value.forEach((p) => {
      p.is_active = p.batch_id === firstActive.batch_id
    })
    activeProcessId.value = firstActive.batch_id
    return firstActive
  }

  return null
})

const overallProgress = computed(() => {
  if (processes.value.length === 0) return 0
  const totalProgress = processes.value.reduce((sum, process) => sum + process.progress, 0)
  return totalProgress / processes.value.length
})

const hasActiveProcesses = computed(() => {
  return activeProcesses.value.length > 0
})

const hasMultipleProcesses = computed(() => {
  return processes.value.length > 1
})

// Funciones principales
const startLoading = (batchId: string, fileName: string) => {
  console.log(`🚀 [START] Iniciando carga para batch: ${batchId}, archivo: ${fileName}`)

  // Verificar si ya existe el proceso
  const existingProcess = processes.value.find((p) => p.batch_id === batchId)
  if (existingProcess) {
    console.warn(`⚠️ [START] Proceso ya existe: ${batchId}`)
    return false
  }

  // Crear nuevo proceso
  const newProcess: ImportProcess = {
    batch_id: batchId,
    file_name: fileName,
    progress: 0,
    current_element: "Iniciando proceso...",
    current_action: "Preparando importación",
    status: "active",
    started_at: new Date().toISOString(),
    connection_status: "connecting",
    is_active: true,
    details: {
      current_element: "Iniciando proceso...",
      processed_records: 0,
      total_records: 0,
      sheet: 1,
      last_update: new Date().toLocaleTimeString(),
    },
  }

  processes.value.push(newProcess)

  // Si es el primer proceso, hacerlo activo
  if (!activeProcessId.value || activeProcesses.value.length === 1) {
    activeProcessId.value = batchId
    isMinimized.value = false

    // Marcar como activo
    processes.value.forEach((p) => {
      p.is_active = p.batch_id === batchId
    })
  }

  console.log(`✅ [START] Proceso creado para ${batchId}`)

  // Iniciar WebSocket
  startWebSocket(batchId)

  return true
}

const selectProcess = (batchId: string) => {
  console.log(`👁️ [SELECT] Seleccionando proceso: ${batchId}`)
  activeProcessId.value = batchId
  isMinimized.value = false
  showProcessList.value = false

  // Marcar como activo
  processes.value.forEach((p) => {
    p.is_active = p.batch_id === batchId
  })
}

const removeProcess = (batchId: string) => {
  console.log(`🗑️ [REMOVE] Eliminando proceso: ${batchId}`)

  // Detener WebSocket
  stopWebSocket(batchId)

  // Eliminar de la lista
  const index = processes.value.findIndex((p) => p.batch_id === batchId)
  if (index !== -1) {
    processes.value.splice(index, 1)
  }

  // Si era el proceso activo, seleccionar otro
  if (activeProcessId.value === batchId) {
    const nextActive = activeProcesses.value[0]
    activeProcessId.value = nextActive ? nextActive.batch_id : null
  }
}

const minimize = () => {
  console.log(`📦 [MINIMIZE] Minimizando vista`)
  isMinimized.value = true
}

const restore = () => {
  console.log(`📤 [RESTORE] Restaurando vista`)
  isMinimized.value = false
}

const showProcessListModal = () => {
  console.log(`📋 [LIST] Mostrando lista de procesos`)
  showProcessList.value = true
}

const hideProcessListModal = () => {
  console.log(`📋 [LIST] Ocultando lista de procesos`)
  showProcessList.value = false
}

const cleanup = () => {
  console.log(`🧹 [CLEANUP] Limpiando todos los procesos`)
  processes.value.forEach((process) => {
    stopWebSocket(process.batch_id)
  })
  processes.value = []
  activeProcessId.value = null
  isMinimized.value = false
  showProcessList.value = false
}

// Event listeners
const onCompleted = (callback: (batchId: string) => void) => {
  callbacks.completed.push(callback)
}

const onError = (callback: (batchId: string, error: any) => void) => {
  callbacks.error.push(callback)
}

const onProgressUpdated = (callback: (batchId: string, progress: number) => void) => {
  callbacks.progressUpdated.push(callback)
}

export function useMultipleLoading() {
  return {
    // Estado reactivo
    processes,
    activeProcesses,
    completedProcesses,
    currentActiveProcess,
    overallProgress,
    hasActiveProcesses,
    hasMultipleProcesses,
    activeProcessId,
    showProcessList,
    isMinimized,

    // Funciones principales
    startLoading,
    selectProcess,
    removeProcess,
    minimize,
    restore,
    showProcessListModal,
    hideProcessListModal,
    cleanup,

    // Event listeners
    onCompleted,
    onError,
    onProgressUpdated,
  }
}
