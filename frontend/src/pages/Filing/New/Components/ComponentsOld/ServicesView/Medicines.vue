<script setup lang="ts">
const { dataList } = defineProps({
  dataList: {
    type: Array<object>,
    default: [],
  }
})
//table 
const inputsTableFilter = ref([
  { key: "numFactura", title: 'Número de la factura', sortable: false, width: "350" },
  { key: "Codigo_del_prestador_de_servicios_de_salud", title: 'Código del prestador de servicios de salud', sortable: false, width: "350" },
  { key: "Tipo_de_identificacion_del_usuario", title: 'Tipo de identificación del usuario', sortable: false, width: "350" },
  { key: "numDocumentoIdentificacion", title: 'Número de identificación del usuario en el sistema', sortable: false, width: "350" },
  { key: "Numero_de_autorizacion", title: 'Numero de autorizacion', sortable: false, width: "350" },
  { key: "Codigo_del_medicamento", title: 'Código del medicamento', sortable: false, width: "350" },
  { key: "Tipo_de_medicamento", title: 'Tipo de medicamento', sortable: false, width: "350" },
  { key: "Nombre_generico_del_medicamento", title: 'Nombre genérico del medicamento', sortable: false, width: "350" },
  { key: "Forma_farmaceutica", title: 'Forma farmacéutica', sortable: false, width: "350" },
  { key: "Concentracion_del_medicamento", title: 'Concentración del medicamento', sortable: false, width: "350" },
  { key: "Unidad_de_medida_del_medicamento", title: 'Unidad de medida del medicamento', sortable: false, width: "350" },
  { key: "Numero_de_unidades", title: 'Número de unidades', sortable: false, width: "350" },
  { key: "Valor_unitario_de_medicamento", title: 'Valor unitario de medicamento', sortable: false, width: "350" },
  { key: "vrServicio", title: 'Valor total de medicamento', sortable: false, width: "350" },
])


const options = ref({ page: 1, itemsPerPage: 10, sortBy: [''], sortDesc: [false] })
const search = ref('')

</script>
<template>
  <div>
    <VCard>
      <VCardText>
        <VCardText>
          <VRow>
            <VCol cols="12" offset-md="8" md="4">
              <AppTextField v-model="search" density="compact" placeholder="Search ..."
                append-inner-icon="tabler-search" single-line hide-details dense outlined clearable />
            </VCol>
          </VRow>
        </VCardText>
        <VDataTable :search="search" :headers="inputsTableFilter" :items="dataList"
          :items-per-page="options.itemsPerPage" :page="options.page" :options="options">


        </VDataTable>

      </VCardText>
    </VCard>
  </div>
</template>
