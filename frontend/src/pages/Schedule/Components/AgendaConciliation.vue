<script setup lang="ts">
import ModalViewEventConciliation from '@/pages/Schedule/Components/ModalViewEventConciliation.vue';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();
const route = useRoute()
const loading = reactive({ excel: false })

//TABLE
const refTableFull = ref()

const optionsTable = {
  url: "/schedule/conciliation/paginateAgenda",
  headers: [
    { key: 'title', title: 'Título', minWidth:200  },
    { key: 'start_date', title: 'Fecha inicio',minWidth:160 },
    { key: "response_status", title: 'Estado de la respuesta',minWidth:160 },
    { key: "response_date", title: 'Fecha de la respuesta', minWidth:160},
    { key: "third_name", title: 'Tercero', minWidth:300 },
    { key: "user_name", title: 'Asignado', minWidth:300  },
    { key: "reconciliation_group_name", title: 'Grupo de conciliación', minWidth:300  },
    { key: "btn", title: 'Acciones',  minWidth:50 },
  ],
}

//FILTER
const optionsFilter = ref({
  dialog: {
    width: 800,
    inputs: [
      {
        name: "response_status",
        label: "Estado",
        type: "selectApi",
        value: null,
        multiple: true,
        url: "/selectScheduleResponseStatusEnum",
        arrayInfo: "scheduleResponseStatusEnum",
      },
      {
        name: "response_date",
        label: "Fecha de respuestas",
        type: "dateRange",
        value: null,
      },
      {
        name: "start_date",
        label: "Fecha Inicio",
        type: "dateRange",
        value: null,
      },

    ],
  }, 
})

const tableLoading = ref(false); // Estado de carga de la tabla

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(null, false, true); // Forzamos la búsqueda
  }
};

const downloadExcel = async () => {
  loading.excel = true;

  const { data, response } = await useAxios("/schedule/conciliation/excelExport").get({
    params: {
      ...route.query,
      company_id: authenticationStore.company.id
    }
  })

  loading.excel = false;

  if (response.status == 200 && data) {
    downloadExcelBase64(data.excel, "Agenda")
  }
}

const openModalInfo = ()=>{

}

// ModalViewEventConciliation
const refModalViewEventConciliation = ref();

const openModalViewEventConciliation = (eventId: any) => {
  refModalViewEventConciliation.value.openModal(eventId);
};

</script>

<template>
  <div>

    <VCard>
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Listado de agendas
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn :loading="loading.excel" :disabled="loading.excel" size="38" color="primary" icon
            @click="downloadExcel()">
            <VIcon icon="tabler-file-spreadsheet"></VIcon>
            <VTooltip location="top" transition="scale-transition" activator="parent" text="Descargar Excel">
            </VTooltip>
          </VBtn>
        </div>
      </VCardTitle>

      <VCardText>
        <FilterDialogNew :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
       
        </FilterDialogNew>
      </VCardText>

      <VCardText class="mt-2">
        <TableFullNew ref="refTableFull" :options="optionsTable" @update:loading="tableLoading = $event">

          <template #item.response_status="{ item }">
            <div>
              <VChip :color="item.response_status_backgroundColor">{{ item.response_status_description }}</VChip>
            </div>
          </template>

          <template #item.btn="{ item }">
            <div>
              <VBtn @click="openModalViewEventConciliation(item.id)">Ver información</VBtn>
            </div>
          </template>

        </TableFullNew>
      </VCardText>
    </VCard>

    <ModalViewEventConciliation ref="refModalViewEventConciliation" :showBtn="false" />

  </div>
</template>
