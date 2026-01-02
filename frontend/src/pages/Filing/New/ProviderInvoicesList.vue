<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

definePage({
  path: "providerInvoicesList",
  name: "ProviderInvoices-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "providerInvoices.list",
  },
});

const route = useRoute();
const authenticationStore = useAuthenticationStore();

//TABLE
const invoiceAuditIds = ref<Array<string>>([]);
const tableLoading = ref(false);
const refTableFull = ref();

const optionsTable = {
  showSelect: true,
  url: `/filingInvoice/paginateThirds`,
  paramsGlobal: {
    company_id: authenticationStore.company.id,
    third_user_id: authenticationStore.user.id,
  },
  headers: [
    { key: 'invoice_number', title: 'Número de factura' },
    { key: 'users_count', title: 'Cant. Usuarios' },
    { key: 'files_count', title: 'Soportes Cargados' },
    { key: 'case_number', title: 'N° Radicado' },
    { key: 'sumVr', title: 'Valor' },
    { key: "date", title: 'Fecha' },
    { key: "status", title: 'Estado' },
    { key: 'type_radication', title: 'Tipo de radicación' },
    { key: 'contract_number', title: 'N° Contrato' },
    { key: 'created_at', title: 'Fecha de radicación' },
    { key: 'updated_at', title: 'Fecha de actualización' },
    { key: 'third_name', title: 'Tercero' },
    { key: 'actions', title: 'Acciones', sortable: false },
  ],
  actions: {
    view: {
      show: true,
    },
    edit: {
      show: false,
    },
    delete: {
      show: false,
    },
  },
}

//FILTER
const optionsFilter = ref({
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(null, false, true); // Forzamos la búsqueda
  }
};
</script>

<template>
  <div>
    <VRow>
      <VCol>
        <VCard>
          <VCardTitle class="d-flex justify-space-between">
            <span>
              Facturas del prestador
            </span>
          </VCardTitle>

          <VCardText>
            <FilterDialogNew :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
            </FilterDialogNew>
          </VCardText>

          <VCardText class="mt-2">
            <TableFullNew v-model:selected="invoiceAuditIds" ref="refTableFull" :options="optionsTable"
              @update:loading="tableLoading = $event">
              <template #item.type_radication="{ item }">
                <div>
                  ????
                </div>
              </template>

              <template #item.contract_number="{ item }">
                <div>
                  ????
                </div>
              </template>

              <template #item.status="{ item }">
                <div>
                  <VChip :color="item.status_backgroundColor">{{ item.status_description }}</VChip>
                </div>
              </template>
            </TableFullNew>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
