<script setup lang="ts">
import ModalUploadGlosaFileCsv from "@/pages/InvoiceAudit/Components/ModalUploadGlosaFileCsv.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { useRouter } from 'vue-router';

const { toast } = useToast();

definePage({
  path: "invoiceAuditAssignmentList/:assignment_batch_id",
  name: "InvoiceAuditAssignment-List",
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

//TABLE
const refTableFull = ref()

const optionsTable = {
  showSelect: true,
  url: "/invoiceAudit/paginateThirds/" + assignment_batch_id,
  headers: [
    { key: 'nit', title: 'Nit' },
    { key: 'name', title: 'Razón Social' },
    { key: 'count_invoice_total', title: 'Cant Fact Totales' },
    { key: 'count_invoice_pending', title: 'Cant Fact Pendientes' },
    { key: 'count_invoice_finish', title: 'Cant Fact Finalizadas' },
    { key: 'values', title: 'Valores' },
    { key: 'status', title: 'Estado', sortable: false },
    // { key: 'actions', title: 'Acciones', sortable: false, width: 100 },
  ],
  actions: {
    delete: { url: '/invoiceAudit/delete' }
  },
  paramsGlobal: {
    user_id: authenticationStore.user.id,
    assignment_batch_id: assignment_batch_id,
    company_id: authenticationStore.company.id,
  }
}


//FILTER
const optionsFilter = ref({
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

const goViewInvoiceAudit = (data: any = { id: null }) => {

  router.push({ name: "InvoiceAuditInvoiceAudit-List", params: { assignment_batch_id: assignment_batch_id, third_id: data.id } })

}

const goViewAssignmentBatchesList = () => {

  router.push({ name: "InvoiceAuditAssignmentBatche-List" })

}

const { downloadExcel, isLoadingExcel } = useGlosaInportExport()

const downloadExport = async () => {
  downloadExcel({
    assignment_batch_id: assignment_batch_id,
    user_id: authenticationStore.user.id,
    from: "thirds",
  })
}

//ModalUploadGlosaFileCsv
const refModalUploadGlosaFileCsv = ref()
const openModalUploadGlosaFileCsv = () => {
  refModalUploadGlosaFileCsv.value.openModal({
    assignment_batch_id: assignment_batch_id,
    user_id: authenticationStore.user.id,
  })
}


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
  if (thirdsIds.value.length > 0) {
    if (refModalQuestion.value) {
      refModalQuestion.value.componentData.isDialogVisible = true;
      refModalQuestion.value.componentData.btnSuccessText = 'Si';
      refModalQuestion.value.componentData.btnCancelText = 'No';
      refModalQuestion.value.componentData.title = '¿Esta seguro que deseea finalizar la auditoria?';
      refModalQuestion.value.componentData.subTitle = `Se han seleccionado ${thirdsIds.value.length} registros. `;
    }
  } else {
    toast("Debe seleccionar almenos un registro", "", "info")
  }
}

const showBtnsView = ref(true);
const thirdsIds = ref<Array<string>>([]);
  const refCountAllData = ref()

const successFinalizedAudit = async () => {
  isLoadingSuccessFinalizedAudit.value = true;
  const { data, response } = await useAxios(`/invoiceAudit/successFinalizedAudit`).post({
    thirds_ids: thirdsIds.value,
    assignment_batch_id: assignment_batch_id,
    company_id: authenticationStore.company.id,
    user_id: authenticationStore.user.id,
  })
  isLoadingSuccessFinalizedAudit.value = false;

  if (response.status == 200 && data && data.code == 200) {
    showBtnsView.value = false
    refCountAllData.value.getData()
    refreshTable()
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
    <CountAllData ref="refCountAllData" :assignment_batch_id="assignment_batch_id" :user_id="authenticationStore.user.id" />
    <VRow>
      <VCol>
        <VCard>
          <VCardTitle class="d-flex justify-space-between">
            <span>
              Prestadores
            </span>

            <div class="d-flex justify-end gap-3 flex-wrap ">
              <ProgressCircularChannel :channel="'glosa.' + authenticationStore.user.id"
                tooltipText="Cargando glosas" />


              <VBtn @click="goViewAssignmentBatchesList">
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
            <TableFullNew v-model:selected="thirdsIds" ref="refTableFull" :options="optionsTable"
              @update:loading="tableLoading = $event">

              <template #item.nit="{ item }">
                <div style="cursor: pointer;" @click="goViewInvoiceAudit({ id: item.id })">
                  {{ item.nit }}
                </div>
              </template>

              <template #item.name="{ item }">
                <div style="cursor: pointer;" @click="goViewInvoiceAudit({ id: item.id })">
                  {{ item.name }}
                </div>
              </template>

              <template #item.count_invoice_assignment="{ item }">
                <div style="cursor: pointer;" @click="goViewInvoiceAudit({ id: item.id })">
                  {{ item.count_invoice_assignment }}
                </div>
              </template>

              <template #item.count_invoice_pending="{ item }">
                <div style="cursor: pointer;" @click="goViewInvoiceAudit({ id: item.id })">
                  {{ item.count_invoice_pending }}
                </div>
              </template>

              <template #item.count_invoice_finish="{ item }">
                <div style="cursor: pointer;" @click="goViewInvoiceAudit({ id: item.id })">
                  {{ item.count_invoice_finish }}
                </div>
              </template>

              <template #item.values="{ item }">
                <div style="cursor: pointer;" @click="goViewInvoiceAudit({ id: item.id })">
                  {{ item.values }}
                </div>
              </template>

              <template #item.status="{ item }">
                <div style="cursor: pointer;" @click="goViewInvoiceAudit({ id: item.id })">
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
