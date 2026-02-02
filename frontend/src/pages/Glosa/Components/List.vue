<script setup lang="ts">
import ModalForm from "@/pages/Glosa/Components/ModalForm.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();

const props = defineProps({
  service_id: {
    type: String,
  },
  total_value: {
    type: String,
  },
  showBtnsView: {
    type: Boolean,
    default: true
  }
})

//TABLE
const refTableFull = ref()

const optionsTable = ref({
  url: "/glosa/paginate",
  headers: [
    { key: 'user_full_name', title: 'Usuario' },
    { key: 'service_description', title: 'Servicio' },
    { key: 'code_glosa_code', title: 'Código de glosa' },
    { key: 'code_glosa_description', title: 'Descripción' },
    { key: 'glosa_value', title: 'Valor' },
    { key: 'observation', title: 'Observación' },
    { key: 'actions', title: 'Acciones' },
  ],
  paramsGlobal: {
    service_id: props.service_id
  },
  actions: {
    delete: {
      url: '/glosa/delete',
      show: props.showBtnsView // Asignar el valor de props.showBtnsView
    },
    edit: { 
      show: props.showBtnsView // Asignar el valor de props.showBtnsView
    },
  }
})


//FILTER
const optionsFilter = ref({
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

//ModalForm
const refModalForm = ref()

const openModalFormCreate = () => {
  refModalForm.value.openModal({ service_id: props.service_id, total_value: props.total_value })
}

const openModalFormEdit = async (data: any) => {
  refModalForm.value.openModal({ service_id: props.service_id, id: data.id, total_value: props.total_value })
}

const openModalFormView = async (data: any) => {
  refModalForm.value.openModal({ service_id: props.service_id, id: data.id, total_value: props.total_value }, true)
}

const tableLoading = ref(false); // Estado de carga de la tabla


// Nueva prop para controlar si se actualiza la URL
const disableUrlUpdate = ref(true);

// Nuevo método para manejar la búsqueda forzada desde el filtro
const handleForceSearch = (params) => {
  if (refTableFull.value) {
    // Si disableUrlUpdate está activo, pasamos los parámetros manualmente
    if (disableUrlUpdate.value && params) {
      refTableFull.value.fetchTableData(null, false, true, params);
    } else {
      refTableFull.value.fetchTableData(null, false, true);
    }
  }
};

</script>

<template>
  <div>

    <VCard>
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Glosas
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn v-if="showBtnsView" @click="openModalFormCreate()">
            Crear Glosa
          </VBtn>
        </div>
      </VCardTitle>

      <VCardText>
        <FilterDialogNew :options-filter="optionsFilter" @force-search="handleForceSearch" :table-loading="tableLoading"
          :disable-url-update="disableUrlUpdate">
        </FilterDialogNew>
      </VCardText>

      <VCardText class="mt-2">
        <TableFullNew ref="refTableFull" :options="optionsTable" @edit="openModalFormEdit" @view="openModalFormView"
          @update:loading="tableLoading = $event" :disable-url-update="disableUrlUpdate" >

        </TableFullNew>

      </VCardText>
    </VCard>
    <ModalForm ref="refModalForm" @execute="handleForceSearch"></ModalForm>
  </div>
</template>
