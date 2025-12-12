<script setup lang="ts">
import ModalErrorsAssignments from "@/pages/InvoiceAudit/Components/ModalErrorsAssignments.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();

//ModalErrorsAssignments
const refModalErrorsAssignments = ref()
const refModalQuestion = ref()

const openModalErrorsAssignments = (data: any) => {
  refModalErrorsAssignments.value.openModal(data, authenticationStore.user.id)
}

const channels = reactive({
  assignmentModalErrors: `assignmentModalErrors.${authenticationStore.user.id}`,
})

// Función para iniciar y manejar el canal dinámicamente
const startEchoChannel = () => {
  const channelAssignmentModalErrors = window.Echo.channel(channels.assignmentModalErrors);
  channelAssignmentModalErrors.listen('ModalError', (event: any) => {
    if (event.errors != null) {
      openModalErrorsAssignments(event.errors);
    }  else {
      if (refModalQuestion.value) {
        refModalQuestion.value.componentData.isDialogVisible = true;
        refModalQuestion.value.componentData.showBtnCancel = false;
        refModalQuestion.value.componentData.btnSuccessText = 'Ok';
        refModalQuestion.value.componentData.title = 'Asignaciones cargadas exitosamente.';
        refModalQuestion.value.componentData.principalIcon = 'tabler-check';
      }
    }
  });
};

onMounted(() => {
  startEchoChannel()
})


</script>

<template>
  <div>
    <ModalErrorsAssignments ref="refModalErrorsAssignments" />

    <ModalQuestion ref="refModalQuestion" />
  </div>
</template>
