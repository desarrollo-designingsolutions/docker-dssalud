<script setup lang="ts">
import ModalImportCsv from './ModalImportCsv.vue';

  

//ModalImportCsv
const refModalImportCsv = ref()
const openModalImportCsv = () => {
  refModalImportCsv.value.openModal()
}
</script>

<template>
  <div>
  <VCard  >
    <VCardText>
      
      <VBtn color="success" @click="openModalImportCsv()">cargar</VBtn>
    <ModalImportCsv ref="refModalImportCsv" :maxFileSizeMB="200" />

    </VCardText>
  </VCard>
  </div>
</template>
