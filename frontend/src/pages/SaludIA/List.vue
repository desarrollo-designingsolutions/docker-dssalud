<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { marked } from 'marked'

definePage({
  name: "SaludIA-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "saludIA.list",
  },
})

// Tipos
interface Message {
  id: number
  role: 'user' | 'assistant'
  content: string
  created_at: string
  metadata?: {
    chunks_utilizados?: number
    distancia_promedio?: number
  }
}

interface FileItem {
  id: number
  original_filename: string
  size: number
  created_at: string
}

interface Conversation {
  id: number
  name: string
  created_at: string
  updated_at: string
  message_count: number
  messages: Message[]
  files: FileItem[]
}

// Estado reactivo
const router = useRouter()
const activeTab = ref<'files' | 'conversations'>('conversations')
const sessionId = ref<string>('')
const conversations = ref<Conversation[]>([])
const currentConversation = ref<Conversation | null>(null)
const isProcessing = ref(false)
const files = ref<FileItem[]>([])
const newConversationDialog = ref(false)
const conversationName = ref('Nueva Conversación')
const questionInput = ref('')
const dropZoneActive = ref(false)
const uploadProgress = ref(0)
const showUploadProgress = ref(false)

// Computed
const hasActiveConversation = computed(() => currentConversation.value !== null)
const filesCount = computed(() => files.value.length)
const sessionIdDisplay = computed(() => sessionId.value.substring(0, 15) + '...')
const isUploadDisabled = computed(() => !hasActiveConversation.value)

// Inicialización
onMounted(() => {
  initializeSession()
  loadConversations()
})

// Funciones
function initializeSession() {
  const savedSessionId = localStorage.getItem('notebooklm_session_id')
  if (savedSessionId) {
    sessionId.value = savedSessionId
  } else {
    sessionId.value = generateSessionId()
    localStorage.setItem('notebooklm_session_id', sessionId.value)
  }
}

function generateSessionId(): string {
  return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
}

async function loadConversations() {
  try {
    // Simulación - reemplazar con API real
    const mockConversations: Conversation[] = [
      {
        id: 1,
        name: 'Conversación de ejemplo',
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
        message_count: 3,
        messages: [
          {
            id: 1,
            role: 'user',
            content: 'Hola, ¿qué puedes hacer?',
            created_at: new Date().toISOString()
          },
          {
            id: 2,
            role: 'assistant',
            content: 'Puedo ayudarte a analizar tus documentos médicos y responder preguntas sobre ellos.',
            created_at: new Date().toISOString(),
            metadata: { chunks_utilizados: 2, distancia_promedio: 0.123 }
          }
        ],
        files: [
          {
            id: 1,
            original_filename: 'informe_medico.pdf',
            size: 2048000,
            created_at: new Date().toISOString()
          }
        ]
      }
    ]
    conversations.value = mockConversations
  } catch (error) {
    console.error('Error loading conversations:', error)
  }
}

function selectConversation(conversation: Conversation) {
  currentConversation.value = conversation
  files.value = conversation.files
}

async function createNewConversation() {
  if (!conversationName.value.trim()) return

  const newConversation: Conversation = {
    id: conversations.value.length + 1,
    name: conversationName.value,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    message_count: 0,
    messages: [],
    files: []
  }

  conversations.value.unshift(newConversation)
  currentConversation.value = newConversation
  files.value = []
  conversationName.value = 'Nueva Conversación'
  newConversationDialog.value = false
}

async function sendMessage() {
  if (!questionInput.value.trim() || !currentConversation.value || isProcessing.value) return

  const userMessage: Message = {
    id: currentConversation.value.messages.length + 1,
    role: 'user',
    content: questionInput.value,
    created_at: new Date().toISOString()
  }

  // Agregar mensaje del usuario
  currentConversation.value.messages.push(userMessage)
  const currentQuestion = questionInput.value
  questionInput.value = ''
  isProcessing.value = true

  try {
    // Simulación de respuesta - reemplazar con API real
    await new Promise(resolve => setTimeout(resolve, 1000))

    const assistantMessage: Message = {
      id: currentConversation.value.messages.length + 1,
      role: 'assistant',
      content: `He analizado tu pregunta sobre "${currentQuestion}". Basándome en los documentos disponibles, puedo proporcionarte información relevante sobre el tema.`,
      created_at: new Date().toISOString(),
      metadata: { chunks_utilizados: 3, distancia_promedio: 0.456 }
    }

    currentConversation.value.messages.push(assistantMessage)
    currentConversation.value.message_count = currentConversation.value.messages.length
    currentConversation.value.updated_at = new Date().toISOString()
  } catch (error) {
    console.error('Error sending message:', error)
  } finally {
    isProcessing.value = false
  }
}

