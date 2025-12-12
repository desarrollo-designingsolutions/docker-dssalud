<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import StatusEventConciliation from '@/pages/Schedule/Components/StatusEventConciliation.vue';
import { useConfigStore } from '@core/stores/config';
import { onMounted, ref } from 'vue';

const { toast } = useToast()
const route = useRoute()

const configStore = useConfigStore()
configStore.theme = 'light'

definePage({
  name: "ViewEventConciliationResponse",
  path: "/ViewEventConciliationResponse/:schedule_id",
  meta: {
    layout: 'blank',
    public: true,
  },
});

interface EventData {
  id: string | null
  user_name: string | null
  title: string | null
  start_date: string | null
  start_hour: string | null
  end_date: string | null
  end_hour: string | null
  description: string | null
  link: string | null
}

const loading = ref<boolean>(false)
const submitting = ref<boolean>(false)
const response = ref<string | null>(null)

const eventData = ref<EventData>({
  id: null,
  user_name: null,
  title: null,
  start_date: null,
  start_hour: null,
  end_date: null,
  end_hour: null,
  description: null,
  link: null,
})

const handleResponse = async (accepted: boolean) => {
  submitting.value = true

  try {
    // Simulate API call - replace with your actual API call
    await new Promise(resolve => setTimeout(resolve, 1500))



  } catch (error) {
    console.error('Error responding to invitation:', error)
  } finally {
    submitting.value = false
  }
}

const sendAccept = async () => {
  try {
    loading.value = true;
    const { data, response } = await useAxios(`/schedule/conciliation/acceptInvitation/${route.params.schedule_id}`).get();

    if (response.status === 200 && data) {
      eventData.value.response_status = data.event_data.response_status
      eventData.value.response_date = data.event_data.response_date
      response.value = 'accepted'
    }
  } catch (error) {
    toast('Error al cargar datos del evento', '', 'warning');
  } finally {
    loading.value = false;
  }
}

const sendReject = async () => {
  try {
    loading.value = true;
    const { data, response } = await useAxios(`/schedule/conciliation/rejectInvitation/${route.params.schedule_id}`).get();

    if (response.status === 200 && data) {
      eventData.value.response_status = data.event_data.response_status
      eventData.value.response_date = data.event_data.response_date
      response.value = 'rejected'
    }
  } catch (error) {
    toast('Error al cargar datos del evento', '', 'warning');
  } finally {
    loading.value = false;
  }
}

const getData = async () => {
  try {
    loading.value = true;
    const { data, response } = await useAxios(`/schedule/conciliation/getAcceptDataEvent/${route.params.schedule_id}`).get();

    if (response.status === 200 && data) {
      eventData.value = data.event_data
    }
  } catch (error) {
    toast('Error al cargar datos del evento', '', 'warning');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  getData();
});


</script>

