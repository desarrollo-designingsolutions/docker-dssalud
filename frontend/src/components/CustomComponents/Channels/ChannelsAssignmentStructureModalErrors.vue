<script setup lang="ts">
import ModalErrorsAssignmentStructure from "@/pages/InvoiceAudit/Components/ModalErrorsAssignmentStructure.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();

//ModalErrorsAssignmentStructure
const refModalErrorsAssignmentStructure = ref()
const refModalQuestion = ref()

const openModalErrorsAssignmentStructure = (data: any) => {
  refModalErrorsAssignmentStructure.value.openModal(data, authenticationStore.user.id)
}

const channels = reactive({
  assignmentStructureModalErrors: `assignmentStructureModalErrors.${authenticationStore.user.id}`,
})

// Función para iniciar y manejar el canal dinámicamente
const startEchoChannel = () => {
  const channelAssignmentStructureModalErrors = window.Echo.channel(channels.assignmentStructureModalErrors);
  channelAssignmentStructureModalErrors.listen('ModalError', (event: any) => {

    if (event.errors != null) {
      openModalErrorsAssignmentStructure(event.errors);
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
    <ModalErrorsAssignmentStructure ref="refModalErrorsAssignmentStructure" />

    <ModalQuestion ref="refModalQuestion" />
  </div>
</template>
