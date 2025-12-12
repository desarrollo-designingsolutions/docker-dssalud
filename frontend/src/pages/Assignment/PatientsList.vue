<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { useRouter } from 'vue-router';

definePage({
  path: "assignment/invoice-audit/:assignment_batch_id/:third_id/:invoice_audit_id",
  name: "AssignmentPatient-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "assignmentBatche.list",
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
  url: `/assignment/paginatePatient/${assignment_batch_id}/${third_id}/${invoice_audit_id}`,
  headers: [
    { key: 'identification_number', title: 'Documento' },
    { key: 'full_name', title: 'Nombre' },
    { key: 'gender', title: 'Genero' },
    { key: 'glosas', title: 'Glosas', sortable: false },
    { key: 'value_glosa', title: 'Valor Glosado', sortable: false },
    { key: 'value_approved', title: 'Valor Aprobado', sortable: false },
    { key: 'total_value', title: 'Valor Total', sortable: false },
    { key: 'status', title: 'Estado', sortable: false },
    // { key: 'actions', title: 'Acciones', sortable: false, width: 100 },
  ],
  actions: {
    delete: { url: '/assignment/delete' }
  },
  paramsGlobal: {
    company_id: authenticationStore.company.id,
  }
}


//FILTER
const optionsFilter = ref({
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

const goViewInvoiceAuditList = () => {
  router.push({ name: "AssignmentInvoiceAudit-List", params: { assignment_batch_id: assignment_batch_id, third_id: third_id } })
}

const tableLoading = ref(false); // Estado de carga de la tabla

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(null, false, true); // Forzamos la búsqueda
  }
};
</script>

<template>
  <div>
    <CountAllData :assignment_batch_id="assignment_batch_id" :third_id="third_id" />

    <VRow>
      <VCol>
        <VCard>
          <VCardTitle class="d-flex justify-space-between">
            <span>
              Usuarios
            </span>

            <div class="d-flex justify-end gap-3 flex-wrap ">
              <VBtn @click="goViewInvoiceAuditList">
                Regresar
              </VBtn>
            </div>
          </VCardTitle>

          <VCardText>
            <FilterDialogNew :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
            </FilterDialogNew>
          </VCardText>

          <VCardText class="mt-2">
            <TableFullNew ref="refTableFull" :options="optionsTable" @update:loading="tableLoading = $event">

              <template #item.status="{ item }">
                <div>
                  {{ getInvoiceAuditStatus(item.status).title }}
                </div>
              </template>

            </TableFullNew>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

  </div>
</template>
