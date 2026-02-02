<script setup lang="ts">
import ModalUploadGlosaFileCsv from "@/pages/InvoiceAudit/Components/ModalUploadGlosaFileCsv.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { useRouter } from 'vue-router';

const { toast } = useToast();

definePage({
  path: "invoiceAuditAssignmentList/invoice-audit/:assignment_batch_id/:third_id",
  name: "InvoiceAuditInvoiceAudit-List",
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

//TABLE
const refTableFull = ref()

const optionsTable = {
  showSelect: true,
  url: `/invoiceAudit/paginateInvoiceAudit/${assignment_batch_id}/${third_id}`,
  headers: [
    { key: 'invoice_number', title: 'Factura' },
    { key: 'count_patients', title: 'Cantidad Usuarios', sortable: false },
    { key: 'count_services', title: 'Cant Servicios', sortable: false },
    { key: 'total_value', title: 'Valor Factura'},
    { key: 'glosas', title: 'Glosas', sortable: false },
    { key: "codes_glosa", title: 'Códigos Glosa', minWidth: 200 },
    { key: 'value_glosa', title: 'Valor Glosado' },
    { key: 'value_approved', title: 'Valor Aprobado' },
    { key: 'status', title: 'Estado', sortable: false },
    { key: 'user_names', title: 'Usuario' },
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

const goViewPatients = (data: any = { id: null }) => {
  router.push({ name: "InvoiceAuditPatient-List", params: { assignment_batch_id: assignment_batch_id, third_id: third_id, invoice_audit_id: data.id } })
}

const goViewAssignmentList = () => {
  router.push({ name: "InvoiceAuditAssignment-List", params: { assignment_batch_id: assignment_batch_id } })
}


const { downloadExcel, isLoadingExcel } = useGlosaInportExport()

const downloadExport = async () => {
  downloadExcel({
    third_id: third_id,
    assignment_batch_id: assignment_batch_id,
    user_id: authenticationStore.user.id,
    from: "invoices",

  })
}

//ModalUploadGlosaFileCsv
const refModalUploadGlosaFileCsv = ref()
const openModalUploadGlosaFileCsv = () => {
  refModalUploadGlosaFileCsv.value.openModal({
    third_id: third_id,
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
  if (invoiceAuditIds.value.length > 0) {
    if (refModalQuestion.value) {
      refModalQuestion.value.componentData.isDialogVisible = true;
      refModalQuestion.value.componentData.btnSuccessText = 'Si';
      refModalQuestion.value.componentData.btnCancelText = 'No';
      refModalQuestion.value.componentData.title = '¿Esta seguro que deseea finalizar la auditoria?';
      refModalQuestion.value.componentData.subTitle = `Se han seleccionado ${invoiceAuditIds.value.length} registros. `;
    }
  } else {
    toast("Debe seleccionar almenos un registro", "", "info")
  }

}

const showBtnsView = ref(true);
const invoiceAuditIds = ref<Array<string>>([]);
const refCountAllData = ref()

const successFinalizedAudit = async () => {
  isLoadingSuccessFinalizedAudit.value = true;
  const { data, response } = await useAxios(`/invoiceAudit/successFinalizedAudit`).post({
    invoices_audits_ids: invoiceAuditIds.value,
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
    <CountAllData ref="refCountAllData" :assignment_batch_id="assignment_batch_id" :third_id="third_id"
      :user_id="authenticationStore.user.id" />

    <VRow>
      <VCol>
        <VCard>
          <VCardTitle class="d-flex justify-space-between">
            <span>
              Facturas
            </span>

            <div class="d-flex justify-end gap-3 flex-wrap ">
              <VBtn @click="goViewAssignmentList">
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
            <TableFullNew v-model:selected="invoiceAuditIds" ref="refTableFull" :options="optionsTable"
              @update:loading="tableLoading = $event">

              <template #item.invoice_number="{ item }">
                <div style="cursor: pointer;" @click="goViewPatients({ id: item.id })">
                  {{ item.invoice_number }}
                </div>
              </template>

              <template #item.count_patients="{ item }">
                <div style="cursor: pointer;" @click="goViewPatients({ id: item.id })">
                  {{ item.count_patients }}
                </div>
              </template>

              <template #item.count_services="{ item }">
                <div style="cursor: pointer;" @click="goViewPatients({ id: item.id })">
                  {{ item.count_services }}
                </div>
              </template>

              <template #item.total_value="{ item }">
                <div style="cursor: pointer;" @click="goViewPatients({ id: item.id })">
                  {{ item.total_value }}
                </div>
              </template>

              <template #item.glosas="{ item }">
                <div style="cursor: pointer;" @click="goViewPatients({ id: item.id })">
                  {{ item.glosas }}
                </div>
              </template>

              <template #item.value_glosa="{ item }">
                <div style="cursor: pointer;" @click="goViewPatients({ id: item.id })">
                  {{ item.value_glosa }}
                </div>
              </template>

              <template #item.spent="{ item }">
                <div style="cursor: pointer;" @click="goViewPatients({ id: item.id })">
                  {{ item.spent }}
                </div>
              </template>

              <template #item.status="{ item }">
                <div style="cursor: pointer;" @click="goViewPatients({ id: item.id })">
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
