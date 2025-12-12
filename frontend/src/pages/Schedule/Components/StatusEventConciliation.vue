<script setup lang="ts">
import { ref } from 'vue';

const { eventData } = defineProps<{
  eventData: {
    id: string | null
    user_name: string | null
    title: string | null
    start_date: string | null
    start_hour: string | null
    end_date: string | null
    end_hour: string | null
    description: string | null
    link: string | null
    response_status: string | null
    response_date: string | null
  }
}>()

const loading = ref<boolean>(false)

const copyMeetingLink = async () => {
  if (eventData.link) {
    try {
      await navigator.clipboard.writeText(eventData.link)
      // Aquí podrías mostrar un toast de confirmación
    } catch (error) {
      console.error('Error al copiar el enlace:', error)
    }
  }
}

</script>

<template>
  <v-app>
    <div class="invitation-background">
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
                  Cargando estado de respuesta
                </h3>
                <p class="text-body-2 text-grey">
                  Verificando tu respuesta a la invitación...
                </p>
              </v-card-text>
            </v-card>

            <!-- Response Status Card -->
            <v-card v-else class="invitation-card response-status-card" elevation="0">

              <!-- Header with status -->
              <div class="response-header"
                :class="eventData.response_status === 'SCHEDULE_RESPONSE_STATUS_002' ? 'accepted-header' : 'rejected-header'">
                <div class="header-content text-center pa-8">
                  <div class="status-icon mb-4">
                    <v-avatar
                      :color="eventData.response_status === 'SCHEDULE_RESPONSE_STATUS_002' ? 'success' : 'error'"
                      size="80" class="elevation-8 status-avatar">
                      <v-icon
                        :icon="eventData.response_status === 'SCHEDULE_RESPONSE_STATUS_002' ? 'tabler-circle-check' : 'tabler-circle-x'"
                        size="40" color="white" />
                    </v-avatar>
                  </div>
                  <h1 class="text-h4 font-weight-bold text-white mb-2">
                    {{ eventData.response_status === 'SCHEDULE_RESPONSE_STATUS_002' ? '¡Invitación Aceptada!' :
                    'Invitación Rechazada' }}
                  </h1>
                  <p class="text-body-1 text-white text-opacity-90">
                    {{ eventData.response_status === 'SCHEDULE_RESPONSE_STATUS_002'
                      ? 'Tu asistencia ha sido confirmada exitosamente'
                      : 'Serás reagendado para una nueva fecha'
                    }}
                  </p>
                </div>
              </div>

              <v-card-text class="pa-8">

                <!-- Status Summary -->
                <div class="status-summary mb-8">
                  <v-card variant="flat"
                    :color="eventData.response_status === 'SCHEDULE_RESPONSE_STATUS_002' ? 'success' : 'error'"
                    class="status-card">
                    <v-card-text class="pa-6">
                      <div class="d-flex align-center">
                        <v-avatar
                          :color="eventData.response_status === 'SCHEDULE_RESPONSE_STATUS_002' ? 'white' : 'white'"
                          size="48" class="mr-4">
                          <v-icon
                            :icon="eventData.response_status === 'SCHEDULE_RESPONSE_STATUS_002' ? 'tabler-calendar-check' : 'tabler-calendar-clock'"
                            color="white" size="24" />
                        </v-avatar>
                        <div class="flex-grow-1">
                          <p class="text-body-2 text-white text-opacity-80 mb-1">Estado de Respuesta</p>
                          <h3 class="text-h6 font-weight-bold text-white">
                            {{ eventData.response_status === 'SCHEDULE_RESPONSE_STATUS_002' ? 'Confirmado' :
                            'Reagendamiento Pendiente' }}
                          </h3>
                          <p v-if="eventData.response_date" class="text-body-2 text-white text-opacity-70 mb-0">
                            Respondido el {{ eventData.response_date }}
                          </p>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </div>

                <!-- Event Details -->
                <div class="event-details-section mb-8">
                  <div class="d-flex align-center mb-4">
                    <v-icon icon="tabler-text-scan-2" color="primary" size="24" class="mr-3" />
                    <h3 class="text-h6 font-weight-bold text-grey-darken-2">Detalles del Evento</h3>
                  </div>

                  <v-card variant="outlined" class="event-info-card">
                    <v-card-text class="pa-6">
                      <h4 class="text-h6 font-weight-bold text-grey-darken-3 mb-4">
                        {{ eventData.title || 'Evento sin título' }}
                      </h4>

                      <div class="event-meta mb-4">
                        <div class="d-flex align-center mb-2">
                          <v-icon icon="tabler-user" color="primary" size="20" class="mr-2" />
                          <span class="text-body-2 text-grey-darken-1">
                            <strong>Organizador:</strong> {{ eventData.user_name }}
                          </span>
                        </div>
                        <div class="d-flex align-center mb-2">
                          <v-icon icon="tabler-calendar" color="primary" size="20" class="mr-2" />
                          <span class="text-body-2 text-grey-darken-1">
                            <strong>Fecha:</strong> {{ eventData.start_date }}
                          </span>
                        </div>
                        <div class="d-flex align-center">
                          <v-icon icon="tabler-clock" color="primary" size="20" class="mr-2" />
                          <span class="text-body-2 text-grey-darken-1">
                            <strong>Horario:</strong> {{ eventData.start_hour }} - {{ eventData.end_hour }}
                          </span>
                        </div>
                      </div>

                      <v-divider class="my-4" />

                      <p v-if="eventData.description" class="text-body-1 text-grey-darken-1 line-height-relaxed mb-0">
                        {{ eventData.description }}
                      </p>
                    </v-card-text>
                  </v-card>
                </div>

                <!-- Meeting Link Section (Only for accepted invitations) -->
                <div v-if="eventData.response_status === 'SCHEDULE_RESPONSE_STATUS_002' && eventData.link"
                  class="meeting-section mb-8">
                  <div class="d-flex align-center mb-4">
                    <v-icon icon="tabler-video" color="primary" size="24" class="mr-3" />
                    <h3 class="text-h6 font-weight-bold text-grey-darken-2">Enlace de Reunión</h3>
                  </div>

                  <v-card variant="outlined" class="meeting-card">
                    <v-card-text class="pa-6">
                      <div class="meeting-info mb-4">
                        <v-alert type="success" variant="tonal" class="mb-4">
                          <template #prepend>
                            <v-icon icon="tabler-file-info" />
                          </template>
                          <div>
                            <strong>¡Tu asistencia está confirmada!</strong><br>
                            Guarda este enlace para unirte a la reunión en la fecha programada.
                          </div>
                        </v-alert>
                      </div>

                      <div class="meeting-actions">
                        <v-btn :href="eventData.link" target="_blank" color="primary" variant="flat" size="large"
                          class="brand-button mr-4 mb-3 mb-sm-0" min-width="160">
                          <v-icon icon="tabler-video" start />
                          Unirse Ahora
                        </v-btn>

                        <v-btn @click="copyMeetingLink" color="primary" variant="outlined" size="large"
                          class="mb-3 mb-sm-0" min-width="160">
                          <v-icon icon="tabler-copy" start />
                          Copiar Enlace
                        </v-btn>
                      </div>
                    </v-card-text>
                  </v-card>
                </div>

                <!-- Rescheduling Section (Only for rejected invitations) -->
                <div v-if="eventData.response_status === 'SCHEDULE_RESPONSE_STATUS_003'" class="rescheduling-section mb-8">
                  <div class="d-flex align-center mb-4">
                    <v-icon icon="tabler-calendar-time" color="error" size="24" class="mr-3" />
                    <h3 class="text-h6 font-weight-bold text-grey-darken-2">Reagendamiento</h3>
                  </div>

                  <v-card variant="outlined" class="rescheduling-card">
                    <v-card-text class="pa-6">
                      <v-alert type="error" variant="tonal" class="mb-4">
                        <template #prepend>
                          <v-icon icon="tabler-alarm" />
                        </template>
                        <div>
                          <strong>Reagendamiento en proceso</strong><br>
                          El organizador será notificado de tu respuesta y te contactará para coordinar una nueva fecha
                          que se ajuste a
                          tu disponibilidad.
                        </div>
                      </v-alert>

                      <div class="rescheduling-info">
                        <h4 class="text-h6 font-weight-medium text-grey-darken-2 mb-3">¿Qué sigue?</h4>
                        <div class="rescheduling-steps">
                          <div class="d-flex align-start mb-3">
                            <v-avatar color="error" size="24" class="mr-3 mt-1">
                              <span class="text-caption font-weight-bold text-white">1</span>
                            </v-avatar>
                            <div>
                              <p class="text-body-2 text-grey-darken-1 mb-1">
                                <strong>Notificación enviada:</strong> El organizador ha sido informado de tu respuesta.
                              </p>
                            </div>
                          </div>
                          <div class="d-flex align-start mb-3">
                            <v-avatar color="error" size="24" class="mr-3 mt-1">
                              <span class="text-caption font-weight-bold text-white">2</span>
                            </v-avatar>
                            <div>
                              <p class="text-body-2 text-grey-darken-1 mb-1">
                                <strong>Contacto directo:</strong> Recibirás una comunicación para coordinar una nueva
                                fecha.
                              </p>
                            </div>
                          </div>
                          <div class="d-flex align-start">
                            <v-avatar color="error" size="24" class="mr-3 mt-1">
                              <span class="text-caption font-weight-bold text-white">3</span>
                            </v-avatar>
                            <div>
                              <p class="text-body-2 text-grey-darken-1 mb-1">
                                <strong>Nueva invitación:</strong> Una vez acordada la fecha, recibirás una nueva
                                invitación.
                              </p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </div>

                <!-- Contact Information -->
                <div class="contact-section">
                  <v-card variant="flat" color="surface-variant" class="contact-card">
                    <v-card-text class="pa-6 text-center">
                      <v-icon icon="tabler-help-circle" color="primary" size="32" class="mb-3" />
                      <h4 class="text-h6 font-weight-medium text-grey-darken-2 mb-2">¿Necesitas ayuda?</h4>
                      <p class="text-body-2 text-grey-darken-1 mb-4">
                        Si tienes alguna pregunta o necesitas hacer cambios, no dudes en contactar al organizador.
                      </p>
                      <v-chip color="primary" variant="tonal" size="small">
                        <v-icon icon="tabler-email" start size="16" />
                        Soporte disponible
                      </v-chip>
                    </v-card-text>
                  </v-card>
                </div>

              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </div>
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