<template>
  <v-app> 
    <div class="invitation-background" v-if="eventData?.response_status == 'SCHEDULE_RESPONSE_STATUS_001'">
      <v-container fluid class="fill-height pa-4">
        <v-row justify="center" align="center" class="fill-height">
          <v-col cols="12" sm="10" md="8" lg="6" xl="5">

            <!-- Loading State -->
            <v-card v-if="loading" class="invitation-card loading-card" elevation="0">
              <v-card-text class="text-center pa-12">
                <div class="loading-animation mb-6">
                  <v-progress-circular indeterminate color="primary" size="56" width="4" />
                </div>
                <h3 class="text-h5 font-weight-medium text-grey-darken-1 mb-2">
                  Cargando invitación
                </h3>
                <p class="text-body-2 text-grey">
                  Preparando los detalles del evento...
                </p>
              </v-card-text>
            </v-card>

            <!-- Event Invitation -->
            <v-card v-else class="invitation-card main-card" elevation="0">

              <!-- Header with brand colors -->
              <div class="invitation-header">
                <div class="header-content text-center pa-8">
                  <div class="brand-logo mb-4">
                    <v-avatar color="white" size="72" class="elevation-8 brand-avatar">
                      <v-icon icon="tabler-calendar-heart" size="36" color="primary" />
                    </v-avatar>
                  </div>
                  <h1 class="text-h4 font-weight-bold text-white mb-2">
                    Invitación Especial
                  </h1>
                  <p class="text-body-1 text-white text-opacity-90">
                    Has sido invitado a un evento importante
                  </p>
                </div>
              </div>

              <v-card-text class="pa-8">

                <!-- Event Title -->
                <div class="event-title-section mb-8">
                  <v-chip color="primary" variant="flat" size="small" class="mb-3 brand-chip">
                    <v-icon icon="tabler-star" start size="16" />
                    Evento
                  </v-chip>
                  <h2 class="text-h5 font-weight-bold text-grey-darken-3 mb-2">
                    {{ eventData.title || 'Evento sin título' }}
                  </h2>
                </div>

                <!-- Organizer Card -->
                <v-card v-if="eventData.user_name" variant="flat" color="primary" class="organizer-card mb-6">
                  <v-card-text class="pa-6">
                    <div class="d-flex align-center">
                      <v-avatar color="white" size="48" class="mr-4">
                        <v-icon icon="tabler-user" color="primary" size="24" />
                      </v-avatar>
                      <div class="flex-grow-1">
                        <p class="text-body-2 text-white text-opacity-80 mb-1">Organizador</p>
                        <h3 class="text-h6 font-weight-bold text-white">{{ eventData.user_name }}</h3>
                      </div>
                      <v-icon icon="tabler-crown" color="warning" size="20" />
                    </div>
                  </v-card-text>
                </v-card>

                <!-- Date and Time Card -->
                <v-card variant="outlined" class="datetime-card mb-6" color="surface-variant">
                  <v-card-text class="pa-6">
                    <div class="d-flex align-center mb-4">
                      <v-icon icon="tabler-calendar-clock" color="primary" size="24" class="mr-3" />
                      <h3 class="text-h6 font-weight-bold text-grey-darken-2">Fecha y Hora</h3>
                    </div>

                    <div class="datetime-details">
                      <div class="datetime-item mb-4">
                        <div class="d-flex align-center">
                          <v-avatar color="success" size="32" class="mr-3">
                            <v-icon icon="tabler-play" color="white" size="16" />
                          </v-avatar>
                          <div class="flex-grow-1">
                            <p class="text-body-2 text-success font-weight-medium mb-1">Inicio</p>
                            <p class="text-h6 font-weight-bold text-grey-darken-2 mb-0">
                              {{ eventData.start_date }}
                            </p>
                            <p v-if="eventData.start_hour" class="text-body-1 text-primary font-weight-medium">
                              {{ eventData.start_hour }}
                            </p>
                          </div>
                        </div>
                      </div>

                      <div class="datetime-item">
                        <div class="d-flex align-center">
                          <v-avatar color="error" size="32" class="mr-3">
                            <v-icon icon="tabler-percentage-100" color="white" size="16" />
                          </v-avatar>
                          <div class="flex-grow-1">
                            <p class="text-body-2 text-error font-weight-medium mb-1">Finalización</p>
                            <p class="text-h6 font-weight-bold text-grey-darken-2 mb-0">
                              {{ eventData.end_date }}
                            </p>
                            <p v-if="eventData.end_hour" class="text-body-1 text-primary font-weight-medium">
                              {{ eventData.end_hour }}
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </v-card-text>
                </v-card>

                <!-- Description Card -->
                <v-card v-if="eventData.description" variant="outlined" class="description-card mb-6">
                  <v-card-text class="pa-6">
                    <div class="d-flex align-center mb-4">
                      <v-icon icon="tabler-text-scan-2" color="primary" size="24" class="mr-3" />
                      <h3 class="text-h6 font-weight-bold text-grey-darken-2">Detalles del Evento</h3>
                    </div>
                    <p class="text-body-1 text-grey-darken-1 line-height-relaxed">
                      {{ eventData.description }}
                    </p>
                  </v-card-text>
                </v-card>

                <!-- Meeting Link Card -->
                <v-card v-if="eventData.link" variant="outlined" class="link-card mb-8">
                  <v-card-text class="pa-6">
                    <div class="d-flex align-center mb-4">
                      <v-icon icon="tabler-video" color="primary" size="24" class="mr-3" />
                      <h3 class="text-h6 font-weight-bold text-grey-darken-2">Enlace de Reunión</h3>
                    </div>
                    <v-btn :href="eventData.link" target="_blank" color="primary" variant="flat" size="large"
                      class="text-none brand-button" block>
                      <v-icon icon="tabler-link" start />
                      Unirse a la reunión
                    </v-btn>
                  </v-card-text>
                </v-card>

                <!-- Action Section -->
                <div class="action-section text-center">
                  <div class="action-header mb-6">
                    <h3 class="text-h5 font-weight-bold text-grey-darken-2 mb-2">
                      ¿Confirmas tu asistencia?
                    </h3>
                    <p class="text-body-1 text-grey-darken-1">
                      Por favor, selecciona una opción para confirmar tu respuesta
                    </p>
                  </div>

                  <div class="action-buttons">
                    <v-btn color="success" size="x-large" variant="flat" :loading="submitting"
                      @click="sendAccept()" class="action-btn accept-btn mr-4 mb-4 mb-sm-0" min-width="140">
                      <v-icon icon="tabler-circle-check" start size="20" />
                      Aceptar
                    </v-btn>

                    <v-btn color="error" size="x-large" variant="flat" :loading="submitting"
                      @click="sendReject()" class="action-btn decline-btn" min-width="140">
                      <v-icon icon="tabler-circle-x" start size="20" />
                      Rechazar
                    </v-btn>
                  </div>
                </div>

              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </div>
    <StatusEventConciliation v-if="!loading && eventData && eventData.response_status != 'SCHEDULE_RESPONSE_STATUS_001'"
      :eventData="eventData" />
  </v-app>
