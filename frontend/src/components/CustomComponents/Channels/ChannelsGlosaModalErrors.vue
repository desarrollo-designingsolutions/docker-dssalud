<script setup lang="ts">
import ModalErrorsGlosas from "@/pages/InvoiceAudit/Components/ModalErrorsGlosas.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();

//ModalErrorsGlosas
const refModalErrorsGlosas = ref()
const refModalQuestion = ref()

const openModalErrorsGlosas = (data: any) => {
  refModalErrorsGlosas.value.openModal(data, authenticationStore.user.id)
}

const channels = reactive({
  glosaModalErrors: `glosaModalErrors.${authenticationStore.user.id}`,
})

// Función para iniciar y manejar el canal dinámicamente
const startEchoChannel = () => {
  const channelGlosaModalErrors = window.Echo.channel(channels.glosaModalErrors);
  channelGlosaModalErrors.listen('ModalError', (event: any) => {
    if (event.errors != null) {
      openModalErrorsGlosas(event.errors);
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
    <ModalErrorsGlosas ref="refModalErrorsGlosas" />

    <ModalQuestion ref="refModalQuestion" />
  </div>
</template>