function handleFileSelect(event: Event) {
  const input = event.target as HTMLInputElement
  if (!input.files?.length || !currentConversation.value) return

  uploadFiles(Array.from(input.files))
}

function handleDrop(event: DragEvent) {
  event.preventDefault()
  dropZoneActive.value = false

  if (!event.dataTransfer?.files.length || !currentConversation.value) return

  uploadFiles(Array.from(event.dataTransfer.files))
}

function handleDragOver(event: DragEvent) {
  event.preventDefault()
  if (hasActiveConversation.value) {
    dropZoneActive.value = true
  }
}

function handleDragLeave() {
  dropZoneActive.value = false
}

async function uploadFiles(fileList: File[]) {
  if (!currentConversation.value) return

  showUploadProgress.value = true
  uploadProgress.value = 0

  // Simular progreso de carga
  const interval = setInterval(() => {
    uploadProgress.value += 10
    if (uploadProgress.value >= 100) {
      clearInterval(interval)
      setTimeout(() => {
        showUploadProgress.value = false
        uploadProgress.value = 0

        // Agregar archivos simulados
        fileList.forEach((file, index) => {
          const newFile: FileItem = {
            id: files.value.length + index + 1,
            original_filename: file.name,
            size: file.size,
            created_at: new Date().toISOString()
          }
          files.value.push(newFile)
          currentConversation.value!.files.push(newFile)
        })
      }, 500)
    }
  }, 100)
}

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

function createNewSession() {
  if (confirm('¿Estás seguro de que quieres crear una nueva sesión? Se perderán todas las conversaciones y archivos actuales.')) {
    sessionId.value = generateSessionId()
    localStorage.setItem('notebooklm_session_id', sessionId.value)
    currentConversation.value = null
    conversations.value = []
    files.value = []
  }
}

function deleteCurrentConversation() {
  if (!currentConversation.value) return

  if (confirm('¿Estás seguro de que quieres eliminar esta conversación? Se eliminarán todos los mensajes y archivos asociados.')) {
    const index = conversations.value.findIndex(c => c.id === currentConversation.value!.id)
    if (index !== -1) {
      conversations.value.splice(index, 1)
    }
    currentConversation.value = null
    files.value = []
  }
}

function clearChat() {
  if (!currentConversation.value) return

  if (confirm('¿Estás seguro de que quieres limpiar el historial de esta conversación?')) {
    currentConversation.value.messages = []
    currentConversation.value.message_count = 0
    currentConversation.value.updated_at = new Date().toISOString()
  }
}

function renderMarkdown(content: string): string {
  return marked.parse(content) as string
}

// Watch para actualizar archivos cuando cambia la conversación
watch(currentConversation, (newConv) => {
  if (newConv) {
    files.value = newConv.files
  } else {
    files.value = []
  }
})
</script>

