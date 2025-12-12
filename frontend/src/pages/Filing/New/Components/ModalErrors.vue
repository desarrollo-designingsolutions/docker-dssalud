<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
const emit = defineEmits(["continue", "cancel"]);
const authenticationStore = useAuthenticationStore();

const titleModal = ref<string>("Errores en la validación")
const isDialogVisible = ref<boolean>(false)
const loading = reactive({
  excel: false,
  getData: false,
})

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const objData = ref()

const openModal = async (element: any) => {
  handleDialogVisible();

  objData.value = element


  init()
};

defineExpose({
  openModal,
})

const has_invoices = ref(false);
const errorMessages = ref([]);
const validationZip = ref<{
  infoValidationZip: boolean
}>({
  infoValidationZip: false
});
const validationTxt = ref<{
  totalSuccessfulInvoices: number
}>({
  totalSuccessfulInvoices: 0
});

const init = async () => {
  errorMessages.value = []
  loading.getData = true
  const { data, response } = await useApi(`/filing/showErrorsValidation`).post({
    id: objData.value.id,
  })
  if (response.value?.ok && data.value) {
    errorMessages.value = data.value.errorMessages
    has_invoices.value = data.value.has_invoices

    data.value.validationTxt ? validationTxt.value = data.value.validationTxt : null
    data.value.validationZip ? validationZip.value = data.value.validationZip : null
  }

  loading.getData = false

}


// headers
const inputsTableFilter = [
  { title: 'Proceso', key: 'type' },
  { title: 'Tipo de Validación', key: 'validacion_type_Y' },
  { title: 'Num Factura', key: 'num_invoice' },
  { title: 'Archivo', key: 'file' },
  { title: 'Columna', key: 'column' },
  { title: 'Fila', key: 'row' },
  { title: 'Validacion', key: 'validacion' },
  { title: 'Dato registrado', key: 'data' },
  { title: 'Descripción error', key: 'error' },
]

const options = ref({ page: 1, itemsPerPage: 10, sortBy: [''], sortDesc: [false] })
const search = ref('')

const downloadExcel = async () => {
  loading.excel = true;
  const { data, response } = await useApi("/filing/excelErrorsValidation").post({
    id: objData.value.id,
  })

  loading.excel = false;

  if (response.value?.ok && data.value) {
    downloadExcelBase64(data.value.excel, "Lista de errores de validación")
  }
}


const submitConfirm = async () => {
  emit('continue')
  handleDialogVisible()
}
const cancel = async () => {
  emit('cancel', {
    validationTxt: errorMessages.value.length > 0,
    has_invoices: has_invoices.value,
  })
  handleDialogVisible()
}

//ModalQuestion
const refModalQuestion = ref()

const openModalQuestion = () => {
  const infoValidationTxt = validationTxt.value

  refModalQuestion.value.componentData.isDialogVisible = true
  refModalQuestion.value.componentData.html = `
      Total de Facturas : ${infoValidationTxt.totalInvoices} <br>
      Facturas a Procesar: ${infoValidationTxt.totalSuccessfulInvoices} <br>
    `
}
</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="loading.getData" :disabled="loading.getData" class="w-100" >
        <!-- Toolbar -->
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
          </VToolbar>
        </div>

        <VCardText>
          <VRow>
            <VCol cols="12" sm="6">
              <AppTextField v-model="search" density="compact" placeholder="Search ..."
                append-inner-icon="tabler-search" single-line hide-details dense outlined clearable />
            </VCol>
            <VCol cols="12" sm="6" class="d-flex justify-end">
              <VBtn :loading="loading.excel" :disabled="loading.excel" @click="downloadExcel">Exportar a Excel</VBtn>
            </VCol>
          </VRow>
        </VCardText>

        <VCardText>
          <VDataTable :search="search" :headers="inputsTableFilter" :items="errorMessages"
            :items-per-page="options.itemsPerPage" :page="options.page" :options="options">

          </VDataTable>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn :loading="loading.getData" color="secondary" variant="tonal" @click="cancel()">
            Cancelar
          </VBtn>

          <template v-if="objData.error_status?.has_n_errors">
            <VBtn v-if="validationTxt.totalSuccessfulInvoices > 0" :disabled="loading.getData"
              :loading="loading.getData" @click="openModalQuestion()" color="primary">
              Continuar
            </VBtn>
          </template>
        </VCardText>
      </VCard>
    </VDialog>

    <ModalQuestion ref="refModalQuestion" @success="submitConfirm" />

  </div>
</template>
