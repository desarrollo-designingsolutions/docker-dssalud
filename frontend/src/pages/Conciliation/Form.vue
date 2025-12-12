<script lang="ts" setup>
import { useToast } from '@/composables/useToast';
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import ModalChangeStatus from '@/pages/Conciliation/Components/ModalChangeStatus.vue';
import ModalGenerateConciliationReport from '@/pages/Conciliation/Components/ModalGenerateConciliationReport.vue';
import ModalShowFiles from '@/pages/Conciliation/Components/ModalShowFiles.vue';
import ModalUploadCsv from '@/pages/Conciliation/Components/ModalUploadCsv.vue';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

definePage({
  path: "conciliation-form/:action/:id?",
  name: "Conciliation-Form",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "conciliation.list",
  },
});

const authenticationStore = useAuthenticationStore();
const { toast } = useToast()
const errorsBack = ref<IErrorsBack>({}); 
const route = useRoute() 
const loading = reactive({
  form: false,
  excel: false, 
})

const form = ref({
  id: null as string | null,
  company_id: null as string | null,
  name: null as string | null,
  third_id: null as string | null,
  third_name: null as string | null,
  status_description: null as string | null,
})

const clearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
}

const fetchDataForm = async () => {

  form.value.id = route.params.id || null

  const url = `/conciliation/${form.value.id}/show`

  loading.form = true
  const { response, data } = await useApi(url).get();
  loading.form = false

  if (response.value?.ok && data.value) {
 

    //formulario 
    if (data.value.form) {
      form.value = data.value.form
    }
  }
} 

onMounted(async () => {
  clearForm()
  await fetchDataForm()
})

// Computed que verifica si al menos uno de los valores es true
const isLoading = computed(() => {
  return Object.values(loading).some(value => value);
});


 
const company_id = {
  company_id: authenticationStore.company.id,
  reconciliation_group_id: route.params.id,
}

//TABLE
const refTableFull = ref()

const optionsTable = {
  url: "/conciliation/paginateConciliationInvoices",
  paramsGlobal: company_id,
  headers: [
    { key: 'invoice_number', title: 'Número de factura' },
    { key: 'total_value', title: 'Total' },
    { key: 'origin', title: 'Origen' }, 
    { key: 'modality', title: 'Modalidad' }, 
    { key: 'contract_number', title: 'Número de contacto' }, 
    { key: 'status_description', title: 'Estado',sortable:false },
    { key: 'sum_accepted_value_ips', title: 'Valor aceptado ips' },
    { key: 'sum_accepted_value_eps', title: 'Valor aceptado eps' },
    { key: 'sum_eps_ratified_value', title: 'Valor ratificado' },

  ],
    actions: {
    changeStatus: {
      show: false,
    },
    delete: {
      show: false,
    },
    edit: {
      show: false
    },
    view: {
      show: false
    },
  }
}

//FILTER
const optionsFilterNew = ref({
  dialog: {
    width: 500,
    inputs: [],
  },
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

const tableLoading = ref(false);

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(null, false, true); // Forzamos la búsqueda
  }
};

const showUploadModal = ref(false)

const openUploadModal = () => {
  showUploadModal.value = true
}


//ModalUploadCsv
const refModalUploadCsv = ref()
const openModalUploadCsv = () => {
  refModalUploadCsv.value.openModal(form.value.id)
}

//ModalShowFiles
const refModalShowFiles = ref()

const openModalShowFiles = () => {
  refModalShowFiles.value.openModal(form.value.id, "ReconciliationGroup")
}

 
const downloadExcel = async () => {
  loading.excel = true;

  const { data, response } = await useAxios("/conciliation/excelExportConciliationInvoices").get({
    params: {
      ...route.query,
      company_id: authenticationStore.company.id,
      user_id: authenticationStore.user.id,
      reconciliation_group_id: form.value.id,
    }
  })

  loading.excel = false;

  if (response.status == 200 && data) {
    downloadExcelBase64(data.excel, "Lista de grupos de conciliación")
  }
}


//ModalChangeStatus
const refModalChangeStatus = ref()

const openModalChangeStatus = () => {
  refModalChangeStatus.value.openModal(form.value.id)
}

//ModalGenerateConciliationReport
const refModalGenerateConciliationReport = ref()

const openModalGenerateConciliationReport = () => {
  refModalGenerateConciliationReport.value.openModal(form.value.id)
}

const changeData = (data:any) => {  
  form.value.status_description =  data.status_description
}


</script>


<template>
  <div>
    <VCard :disabled="loading.form" :loading="loading.form">
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Información
        </span>

         <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn :loading="loading.excel" :disabled="loading.excel" size="38" color="primary" icon
            @click="downloadExcel()">
            <VIcon icon="tabler-file-spreadsheet"></VIcon>
            <VTooltip location="top" transition="scale-transition" activator="parent" text="Descargar Excel">
            </VTooltip>
          </VBtn>
          <VBtn v-if="form.id" color="primary" append-icon="tabler-chevron-down">
            Más Acciones
            <VMenu activator="parent">
              <VList> 
                <VListItem @click="openUploadModal">
                  Añadir Soportes
                </VListItem> 
                <VListItem @click="openModalShowFiles()">Ver soportes</VListItem>
                <VListItem @click="openModalUploadCsv">
                  Subir CSV
                </VListItem> 
                <VListItem @click="openModalChangeStatus">
                  Cambiar estado conciliación
                </VListItem> 
                <VListItem @click="openModalGenerateConciliationReport">
                  Generar acta conciliación
                </VListItem> 
              </VList>
            </VMenu>
          </VBtn>
        </div>
        
      </VCardTitle>
      <VCardText>
 
          <VRow>
            <VCol cols="12" md="6">
              <div class="d-flex flex-column">
                <span>Nombre:</span>
                <span>{{ form.name }}</span>  
              </div>
            </VCol>
            <VCol cols="12" md="6">
              <div class="d-flex flex-column">
                <span>Tercero:</span>
                <span>{{ form.third_name }}</span>  
              </div>
            </VCol>
            <VCol cols="12" md="6">
              <div class="d-flex flex-column">
                <span>Estado:</span>
                <span>{{ form.status_description }}</span>  
              </div>
            </VCol>
          </VRow> 
      </VCardText>


       <VCardText>
        <FilterDialogNew :options-filter="optionsFilterNew" @force-search="refreshTable" :table-loading="tableLoading">
        </FilterDialogNew>
      </VCardText>

      <VCardText class="mt-2">
        <TableFullNew ref="refTableFull" :options="optionsTable"  
          @update:loading="tableLoading = $event">

             <template #item.status_description="{ item }">
            <div>
              <VChip :color="item.status_backgroundColor">{{ item.status_description }}</VChip>
            </div>
          </template>

        </TableFullNew>
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap mt-5">
        <BtnBack :disabled="isLoading" :loading="isLoading" />
        
      </VCardText>
    </VCard>

    <template v-if="form.id">
      <FileUploadModal ref="refFileUploadModal" v-model="showUploadModal" :maxFileSizeMB="200"  :fileable_id="form.id"
          fileable_type="ReconciliationGroup" />
    </template>


    <ModalUploadCsv ref="refModalUploadCsv" :maxFileSizeMB="200" />

    <ModalShowFiles ref="refModalShowFiles" />

    <ModalChangeStatus ref="refModalChangeStatus" @execute="changeData"/>

    <ModalGenerateConciliationReport ref="refModalGenerateConciliationReport" />

  </div>
</template>
