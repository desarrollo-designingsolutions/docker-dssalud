<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { useRouter } from 'vue-router';

const { toast } = useToast();

definePage({
  name: "InvoiceAuditAssignmentBatche-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "invoiceAuditAssignmentBatche.list",
  },
});

const authenticationStore = useAuthenticationStore();

const router = useRouter();

//TABLE
const refTableFull = ref()

const optionsTable = {
  showSelect: true,
  url: "/invoiceAudit/paginateBatche",
  headers: [
    { key: 'description', title: 'Descripcion' },
    { key: 'count_invoice', title: 'Facturas Totales', sortable: false },
    { key: 'count_invoice_pending', title: 'Facturas Pendientes', sortable: false },
    { key: 'count_invoice_completed', title: 'Facturas Completadas', sortable: false },
    { key: 'status', title: 'Estado', sortable: false },
    // { key: 'actions', title: 'Acciones', sortable: false, width: 100 },
  ],
  actions: {
    delete: { url: '/assignmentBatche/delete' }
  },
  paramsGlobal: {
    user_id: authenticationStore.user.id,
    company_id: authenticationStore.company.id,
  }
}

//FILTER
const optionsFilter = ref({
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

const goViewThirds = (data: { id: number | null } = { id: null }) => {
  router.push({ name: "InvoiceAuditAssignment-List", params: { assignment_batch_id: data.id } })
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
  if (assignmentsBatchsIds.value.length > 0) {
    if (refModalQuestion.value) {
      refModalQuestion.value.componentData.isDialogVisible = true;
      refModalQuestion.value.componentData.btnSuccessText = 'Si';
      refModalQuestion.value.componentData.btnCancelText = 'No';
      refModalQuestion.value.componentData.title = '¿Esta seguro que deseea finalizar la auditoria?';
      refModalQuestion.value.componentData.subTitle = `Se han seleccionado ${assignmentsBatchsIds.value.length} registros. `;
    }
  } else {
    toast("Debe seleccionar almenos un registro", "", "info")
  }
}

const showBtnsView = ref(true);
const assignmentsBatchsIds = ref<Array<string>>([]);
const refCountAllData = ref()

const successFinalizedAudit = async () => {
  isLoadingSuccessFinalizedAudit.value = true;
  const { data, response } = await useAxios(`/invoiceAudit/successFinalizedAudit`).post({
    assignments_batchs_ids: assignmentsBatchsIds.value,
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

</script>

<template>
  <div>
    <CountAllData ref="refCountAllData" :user_id="authenticationStore.user.id" />

    <VRow>
      <VCol>

        <VCard>
          <VCardTitle class="d-flex justify-space-between">
            <span>
              Auditorias
            </span>

            <div class="d-flex justify-end gap-3 flex-wrap ">

              <VBtn @click="openModalQuestion" :disabled="isLoadingSuccessFinalizedAudit"
                :loading="isLoadingSuccessFinalizedAudit">
                <template #prepend>
                  <VIcon start icon="tabler-files" />
                </template>
                Finalizar auditoria
              </VBtn>
            </div>
          </VCardTitle>

          <VCardText>
            <FilterDialogNew :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
            </FilterDialogNew>
          </VCardText>

          <VCardText class="mt-2">
            <TableFullNew v-model:selected="assignmentsBatchsIds" ref="refTableFull" :options="optionsTable"
              @update:loading="tableLoading = $event">

              <template #item.description="{ item }">
                <div style="cursor: pointer;" @click="goViewThirds({ id: item.id })">
                  {{ item.description }}
                </div>
              </template>

              <template #item.count_invoice="{ item }">
                <div style="cursor: pointer;" @click="goViewThirds({ id: item.id })">
                  {{ item.count_invoice }}
                </div>
              </template>

              <template #item.count_invoice_pending="{ item }">
                <div style="cursor: pointer;" @click="goViewThirds({ id: item.id })">
                  {{ item.count_invoice_pending }}
                </div>
              </template>

              <template #item.count_invoice_completed="{ item }">
                <div style="cursor: pointer;" @click="goViewThirds({ id: item.id })">
                  {{ item.count_invoice_completed }}
                </div>
              </template>

              <template #item.status="{ item }">
                <div style="cursor: pointer;" @click="goViewThirds({ id: item.id })">
                  {{ getInvoiceAuditStatus(item.status).title }}
                </div>
              </template>

            </TableFullNew>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <ModalQuestion ref="refModalQuestion" @success="successFinalizedAudit" />

  </div>
</template>
