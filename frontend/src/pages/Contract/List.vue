<script setup lang="ts">
import ModalForm from '@/pages/Contract/Components/ModalForm.vue';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

definePage({
  name: "Contract-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "contract.list",
  },
});

const { btnCreate } = defineProps({
  btnCreate: {
    type: Boolean,
    default: true,
  },
});

const authenticationStore = useAuthenticationStore();

//TABLE
const refTableFullNew = ref()
const optionsTable = {
  url: "/contract/paginate",
  paramsGlobal: {
    company_id: authenticationStore.company.id,
  },
  headers: [
    { key: 'name', title: 'Nombre' },
    { key: 'third_name', title: 'Tercero' },
    { key: 'actions', title: 'Acciones', sortable: false, width: 100 },
  ],
  actions: {
    changeStatus: {
      url: "/contract/changeStatus"
    },
    delete: {
      url: "/contract/delete"
    },
  }
}

//FILTER
const optionsFilterNew = ref({
  filterLabels: { inputGeneral: 'Buscar en todo' }
})


//ModalForm
const refModalForm = ref()

const reloadTable = () => {
  refTableFullNew.value.fetchTableData()
}

const goViewView = async (data: any) => {
  refModalForm.value.openModal(data.id, true)
}

const goViewEdit = async (data: any) => {
  refModalForm.value.openModal(data.id, false)
}

const goViewCreate = async () => {
  refModalForm.value.openModal()
}


const tableLoading = ref(false);

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFullNew.value) {
    refTableFullNew.value.fetchTableData(1, false, true); // Forzamos la búsqueda
  }
};

</script>

<template>
  <div>

    <VCard>
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Contratos
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn @click="goViewCreate()" v-if="btnCreate">
            Agregar contrato
          </VBtn>
        </div>
      </VCardTitle>

      <VCardText>
        <FilterDialogNew :options-filter="optionsFilterNew" @force-search="refreshTable" :table-loading="tableLoading">
        </FilterDialogNew>
      </VCardText>

      <VCardText class="mt-2">
        <TableFullNew ref="refTableFullNew" :options="optionsTable" @edit="goViewEdit" @view="goViewView"
          @update:loading="tableLoading = $event">
        </TableFullNew>
      </VCardText>
    </VCard>

    <ModalForm ref="refModalForm" @execute="reloadTable" />

  </div>
</template>
