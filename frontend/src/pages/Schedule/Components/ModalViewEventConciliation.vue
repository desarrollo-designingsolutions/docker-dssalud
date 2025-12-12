<script setup lang="ts">
import { ref } from 'vue';

import { useToast } from '@/composables/useToast';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const { toast } = useToast()
const authenticationStore = useAuthenticationStore();

const props = defineProps({
  showBtn: {
    type: Boolean,
    default: true
  }, 
});

const emits = defineEmits(["modify", "close", "delete","reschedule"]);

const titleModal = ref<string>("Detalles del evento")
const isDialogVisible = ref<boolean>(false)
const isLoading = ref<boolean>(false)

// Datos del Evento - mantén tu estructura original
const eventData = ref({
  id: null as string | null,
  title: null as string | null,
  start_date: null as string | null,
  start_hour: null as string | null,
  end_date: null as string | null,
  end_hour: null as string | null,
  description: null as string | null,
  response_status: null as string | null,
  link: null as string | null,
  user: {
    id: null as string | null,
    full_name: null as string | null,
    photo: null as string | null,
    role: { description: null as string | null, }
  },
  third: {
    id: null as string | null,
    nit: null as string | null,
    name: null as string | null,
  },
  reconciliation_group: {
    id: null as string | null,
    name: null as string | null,
  },
  guests: [] as Array<{
    email: string;
  }>,
})

const clearData = () => {
  eventData.value = {
     id: null,
  title: null,
  start_date: null,
  start_hour: null,
  end_date: null,
  end_hour: null,
  description: null,
  response_status: null,
  link: null,
  user: {
    id: null,
    full_name: null,
    photo: null,
    role: { description: null, }
  },
  third: {
    id: null,
    nit: null,
    name: null,
  },
  reconciliation_group: {
    id: null,
    name: null,
  },
  guests: [],
  }
}

// Tus métodos originales - mantén exactamente como los tienes
const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
  if (!isDialogVisible.value) {
    emits('close');
  }
};

const openModal = async (event_id: any) => {
  isDialogVisible.value = true;

  clearData();

  await getData(event_id);
};

const getData = async (event_id: any) => {
  try {
    isLoading.value = true;
    const { data, response } = await useAxios(`/schedule/conciliation/show/${event_id}`).get({
      params: {
        company_id: authenticationStore.company.id
      }
    });

    if (response.status === 200 && data) {
      eventData.value = data.schedule
    }
  } catch (error) {
    toast('Error al cargar datos del evento', '', 'warning');
  } finally {
    isLoading.value = false;
  }
};

defineExpose({ openModal });

const handleModify = async () => {
  handleDialogVisible();
  emits("modify", eventData.value.id);
};

const handleReschedule = async () => {
  handleDialogVisible();
  emits("reschedule", eventData.value.id);
};

//ModalQuestion
const refModalQuestion = ref()

const openModalQuestion = () => {
  refModalQuestion.value.openModal()
}