<template>
  <VContainer fluid class="pa-6">
    <VRow>
      <!-- Sidebar -->
      <VCol cols="12" lg="3">
        <VCard class="sidebar-gradient" height="calc(100vh - 120px)">
          <VCardText>
            <!-- Encabezado de sesión -->
            <div class="mb-6">
              <h2 class="text-h6 font-weight-bold mb-2 text-white">Sesión Actual</h2>
              <VCard color="rgba(255, 255, 255, 0.1)" class="mb-2">
                <VCardText class="pa-3">
                  <div class="d-flex align-center justify-space-between">
                    <span class="text-body-2 font-weight-medium text-white">{{ sessionIdDisplay }}</span>
                    <VBtn size="x-small" color="white" variant="tonal" @click="createNewSession">
                      <VIcon icon="mdi-plus" size="small" />
                      Nueva
                    </VBtn>
                  </div>
                </VCardText>
              </VCard>
            </div>

            <!-- Tabs -->
            <VTabs v-model="activeTab" grow class="mb-4">
              <VTab value="files" class="text-white">
                Archivos
              </VTab>
              <VTab value="conversations" class="text-white">
                Conversaciones
              </VTab>
            </VTabs>

            <!-- Contenido de pestañas -->
            <VWindow v-model="activeTab">
              <!-- Pestaña Archivos -->
              <VWindowItem value="files">
                <!-- Área de subida -->
                <div class="mb-4">
                  <VAlert v-if="!hasActiveConversation" type="info" density="compact" class="mb-3">
                    Selecciona o crea una conversación para subir archivos
                  </VAlert>

                  <VCard
                    :class="['drop-zone', { 'active-drop-zone': dropZoneActive, 'disabled-drop-zone': !hasActiveConversation }]"
                    @dragover="handleDragOver" @dragleave="handleDragLeave" @drop="handleDrop"
                    @click="$refs.fileInput?.click()" :disabled="!hasActiveConversation">
                    <VCardText class="text-center pa-6">
                      <VIcon icon="mdi-cloud-upload" size="48"
                        :class="hasActiveConversation ? 'text-primary' : 'text-grey'" class="mb-2" />
                      <p class="text-body-2 mb-2">Arrastra archivos aquí o haz clic para seleccionar</p>
                      <VBtn size="small" color="primary" :disabled="!hasActiveConversation"
                        @click.stop="$refs.fileInput?.click()">
                        Seleccionar Archivos
                      </VBtn>

                      <!-- Progress bar -->
                      <VProgressLinear v-if="showUploadProgress" v-model="uploadProgress" color="primary" height="8"
                        class="mt-3" />
                    </VCardText>
                  </VCard>

                  <input ref="fileInput" type="file" multiple class="d-none" :disabled="!hasActiveConversation"
                    @change="handleFileSelect" />
                </div>

                <!-- Lista de archivos -->
                <div>
                  <div class="d-flex align-center justify-space-between mb-2">
                    <h3 class="text-subtitle-2 font-weight-medium text-white">Archivos de esta conversación</h3>
                    <VChip size="small" color="rgba(255, 255, 255, 0.2)">
                      {{ filesCount }}
                    </VChip>
                  </div>

                  <VList class="bg-transparent">
                    <VListItem v-for="file in files" :key="file.id" class="mb-2 file-card">
                      <VListItemTitle class="text-body-2 font-weight-medium text-white">
                        {{ file.original_filename }}
                      </VListItemTitle>
                      <VListItemSubtitle class="text-caption text-grey-lighten-1">
                        {{ formatFileSize(file.size) }} • {{ formatDate(file.created_at) }}
                      </VListItemSubtitle>
                    </VListItem>

                    <VListItem v-if="files.length === 0">
                      <VListItemTitle class="text-center text-grey-lighten-1">
                        <VIcon icon="mdi-folder-open" size="48" class="mb-2" />
                        <p>No hay archivos en esta conversación</p>
                      </VListItemTitle>
                    </VListItem>
                  </VList>
                </div>
              </VWindowItem>

              <!-- Pestaña Conversaciones -->
              <VWindowItem value="conversations">
                <!-- Botón nueva conversación -->
                <VBtn block color="white" variant="tonal" class="mb-4" @click="newConversationDialog = true">
                  <VIcon icon="mdi-plus" class="mr-2" />
                  Nueva Conversación
                </VBtn>

                <!-- Lista de conversaciones -->
                <div>
                  <h3 class="text-subtitle-2 font-weight-medium text-white mb-2">Tus Conversaciones</h3>

                  <VList class="bg-transparent">
                    <VListItem v-for="conversation in conversations" :key="conversation.id"
                      :class="['conversation-item', { 'active-conversation': currentConversation?.id === conversation.id }]"
                      @click="selectConversation(conversation)">
                      <VListItemTitle class="text-body-2 font-weight-medium text-white">
                        {{ conversation.name }}
                      </VListItemTitle>
                      <VListItemSubtitle class="text-caption text-grey-lighten-1">
                        {{ formatDate(conversation.updated_at) }} • {{ conversation.message_count }} mensajes
                      </VListItemSubtitle>
                    </VListItem>

                    <VListItem v-if="conversations.length === 0">
                      <VListItemTitle class="text-center text-grey-lighten-1">
                        <VIcon icon="mdi-comment" size="48" class="mb-2" />
                        <p>No hay conversaciones</p>
                        <p class="text-caption mt-2">Crea tu primera conversación</p>
                      </VListItemTitle>
                    </VListItem>
                  </VList>
                </div>
              </VWindowItem>
            </VWindow>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Área principal de chat -->
      <VCol cols="12" lg="9">
        <VCard class="chat-container" height="calc(100vh - 120px)">
          <!-- Encabezado del chat -->
          <VCardTitle class="border-b">
            <div class="d-flex align-center justify-space-between w-100">
              <div>
                <h2 class="text-h5 font-weight-bold">
                  {{ currentConversation?.name || 'Selecciona una Conversación' }}
                </h2>
                <p class="text-body-2 text-grey">
                  {{
                    currentConversation
                      ? `${currentConversation.files.length} archivos • ${currentConversation.messages.length} mensajes`
                      : 'Crea o selecciona una conversación para comenzar'
                  }}
                </p>
              </div>

              <div v-if="hasActiveConversation" class="d-flex gap-2">
                <VBtn icon size="small" variant="text" color="grey" @click="clearChat" title="Limpiar chat">
                  <VIcon icon="mdi-broom" />
                </VBtn>
                <VBtn icon size="small" variant="text" color="error" @click="deleteCurrentConversation"
                  title="Eliminar conversación">
                  <VIcon icon="mdi-trash" />
                </VBtn>
              </div>
            </div>
          </VCardTitle>

          <!-- Área de mensajes -->
          <VCardText class="messages-container">
            <div v-if="!currentConversation" class="text-center py-12">
              <VIcon icon="mdi-comment" size="64" color="grey-lighten-2" class="mb-4" />
              <h3 class="text-h6 font-weight-medium text-grey-darken-1 mb-2">
                Bienvenido a DS Salud IA
              </h3>
              <p class="text-body-1 text-grey mb-4">
                Crea una conversación para comenzar a chatear con tus documentos
              </p>
              <VBtn color="primary" size="large" @click="newConversationDialog = true">
                <VIcon icon="mdi-plus" class="mr-2" />
                Crear Primera Conversación
              </VBtn>
            </div>

            <!-- Mensajes de la conversación -->
            <div v-else>
              <div v-for="message in currentConversation.messages" :key="message.id"
                :class="['message pa-3 mb-3', message.role === 'user' ? 'user-message' : 'assistant-message']">
                <div class="message-header mb-2">
                  <span class="text-caption font-weight-bold">
                    {{ message.role === 'user' ? 'Tú' : 'DS Salud IA' }}
                  </span>
                  <span class="text-caption text-grey ml-2">
                    {{ formatDate(message.created_at) }}
                  </span>
                </div>

                <div class="markdown-content" v-html="renderMarkdown(message.content)" />

                <div v-if="message.metadata" class="text-caption text-grey mt-2">
                  <span v-if="message.metadata.chunks_utilizados">
                    Chunks: {{ message.metadata.chunks_utilizados }}
                  </span>
                  <span v-if="message.metadata.distancia_promedio" class="ml-2">
                    Distancia: {{ message.metadata.distancia_promedio.toFixed(3) }}
                  </span>
                </div>
              </div>

              <!-- Indicador de escritura -->
              <div v-if="isProcessing" class="typing-indicator pa-3">
                <span class="text-body-2">Asistente está escribiendo</span>
                <div class="typing-dots ml-2">
                  <div class="typing-dot"></div>
                  <div class="typing-dot"></div>
                  <div class="typing-dot"></div>
                </div>
              </div>
            </div>
          </VCardText>

          <!-- Área de entrada -->
          <VCardActions>
            <VForm @submit.prevent="sendMessage" class="w-100">
              <VTextField v-model="questionInput"
                :placeholder="hasActiveConversation ? 'Escribe tu pregunta...' : 'Selecciona una conversación para comenzar...'"
                :disabled="!hasActiveConversation || isProcessing" density="comfortable" variant="outlined" hide-details
                class="mr-2" @keydown.enter.prevent="sendMessage">
                <template #append-inner>
                  <VBtn type="submit" icon color="primary"
                    :disabled="!hasActiveConversation || !questionInput.trim() || isProcessing" :loading="isProcessing">
                    <VIcon icon="mdi-send" />
                  </VBtn>
                </template>
              </VTextField>
            </VForm>
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>

    <!-- Modal para nueva conversación -->
    <VDialog v-model="newConversationDialog" max-width="500">
      <VCard>
        <VCardTitle class="text-h5">Nueva Conversación</VCardTitle>
        <VCardText>
          <VTextField v-model="conversationName" label="Nombre de la conversación" placeholder="Mi conversación"
            density="comfortable" variant="outlined" class="mb-4" />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="newConversationDialog = false">
            Cancelar
          </VBtn>
          <VBtn color="primary" @click="createNewConversation">
            Crear
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VContainer>
</template>