.loading-card {
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

.response-status-card {
  background: rgba(255, 255, 255, 98%) !important;
}

.response-header {
  position: relative;
  overflow: hidden;
}

.accepted-header {
  background: linear-gradient(135deg, #059669 0%, #10b981 50%, #34d399 100%);
}

.rejected-header {
  background: linear-gradient(135deg, #b91c1c 0%, #dc2626 50%, #f87171 100%);
}

.response-header::before {
  position: absolute;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
  content: "";
  inset: 0;
}

.header-content {
  position: relative;
  z-index: 1;
}

.status-icon {
  animation: float 3s ease-in-out infinite;
}

.status-avatar {
  box-shadow: 0 12px 32px rgba(0, 0, 0, 20%) !important;
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

.status-card {
  border-radius: 16px !important;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 10%) !important;
}

.event-info-card,
.meeting-card,
.rescheduling-card {
  border: 2px solid #e2e8f0 !important;
  border-radius: 16px !important;
  transition: all 0.3s ease;
}

.event-info-card:hover,
.meeting-card:hover,
.rescheduling-card:hover {
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
  text-transform: none !important;
}

.brand-button:hover {
  box-shadow: 0 8px 24px rgba(37, 99, 235, 30%) !important;
  transform: translateY(-1px);
}

.meeting-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
  gap: 16px;
}

.rescheduling-steps {
  position: relative;
}

.rescheduling-steps .d-flex:not(:last-child)::after {
  position: absolute;
  border-radius: 1px;
  background: #dc2626;
  block-size: 24px;
  content: "";
  inline-size: 2px;
  inset-block-start: 32px;
  inset-inline-start: 12px;
  opacity: 0.3;
}

.contact-card {
  border: 2px solid rgba(37, 99, 235, 10%);
  border-radius: 16px !important;
  background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
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

  .meeting-actions {
    flex-direction: column;
  }

  .brand-button {
    inline-size: 100%;
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