const handleDelete = async () => {
    isLoading.value = true;

  try {
    let url = '/schedule/conciliation/delete/' + eventData.value.id;

    const { data, response } = await useAxios(url).delete();

    if (response.status == 200 && data) {
      handleDialogVisible();
      emits("delete", eventData.value.id);
    }
  } catch (error) {
    toast('Error al eliminar el evento', '', 'warning');
  } finally {
    isLoading.value = false;
  }


};
</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" max-width="1000" persistent class="user-details-dialog">
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" :disabled="isLoading">

        <!-- Toolbar -->
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
          </VToolbar>
        </div>

        <VCardText class="pa-6">
          <VRow>
            <!-- Columna izquierda - Información detallada -->
            <VCol cols="12" lg="7">
              <VCard variant="outlined" class="info-card h-100" elevation="2">
                <VCardTitle class="bg-grey-lighten-4 d-flex align-center">
                  <VIcon color="primary" class="me-2">tabler-calendar-event</VIcon>
                  Información
                </VCardTitle>
                <VCardText class="pa-4">
                  <VList class="transparent">
                    <VListItem class="px-0 py-2">
                      <template #prepend>
                        <VIcon color="primary">tabler-presentation-analytics</VIcon>
                      </template>
                      <VListItemTitle class="text-body-2 text-grey-darken-1">Titulo</VListItemTitle>
                      <VListItemSubtitle class="text-body-1 font-weight-medium text-wrap">
                        {{ eventData.title }}
                      </VListItemSubtitle>
                    </VListItem>

                    <VDivider class="my-2"></VDivider>

                    <VListItem class="px-0 py-2">
                      <template #prepend>
                        <VIcon color="primary">tabler-user</VIcon>
                      </template>
                      <VListItemTitle class="text-body-2 text-grey-darken-1">Usuario Asignado</VListItemTitle>
                      <VListItemSubtitle class="text-body-1 font-weight-medium text-wrap">
                        {{ eventData.user.full_name }}
                      </VListItemSubtitle>
                    </VListItem>
                    <VListItem class="px-0 py-2">
                      <template #prepend>
                        <VIcon color="primary">tabler-user</VIcon>
                      </template>
                      <VListItemTitle class="text-body-2 text-grey-darken-1">Tercero</VListItemTitle>
                      <VListItemSubtitle class="text-body-1 font-weight-medium text-wrap">
                        {{ eventData.third.nit }} - {{ eventData.third.name }}
                      </VListItemSubtitle>
                    </VListItem>
                    <VListItem class="px-0 py-2">
                      <template #prepend>
                        <VIcon color="primary">tabler-user</VIcon>
                      </template>
                      <VListItemTitle class="text-body-2 text-grey-darken-1">Grupo de conciliación</VListItemTitle>
                      <VListItemSubtitle class="text-body-1 font-weight-medium text-wrap">
                        {{ eventData.reconciliation_group.name }}
                      </VListItemSubtitle>
                    </VListItem>

                    <VDivider class="my-2"></VDivider>

                    <VListItem class="px-0 py-2">
                      <template #prepend>
                        <VIcon color="primary">tabler-clock-hour-1</VIcon>
                      </template>
                      <VListItemTitle class="text-body-2 text-grey-darken-1 mb-2">
                        Fecha inicio
                      </VListItemTitle>
                      <div class="d-flex flex-wrap ga-2">
                        {{ eventData.start_date }} {{ eventData.start_hour }}
                      </div>
                    </VListItem>

                    <VListItem class="px-0 py-2">
                      <template #prepend>
                        <VIcon color="primary">tabler-clock-hour-10</VIcon>
                      </template>
                      <VListItemTitle class="text-body-2 text-grey-darken-1 mb-2">
                        Fecha fin
                      </VListItemTitle>
                      <div class="d-flex flex-wrap ga-2">
                        {{ eventData.end_date }} {{ eventData.end_hour }}
                      </div>
                    </VListItem>

                    <VDivider class="my-2"></VDivider>

                    <VListItem class="px-0 py-2">
                      <template #prepend>
                        <VIcon color="primary">tabler-link</VIcon>
                      </template>
                      <VListItemTitle class="text-body-2 text-grey-darken-1">Link</VListItemTitle>
                      <VListItemSubtitle class="text-body-1 font-weight-medium" >
                       <a :href="eventData.link" target="_blank">{{ eventData.link }}</a>
                      </VListItemSubtitle>
                    </VListItem>
                    <VListItem class="px-0 py-2">
                      <template #prepend>
                        <VIcon color="primary">tabler-file-description</VIcon>
                      </template>
                      <VListItemTitle class="text-body-2 text-grey-darken-1">Descripción</VListItemTitle>
                      <VListItemSubtitle class="text-body-1 font-weight-medium">
                        {{ eventData.description }}
                      </VListItemSubtitle>
                    </VListItem>
                  </VList>

                </VCardText>
              </VCard>
            </VCol>

            <!-- Columna derecha - Equipo a cargo -->
            <VCol cols="12" lg="5">
              <VCard variant="outlined" class="team-card h-100" elevation="2">
                <VCardTitle class="bg-grey-lighten-4 d-flex align-center justify-space-between">
                  <div class="d-flex align-center">
                    <VIcon color="primary" class="me-2">tabler-users-group</VIcon>
                    Invitados
                  </div>
                  <VChip color="primary" variant="tonal" size="small" class="font-weight-bold">
                    {{ eventData.guests.length }}
                  </VChip>
                </VCardTitle>

                <VCardText class="pa-4 team-content">
                  <div v-if="eventData.guests.length > 0" class="team-scroll-container">
                    <div class="team-grid">
                      <VCard v-for="(el, index) in eventData.guests" :key="index" variant="outlined"
                        class="team-member-card" hover>
                        <VCardText class="pa-4">
                          <div class="d-flex align-center mb-3">
                            <div class="flex-grow-1">
                              <div class="text-subtitle-1 font-weight-bold mb-1">
                                {{ el }}
                              </div>
                            </div>
                          </div>
                          <div class="d-flex flex-wrap ga-1">
                          </div>
                        </VCardText>
                      </VCard>
                    </div>
                  </div>
                  <div v-else class="text-center text-grey-darken-2 py-6">
                    No hay invitados asignados a este evento.
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VCardText>

        <!-- Footer con botones mejorados -->
        <VDivider></VDivider>

        <template v-if="props.showBtn"> 
          <VCardText class="d-flex justify-end gap-3 flex-wrap" v-if="eventData.response_status === 'SCHEDULE_RESPONSE_STATUS_001'">
            <VSpacer />
            <VBtn color="error" :disabled="isLoading" @click="openModalQuestion" class="me-3">
              Eliminar
            </VBtn>
            <VBtn color="info" :loading="isLoading" :disabled="isLoading" @click="handleModify">
              Modificar
            </VBtn>
          </VCardText>
  
          <VCardText  v-if="eventData.response_status === 'SCHEDULE_RESPONSE_STATUS_002'">
            <VAlert  border="bottom" color="success" variant="tonal">
              Esta conciliación ya ha sido aceptada y no se puede modificar.
            </VAlert>
          </VCardText>
  
          <VCardText class="d-flex gap-3 flex-wrap " v-if="eventData.response_status === 'SCHEDULE_RESPONSE_STATUS_003'">
            <VAlert  border="bottom" color="error" variant="tonal">
              Esta conciliación ha sido rechazada y no se puede modificar.
            </VAlert>
            
            <VBtn color="primary" :loading="isLoading" :disabled="isLoading" @click="handleReschedule">
              Reagendar
            </VBtn>
          </VCardText>
        </template>
      </VCard>
    </VDialog>

    <ModalQuestion ref="refModalQuestion" @success="handleDelete" />
  </div>