<style scoped>
/* Estilos específicos para NotebookLM */
.sidebar-gradient {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  color: white !important;
}

.chat-container {
  display: flex;
  flex-direction: column;
}

.messages-container {
  flex: 1;
  overflow-y: auto;
  max-height: calc(100vh - 250px);
}

.message {
  border-radius: 12px;
  max-width: 80%;
}

.user-message {
  background-color: #e0e7ff;
  margin-left: auto;
  border-bottom-right-radius: 4px;
}

.assistant-message {
  background-color: white;
  border: 1px solid #e2e8f0;
  margin-right: auto;
  border-bottom-left-radius: 4px;
}

.message-header {
  font-weight: 600;
  font-size: 0.875rem;
  color: #4f46e5;
}

.assistant-message .message-header {
  color: #059669;
}

.file-card {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  backdrop-filter: blur(10px);
}

.conversation-item {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  border-left: 4px solid transparent;
}

.conversation-item:hover {
  background: rgba(255, 255, 255, 0.2);
}

.conversation-item.active-conversation {
  background: rgba(255, 255, 255, 0.25);
  border-left: 4px solid white;
}

.drop-zone {
  border: 2px dashed #cbd5e0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.drop-zone.disabled-drop-zone {
  opacity: 0.5;
  cursor: not-allowed;
  border-color: #94a3b8;
}

