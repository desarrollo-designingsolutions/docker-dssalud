<script setup lang="ts">
import ModalUploadGlosaFileCsv from "@/pages/InvoiceAudit/Components/ModalUploadGlosaFileCsv.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { useRouter } from 'vue-router';

const { toast } = useToast();

definePage({
  path: "invoiceAuditAssignmentList/invoice-audit/:assignment_batch_id/:third_id/:invoice_audit_id",
  name: "InvoiceAuditPatient-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "invoiceAuditAssignmentBatche.list",
  },
});

const authenticationStore = useAuthenticationStore();

const router = useRouter();

const route = useRoute();
const assignment_batch_id = route.params.assignment_batch_id;
const third_id = route.params.third_id;
const invoice_audit_id = route.params.invoice_audit_id;

//TABLE
const refTableFull = ref()

const optionsTable = {
  showSelect: true,
  url: `/invoiceAudit/paginatePatient/${assignment_batch_id}/${third_id}/${invoice_audit_id}`,
  headers: [
    { key: 'identification_number', title: 'Documento' },
    { key: 'full_name', title: 'Nombre' },
    { key: 'gender', title: 'Genero' },
    { key: 'glosas', title: 'Glosas', sortable: false },
    { key: "codes_glosa", title: 'Códigos Glosa', minWidth: 200 },
    { key: 'value_glosa', title: 'Valor Glosado' },
    { key: 'value_approved', title: 'Valor Aprobado' },
    { key: 'total_value', title: 'Valor Total' },
    { key: 'status', title: 'Estado', sortable: false  },
    // { key: 'actions', title: 'Acciones', sortable: false, width: 100 },
  ],
  actions: {
    delete: { url: '/invoiceAudit/delete' }
  },
  paramsGlobal: {
    company_id: authenticationStore.company.id,
    user_id: authenticationStore.user.id,
  }
}


//FILTER
const optionsFilter = ref({
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

const goViewInformationSheet = (data: any = { id: null }) => {

  router.push({ name: "InvoiceAuditInformationSheet-Info", params: { assignment_batch_id: assignment_batch_id, third_id: third_id, invoice_audit_id: invoice_audit_id, patient_id: data.id } })

}

const goViewInvoiceAuditList = () => {
  router.push({ name: "InvoiceAuditInvoiceAudit-List", params: { assignment_batch_id: assignment_batch_id, third_id: third_id } })
}


const { downloadExcel, isLoadingExcel } = useGlosaInportExport()

const downloadExport = async () => {
  downloadExcel({
    third_id: third_id,
    assignment_batch_id: assignment_batch_id,
    invoice_audit_id: invoice_audit_id,
    user_id: authenticationStore.user.id,
    from: "patients",
  })
}

//ModalUploadGlosaFileCsv
const refModalUploadGlosaFileCsv = ref()
const openModalUploadGlosaFileCsv = () => {
  refModalUploadGlosaFileCsv.value.openModal({
    assignment_batch_id: assignment_batch_id,
    third_id: third_id,
    invoice_audit_id: invoice_audit_id,
    user_id: authenticationStore.user.id,
  })
}




// const disbaledBtn = ref(false)
// const channels = reactive({
//   glosa: `glosa.${authenticationStore.user.id}`,
// })

// // Función para iniciar y manejar el canal dinámicamente
// const startEchoChannel = () => {
//   const channelGlosaModalErrors = window.Echo.channel(channels.glosa);
//   channelGlosaModalErrors.listen('ProgressCircular', (event: any) => {
//     const progress = Number(event.progress);

//     if (progress != 100) {
//       disbaledBtn.value = true;
//     }
//     if (progress == 100) {
//       disbaledBtn.value = false;
//     }
//   });
// };

// startEchoChannel()


const tableLoading = ref(false); // Estado de carga de la tabla

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(null, false, true); // Forzamos la búsqueda
  }
};

const isLoadingSuccessFinalizedAudit = ref<boolean>(false)

//ModalQuestion
const refModalQuestion = ref()

const openModalQuestion = () => {
  if (patientsIds.value.length > 0) {
    if (refModalQuestion.value) {
      refModalQuestion.value.componentData.isDialogVisible = true;
      refModalQuestion.value.componentData.btnSuccessText = 'Si';
      refModalQuestion.value.componentData.btnCancelText = 'No';
      refModalQuestion.value.componentData.title = '¿Esta seguro que deseea finalizar la auditoria?';
      refModalQuestion.value.componentData.subTitle = `Se han seleccionado ${patientsIds.value.length} registros. `;
    }
  } else {
    toast("Debe seleccionar almenos un registro", "", "info")
  }
}