</template>

<style scoped>
.user-details-dialog {
  backdrop-filter: blur(8px);
}

.header-gradient {
  background: linear-gradient(135deg, #e53935 0%, #c62828 50%, #7f0000 100%);
  color: white;

  /* position: relative; */
  padding-block: 32px;
  padding-inline: 24px;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 24px;
}

.user-avatar-header {
  border: 4px solid rgba(255, 255, 255, 20%);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 20%);
  transition: transform 0.3s ease;
}

.user-avatar-header:hover {
  transform: scale(1.05);
}

.header-text h2 {
  text-shadow: 0 2px 4px rgba(0, 0, 0, 20%);
}

/* ESTILOS PARA EL SCROLL DEL EQUIPO */
.team-content {
  display: flex;
  flex-direction: column;
  block-size: 100%;
}

.team-scroll-container {
  flex: 1;
  max-block-size: 500px;
  overflow-y: auto;

  /* Altura máxima antes de mostrar scroll */
  padding-inline-end: 8px;

  /* Espacio para el scrollbar */
}

/* Personalizar scrollbar */
.team-scroll-container::-webkit-scrollbar {
  inline-size: 8px;
}

.team-scroll-container::-webkit-scrollbar-track {
  border-radius: 4px;
  background: #f1f1f1;
}

.team-scroll-container::-webkit-scrollbar-thumb {
  border-radius: 4px;
  background: #c1c1c1;
  transition: background 0.3s ease;
}

.team-scroll-container::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Para Firefox */
.team-scroll-container {
  scrollbar-color: #c1c1c1 #f1f1f1;
  scrollbar-width: thin;
}

.team-grid {
  display: grid;
  gap: 16px;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  padding-block-end: 8px;

  /* Espacio al final del scroll */
}

.team-member-card {
  border-radius: 12px !important;
  transition: all 0.3s ease;

  /* background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%); */
}

.team-member-card:hover {
  box-shadow: 0 12px 24px rgba(0, 0, 0, 15%) !important;
  transform: translateY(-4px);
}

.member-avatar {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 10%);
  transition: all 0.3s ease;
}

.member-avatar:hover {
  box-shadow: 0 6px 16px rgba(0, 0, 0, 20%);
  transform: scale(1.1);
}

.info-card,
.team-card {
  border-radius: 12px !important;
  transition: all 0.3s ease;
}

.info-card:hover,
.team-card:hover {
  box-shadow: 0 8px 25px rgba(0, 0, 0, 10%) !important;
  transform: translateY(-2px);
}

.department-chip {
  transition: all 0.2s ease;
}

.department-chip:hover {
  transform: scale(1.05);
}

.supervisor-section {
  padding: 16px;

  /* background: linear-gradient(145deg, #f8f9fa 0%, #e9ecef 100%); */
  border-radius: 12px;

  /* border: 1px solid #e0e0e0; */
}

.supervisor-select :deep(.v-field) {
  border-radius: 8px;
}

.team-grid {
  display: grid;
  gap: 16px;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

.team-member-card {
  border-radius: 12px !important;
  transition: all 0.3s ease;

  /* background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%); */
}

.team-member-card:hover {
  box-shadow: 0 12px 24px rgba(0, 0, 0, 15%) !important;
  transform: translateY(-4px);
}

.member-avatar {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 10%);
  transition: all 0.3s ease;
}

.member-avatar:hover {
  box-shadow: 0 6px 16px rgba(0, 0, 0, 20%);
  transform: scale(1.1);
}

.v-card-actions {
  border-block-start: 1px solid #e0e0e0;
}

/* Animaciones adicionales */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive adjustments */
@media (max-width: 960px) {
  .header-content {
    flex-direction: column;
    gap: 16px;
    text-align: center;
  }

  .team-grid {
    grid-template-columns: 1fr;
  }
}
 
</style>