.drop-zone.active-drop-zone {
  border-color: #667eea;
  background-color: #f7fafc;
}

.typing-indicator {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  background-color: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  margin-bottom: 1rem;
  max-width: 80%;
}

.typing-dots {
  display: flex;
  margin-left: 0.5rem;
}

.typing-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background-color: #94a3b8;
  margin: 0 1px;
  animation: typing 1.4s infinite ease-in-out;
}

.typing-dot:nth-child(1) {
  animation-delay: -0.32s;
}

.typing-dot:nth-child(2) {
  animation-delay: -0.16s;
}

@keyframes typing {

  0%,
  80%,
  100% {
    transform: scale(0.8);
    opacity: 0.5;
  }

  40% {
    transform: scale(1);
    opacity: 1;
  }
}

/* Estilos para markdown */
.markdown-content :deep(h1),
.markdown-content :deep(h2),
.markdown-content :deep(h3) {
  font-weight: 600;
  margin-top: 1rem;
  margin-bottom: 0.5rem;
}

.markdown-content :deep(h1) {
  font-size: 1.5rem;
}

.markdown-content :deep(h2) {
  font-size: 1.25rem;
}

.markdown-content :deep(h3) {
  font-size: 1.125rem;
}

.markdown-content :deep(ul),
.markdown-content :deep(ol) {
  margin-left: 1.5rem;
  margin-bottom: 1rem;
}

.markdown-content :deep(li) {
  margin-bottom: 0.25rem;
}

.markdown-content :deep(strong) {
  font-weight: 600;
}

.markdown-content :deep(em) {
  font-style: italic;
}

.markdown-content :deep(code) {
  background-color: #f1f5f9;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-family: monospace;
  font-size: 0.875rem;
}

.markdown-content :deep(pre) {
  background-color: #1e293b;
  color: #e2e8f0;
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin-bottom: 1rem;
}

.markdown-content :deep(blockquote) {
  border-left: 4px solid #cbd5e0;
  padding-left: 1rem;
  margin-left: 0;
  color: #64748b;
  font-style: italic;
}

/* Scrollbar personalizado */
.messages-container::-webkit-scrollbar {
  width: 6px;
}

.messages-container::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb:hover {
  background: #555;
}

.border-b {
  border-bottom: 1px solid #e2e8f0;
}
</style>