</template>

<style scoped>
.invitation-background {
  position: relative;
  background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #3b82f6 100%);
  min-block-size: 100vh;
}

.invitation-background::before {
  position: absolute;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.08"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.08"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.06"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.06"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.06"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
  content: "";
  inset: 0;
  pointer-events: none;
}

.fill-height {
  min-block-size: 100vh;
}

.invitation-card {
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 30%);
  border-radius: 24px !important;
  backdrop-filter: blur(20px);
  background: rgba(255, 255, 255, 98%) !important;
  box-shadow: 0 25px 50px rgba(30, 64, 175, 15%) !important;
}

.loading-card,
.response-card {
  background: rgba(255, 255, 255, 98%) !important;
}

.loading-animation {
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%,
  100% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.05);
  }
}

.response-icon-container {
  position: relative;
}

.response-avatar {
  animation: bounceIn 0.6s ease-out;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 15%);
}

@keyframes bounceIn {
  0% {
    opacity: 0;
    transform: scale(0);
  }

  50% {
    opacity: 0.8;
    transform: scale(1.1);
  }

  100% {
    opacity: 1;
    transform: scale(1);
  }
}

.main-card {
  background: rgba(255, 255, 255, 98%) !important;
}

.invitation-header {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #3b82f6 100%);
}

.invitation-header::before {
  position: absolute;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
  content: "";
  inset: 0;
}

.header-content {
  position: relative;
  z-index: 1;
}

.brand-logo {
  animation: float 3s ease-in-out infinite;
}

.brand-avatar {
  box-shadow: 0 8px 32px rgba(37, 99, 235, 30%) !important;
}

@keyframes float {
  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-8px);
  }
}

.event-title-section {
  border-inline-start: 4px solid #2563eb;
  padding-inline-start: 16px;
}

.brand-chip {
  color: white !important;
  font-weight: 600;
}

.organizer-card {
  border-radius: 16px !important;
  background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%) !important;
  box-shadow: 0 8px 32px rgba(37, 99, 235, 25%) !important;
}

.datetime-card {
  border: 2px solid #e2e8f0 !important;
  border-radius: 16px !important;
  background: #f8fafc !important;
  transition: all 0.3s ease;
}

.datetime-card:hover {
  border-color: #2563eb !important;
  box-shadow: 0 8px 32px rgba(37, 99, 235, 10%) !important;
}

.datetime-details {
  position: relative;
}

.datetime-item {
  position: relative;
}

.datetime-item:not(:last-child)::after {
  position: absolute;
  border-radius: 1px;
  background: linear-gradient(to bottom, #10b981, #ef4444);
  block-size: 16px;
  content: "";
  inline-size: 2px;
  inset-block-start: 100%;
  inset-inline-start: 16px;
}

.description-card,
.link-card {
  border: 2px solid #e2e8f0 !important;
  border-radius: 16px !important;
  transition: all 0.3s ease;
}

.description-card:hover,
.link-card:hover {
  border-color: #2563eb !important;
  box-shadow: 0 4px 20px rgba(37, 99, 235, 10%) !important;
}

.line-height-relaxed {
  line-height: 1.7;
}

.brand-button {
  border-radius: 12px !important;
  box-shadow: 0 4px 16px rgba(37, 99, 235, 20%) !important;
  font-weight: 600 !important;
}

.brand-button:hover {
  box-shadow: 0 8px 24px rgba(37, 99, 235, 30%) !important;
  transform: translateY(-1px);
}

.action-section {
  border: 2px solid rgba(37, 99, 235, 10%);
  border-radius: 20px;
  background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
  padding-block: 32px;
  padding-inline: 24px;
}

.action-buttons {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 16px;
}

.action-btn {
  border-radius: 16px !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 10%) !important;
  font-weight: 600 !important;
  letter-spacing: 0.5px;
  text-transform: none !important;
  transition: all 0.3s ease !important;
}

.accept-btn:hover {
  box-shadow: 0 8px 24px rgba(16, 185, 129, 30%) !important;
  transform: translateY(-2px);
}

.decline-btn:hover {
  box-shadow: 0 8px 24px rgba(239, 68, 68, 30%) !important;
  transform: translateY(-2px);
}

/* Responsive adjustments */
@media (max-width: 600px) {
  .invitation-background {
    padding: 8px;
  }

  .invitation-card {
    border-radius: 16px !important;
  }

  .header-content {
    padding-block: 24px !important;
    padding-inline: 16px !important;
  }

  .action-buttons {
    flex-direction: column;
    align-items: center;
  }

  .action-btn {
    inline-size: 100%;
    max-inline-size: 280px;
  }
}

/* Loading states */
.v-progress-circular {
  filter: drop-shadow(0 4px 8px rgba(37, 99, 235, 30%));
}

/* Custom scrollbar */
::-webkit-scrollbar {
  inline-size: 8px;
}

::-webkit-scrollbar-track {
  border-radius: 4px;
  background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
  border-radius: 4px;
  background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
}
</style>
