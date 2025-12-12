<script setup lang="ts">
import ModalErrorsGlosaStructure from "@/pages/InvoiceAudit/Components/ModalErrorsGlosaStructure.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();

//ModalErrorsGlosaStructure
const refModalErrorsGlosaStructure = ref()
const refModalQuestion = ref()

const openModalErrorsGlosaStructure = (data: any) => {
  refModalErrorsGlosaStructure.value.openModal(data, authenticationStore.user.id)
}

const channels = reactive({
  glosaStructureModalErrors: `glosaStructureModalErrors.${authenticationStore.user.id}`,
})

// Función para iniciar y manejar el canal dinámicamente
const startEchoChannel = () => {
  const channelGlosaStructureModalErrors = window.Echo.channel(channels.glosaStructureModalErrors);
  channelGlosaStructureModalErrors.listen('ModalError', (event: any) => {

    if (event.errors != null) {
      openModalErrorsGlosaStructure(event.errors);
    }  else {
      if (refModalQuestion.value) {
        refModalQuestion.value.componentData.isDialogVisible = true;
        refModalQuestion.value.componentData.showBtnCancel = false;
        refModalQuestion.value.componentData.btnSuccessText = 'Ok';
        refModalQuestion.value.componentData.title = 'Glosas cargadas exitosamente.';
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
    <ModalErrorsGlosaStructure ref="refModalErrorsGlosaStructure" />

    <ModalQuestion ref="refModalQuestion" />
  </div>
</template>
