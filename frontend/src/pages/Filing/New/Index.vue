<script setup lang="ts">
import ModalUploadFileJson from "@/pages/Filing/New/Components/ModalUploadFileJson.vue";
import ModalUploadFileZip from "@/pages/Filing/New/Components/ModalUploadFileZip.vue";
import { router } from "@/plugins/1.router";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

definePage({
  name: "Filing-New-Index",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "filing.new.index",
  },
});

const authenticationStore = useAuthenticationStore();

//ModalUploadFileZip
const refModalUploadFileZip = ref()
const openModalUploadFileZip = () => {
  refModalUploadFileZip.value.openModal({ id: null, contract_id: null })
}


//ModalUploadFileJson
const refModalUploadFileJson = ref()

const openModalUploadFileJson = () => {
  refModalUploadFileJson.value.openModal()
}

//TABLE
const refTableFull = ref()

const optionsTable = {
  url: "/filing/paginate",
  paramsGlobal: {
    company_id: authenticationStore.company.id,
    user_id: authenticationStore.user.id,
  },
  headers: [
    { key: 'contract_name', title: 'Contrato' },
    { key: 'type_description', title: 'Tipo' },
    { key: 'status', title: 'Estado' },
    { key: 'sumVr', title: 'Valor' },
    { key: 'filing_invoice_pre_radicated_count', title: 'Facturas preradicadas' },
    { key: 'created_at', title: 'Fecha de creación' },
    { key: 'user_full_name', title: 'Usuario' },
    { key: 'actions', title: 'Acciones' },
  ],
}


//FILTER
const optionsFilter = ref({
  dialog: {
    width: 800,
    inputs: [
      {
        type: "selectApi",
        label: "Estado",
        arrayInfo: "statusFilingEnumOpenAndClosed",
        url: "selectStatusFilingEnumOpenAndClosed",
        name: "status",
      }
    ],
  },
  extraFilters: {
    filing_invoice_pre_radicated_count: { value: '' },
  },
  filterLabels: { inputGeneral: 'Buscar en todo', filing_invoice_pre_radicated_count: 'Facturas preradicadas' }

})

const goView = (item: any) => {
  router.push({ name: "Filing-New-List", params: { type: item.type, id: item.id } })
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

    <VCard class="mt-5">
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Lista de radicaciones
        </span>
        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VMenu location="bottom">
            <template #activator="{ props }">
              <VBtn v-bind="props">
                Agregar radicación
                <VIcon icon="tabler-circle-chevrons-down"></VIcon>
              </VBtn>
            </template>

            <VList>
              <VListItem @click="openModalUploadFileZip()">
                Radicación antigua
              </VListItem>
              <VListItem @click="openModalUploadFileJson()">
                Radicación 2275
              </VListItem>
            </VList>
          </VMenu>
        </div>
      </VCardTitle>

      <VCardText>
        <FilterDialogNew :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
          <template #default="{ extraFilters }">
            <VCol cols="3">
              <AppTextField prepend-inner-icon="tabler-search"
                v-model="extraFilters.filing_invoice_pre_radicated_count.value" label="Facturar preradicadas"
                placeholder="Filtrar por facturar preradicadas" type="text" name="filing_invoice_pre_radicated_count" />
            </VCol>
          </template>
        </FilterDialogNew>
      </VCardText>

      <VCardText class=" mt-2">
        <TableFullNew ref="refTableFull" :options="optionsTable" @update:loading="tableLoading = $event">
          <template #item.status="{ item }">
            <div>
              <VChip :color="item.status_backgroundColor">{{ item.status_description }}</VChip>
            </div>
          </template>

          <template #item.actions="{ item }">
            <div>
              <VBtn icon color="primary">
                <VIcon icon="tabler-square-rounded-chevron-down"></VIcon>
                <VMenu activator="parent">
                  <VList>
                    <VListItem @click="goView(item)">
                      Ingresar
                    </VListItem>
                  </VList>
                </VMenu>
              </VBtn>
            </div>
          </template>
        </TableFullNew>
      </VCardText>
    </VCard>

    <ModalUploadFileZip ref="refModalUploadFileZip" />

    <ModalUploadFileJson ref="refModalUploadFileJson" />


  </div>
</template>
