<script setup lang="ts">
import ModalUploadFileCsv from "@/pages/FilingCoosalud/Components/ModalUploadFileCsv.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

definePage({
  name: "FilingCoosalud-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "filing.coosalud.list",
  },
})

const authenticationStore = useAuthenticationStore();

//TABLE
const refTableFull = ref()
const tableLoading = ref(false); // Estado de carga de la tabla

const optionsTable = {
  url: "/invoiceAudit/paginateInvoiceAuditAll",
  paramsGlobal: {
    company_id: authenticationStore.company.id,
    third_id: authenticationStore.user.third.id,
  },
  headers: [
    { key: 'third_id', title: 'Nit' },
    { key: 'invoice_number', title: 'Número Factura', sortable: false },
    { key: 'total_value', title: 'Valor Factura', sortable: false },
    { key: 'origin', title: 'Origen'},
    // { key: 'expedition_date', title: 'Fecha Factura', sortable: false },
    // { key: 'date_entry', title: 'Fecha Inicio', sortable: false },
    // { key: 'date_departure', title: 'Fecha Fin', sortable: false },
    { key: "modality", title: 'Modalidad', sortable: false },
    { key: "regimen", title: 'Régimen', sortable: false },
    { key: "coverage", title: 'Cobertura', sortable: false },
    { key: "contract_number", title: 'Contrato', sortable: false },
    { key: 'status', title: 'Estado', sortable: false },
    { key: 'actions2', title: 'Acciones', sortable: false }
  ],
  actions: {
    changeStatus: {
      url: "/company/changeStatus"
    },
    view: {
      show: false,
    },
    delete: {
      show: false,
    },
  }
}

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(null, false, true); // Forzamos la búsqueda
  }
};

//FILTER
const optionsFilter = ref({
  dialog: {
    width: 500,
    inputs: [],
  },
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

//ModalUploadFileZip
const refModalUploadFileCsv = ref()
const openModalUploadFileCsv = () => {
  refModalUploadFileCsv.value.openModal()
}

const downloadPdf = async (id: number) => {
  try {
    // loading.pdf = true

    const now = new Date();
    const fecha = now.toLocaleDateString('es-CO').replace(/\//g, '-');
    const hora = now.toLocaleTimeString('es-CO', { hour12: false }).replace(/:/g, '-');

    const api = `/invoiceAudit/generatePdf/${id}`
    const nameFile = `invoiceAudit_${fecha}_${hora}`;
    const ext = "pdf"
    const params = {}

    await downloadBlob("get", api, nameFile, ext, params)
  } catch (error) {
    console.error('Error al descargar el archivo:', error);
  } finally {
    // loading.pdf = false
  }
};
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Compañias
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn @click="openModalUploadFileCsv()">
            Importar Csv
          </VBtn>
        </div>
      </VCardTitle>

      <VCardText>
        <FilterDialogNew :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
        </FilterDialogNew>
      </VCardText>

      <VCardText class="mt-2">
        <TableFullNew ref="refTableFull" :options="optionsTable" @update:loading="tableLoading = $event">

          <template #item.logo="{ item }">
            <div class="my-2">
              <VImg style="width: 80px;" :src="storageBack(item.logo)"></VImg>
            </div>
          </template>
          <template #item.actions2="{ item }">
            <VBtn size="small" color="primary" icon="tabler-file-type-pdf" @click="downloadPdf(item.id)" />
          </template>
        </TableFullNew>
      </VCardText>
    </VCard>

    <ModalUploadFileCsv ref="refModalUploadFileCsv" />
  </div>
</template>
