<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();

//ModalErrorsGlosas
const refModalErrorsGlosas = ref()
const refModalQuestion = ref()

const openModalErrorsGlosas = (data: any) => {
  refModalErrorsGlosas.value.openModal(data, authenticationStore.user.id)
}

const channels = reactive({
  name: `filing.${authenticationStore.user.id}`,
})
const progress = ref<number>(0)

// Función para iniciar y manejar el canal dinámicamente
const startEchoChannel = () => {
  const channel = window.Echo.channel(channels.name);
  channel.listen('ProgressCircular', (event: any) => {
        progress.value = Number(event.progress);

  });
};

onMounted(() => {
  startEchoChannel()
})


</script>

<template>
  <div>
    <LoadingBase ref="refLoading" :progress="progress"   />



    
  </div>
</template>
