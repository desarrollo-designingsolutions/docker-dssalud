<script setup lang="ts">
interface Props {
  id: string
  status: string
  backgroundColor: string
  description: string
}

const props = defineProps<Props>()

const rip = reactive<Props>({
  id: "",
  status: "",
  backgroundColor: "",
  description: "",
});

const echoChannel = () => {
  window.Echo.channel(`rip.${props.id}`)
    .listen('.RipStatusUpdated', (event: any) => {

      rip.id = event.id
      rip.status = event.status
      rip.backgroundColor = event.status_backgroundColor
      rip.description = event.status_description
    });
}


// Recuperar el estado y el tiempo cuando el componente se monta
onMounted(async () => {
  rip.id = props.id
  rip.status = props.status
  rip.backgroundColor = props.backgroundColor
  rip.description = props.description
  await echoChannel();
});


</script>

<template>
  <div>
    <VChip :color="rip.backgroundColor">
      <VProgressCircular indeterminate color="secondary" size="20" class="mr-2"
        v-if="rip.status === StatusRipsEnum.IN_PROCESS" />
      <span>{{ rip.description }}</span>
    </VChip>
  </div>
</template>