const showBtnsView = ref(true);
const patientsIds = ref<Array<string>>([]);
const refCountAllData = ref()

const successFinalizedAudit = async () => {
  isLoadingSuccessFinalizedAudit.value = true;
  const { data, response } = await useAxios(`/invoiceAudit/successFinalizedAudit`).post({
    patients_ids: patientsIds.value,
    invoice_audit_id: invoice_audit_id,
    assignment_batch_id: assignment_batch_id,
    company_id: authenticationStore.company.id,
    user_id: authenticationStore.user.id,
  })
  isLoadingSuccessFinalizedAudit.value = false;

  if (response.status == 200 && data && data.code == 200) {
    showBtnsView.value = false
    refCountAllData.value.getData()
  }
}

const isLoading = computed(() => {

let loading = [
isLoadingExcel.value,
isLoadingSuccessFinalizedAudit.value
]

return Object.values(loading).some(value => value);
});

</script>

<template>
  <div>

    <CountAllData ref="refCountAllData" :assignment_batch_id="assignment_batch_id" :third_id="third_id" />

    <VRow>
      <VCol>
        <VCard>
          <VCardTitle class="d-flex justify-space-between">
            <span>
              Usuarios
            </span>


            <div class="d-flex justify-end gap-3 flex-wrap ">

              <!-- <ProgressCircularChannel :channel="'glosa.' + authenticationStore.user.id"
                tooltipText="Cargando glosas" /> -->

              <VBtn @click="goViewInvoiceAuditList">
                Regresar
              </VBtn> 

              <VBtn color="primary" append-icon="tabler-chevron-down" :loading="isLoading">
                Más Acciones
                <VMenu activator="parent" :loading="isLoadingExcel">
                  <VList>
                    <VListItem v-if="showBtnsView" @click="openModalUploadGlosaFileCsv()">
                      <template #prepend>
                        <VIcon start icon="tabler-file-upload" />
                      </template>
                      <span>Importar</span>
                    </VListItem>
                    <VListItem v-if="showBtnsView" @click="downloadExport()">
                      <template #prepend>
                        <VIcon start icon="tabler-file-download" />
                      </template>
                      <span>Exportar</span>
                    </VListItem>
                    <VListItem v-if="showBtnsView" @click="openModalQuestion()">
                      <template #prepend>
                        <VIcon start icon="tabler-file-download" />
                      </template>
                      <span>Finalizar auditoria</span>
                    </VListItem>

                  </VList>
                </VMenu>
              </VBtn>
            </div>
          </VCardTitle>

          <VCardText>
            <FilterDialogNew :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
            </FilterDialogNew>
          </VCardText>

          <VCardText class="mt-2">
            <TableFullNew v-model:selected="patientsIds" ref="refTableFull" :options="optionsTable"
              @update:loading="tableLoading = $event">

              <template #item.identification_number="{ item }">
                <div style="cursor: pointer;" @click="goViewInformationSheet({ id: item.id })">
                  {{ item.identification_number }}
                </div>
              </template>

              <template #item.full_name="{ item }">
                <div style="cursor: pointer;" @click="goViewInformationSheet({ id: item.id })">
                  {{ item.full_name }}
                </div>
              </template>

              <template #item.gender="{ item }">
                <div style="cursor: pointer;" @click="goViewInformationSheet({ id: item.id })">
                  {{ item.gender }}
                </div>
              </template>

              <template #item.glosas="{ item }">
                <div style="cursor: pointer;" @click="goViewInformationSheet({ id: item.id })">
                  {{ item.glosas }}
                </div>
              </template>

              <template #item.value_glosa="{ item }">
                <div style="cursor: pointer;" @click="goViewInformationSheet({ id: item.id })">
                  {{ item.value_glosa }}
                </div>
              </template>

              <template #item.value_approved="{ item }">
                <div style="cursor: pointer;" @click="goViewInformationSheet({ id: item.id })">
                  {{ item.value_approved }}
                </div>
              </template>

              <template #item.total_value="{ item }">
                <div style="cursor: pointer;" @click="goViewInformationSheet({ id: item.id })">
                  {{ item.total_value }}
                </div>
              </template>

              <template #item.status="{ item }">
                <div style="cursor: pointer;" @click="goViewInformationSheet({ id: item.id })">
                  {{ getInvoiceAuditStatus(item.status).title }}
                </div>
              </template>


            </TableFullNew>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <ModalUploadGlosaFileCsv ref="refModalUploadGlosaFileCsv" />

    <ModalQuestion ref="refModalQuestion" @success="successFinalizedAudit" />




  </div>

</template>
