<script setup lang="ts">
import { router } from '@/plugins/1.router';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

definePage({
  name: "ReconciliationGroup-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "reconciliationGroup.list",
  },
});

const authenticationStore = useAuthenticationStore();

const company_id = {
  company_id: authenticationStore.company.id,
}

//TABLE
const refTableFull = ref()

const optionsTable = {
  url: "/reconciliationGroup/paginate",
  paramsGlobal: company_id,
  headers: [
    { key: 'name', title: 'Nombre' },  
    { key: 'third_nit', title: 'Nit' }, 
    { key: 'third_name', title: 'Tercero' }, 
    { key: 'link', title: 'Link' }, 
    { key: 'actions', title: 'Acciones', sortable: false,width: '50' },
  ],
  actions: {
    changeStatus: {
      url: "/reconciliationGroup/changeStatus"
    },
    delete: {
      url: "/reconciliationGroup/delete"
    },
    edit: {
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

const goViewView = async (data: any) => {
  router.push({ name: "ReconciliationGroup-Form", params: { action: 'view', id: data.id } })
}

const goViewEdit = async (data: any) => {
  router.push({ name: "ReconciliationGroup-Form", params: { action: 'edit', id: data.id } })
}

const goViewCreate = async () => {
  router.push({ name: "ReconciliationGroup-Form", params: { action: 'create' } })
}

const tableLoading = ref(false);

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(null, false, true); // Forzamos la búsqueda
  }
};

const route = useRoute()

const loading = reactive({ excel: false })
const downloadExcel = async () => {
  loading.excel = true;

  const { data, response } = await useAxios("/reconciliationGroup/excelExport").get({
    params: {
      ...route.query,
      company_id: authenticationStore.company.id
    }
  })

  loading.excel = false;

  if (response.status == 200 && data) {
    downloadExcelBase64(data.excel, "Lista de grupos de conciliación")
  }
}
</script>

<template>
  <div>

    <VCard>
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Grupos de conciliación
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
        <VBtn :loading="loading.excel" :disabled="loading.excel" size="38" color="primary" icon
            @click="downloadExcel()">
            <VIcon icon="tabler-file-spreadsheet"></VIcon>
            <VTooltip location="top" transition="scale-transition" activator="parent" text="Descargar Excel">
            </VTooltip>
          </VBtn>
          <VBtn @click="goViewCreate()">
            Agregar Grupo de conciliación
          </VBtn>
        </div>
      </VCardTitle>

      <VCardText>
        <FilterDialogNew :options-filter="optionsFilterNew" @force-search="refreshTable" :table-loading="tableLoading">
        </FilterDialogNew>
      </VCardText>

      <VCardText class="mt-2">
        <TableFullNew ref="refTableFull" :options="optionsTable" @edit="goViewEdit" @view="goViewView"
          @update:loading="tableLoading = $event">
           
        </TableFullNew>
      </VCardText>
    </VCard>
  </div>
</template>
