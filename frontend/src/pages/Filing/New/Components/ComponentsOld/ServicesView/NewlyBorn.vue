<script setup lang="ts">
const { dataList } = defineProps({
  dataList: {
    type: Array<object>,
    default: [],
  }
})
//table 
const inputsTableFilter = ref([
  { key: 'num_factura', title: 'Número de la factura', sortable: false, width: "200" },
  { key: 'Codigo_del_prestador_de_servicios_de_salud', title: 'Código del prestador de servicios de salud', sortable: false, width: "200" },
  { key: 'Tipo_de_identificacion_de_la_madre', title: 'Tipo de identificación de la madre', sortable: false, width: "200" },
  { key: 'numDocumentoIdentificacion', title: 'Numero de identificacion de la madre en el Sistema', sortable: false, width: "200" },
  { key: 'Fecha_de_nacimiento_del_recien_nacido', title: 'Fecha de nacimiento del recién nacido', sortable: false, width: "200" },
  { key: 'Hora_de_nacimiento', title: 'Hora de nacimiento', sortable: false, width: "200" },
  { key: 'Edad_gestacional', title: 'Edad gestacional', sortable: false, width: "200" },
  { key: 'Control_prenatal', title: 'Control prenatal', sortable: false, width: "200" },
  { key: 'Sexo', title: 'Sexo', sortable: false, width: "200" },
  { key: 'Peso', title: 'Peso', sortable: false, width: "200" },
  { key: 'Diagnostico_del_recien_nacido', title: 'Diagnóstico del recién nacido', sortable: false, width: "200" },
  { key: 'Causa_basica_de_muerte', title: 'Causa básica de muerte', sortable: false, width: "200" },
  { key: 'Fecha_de_muerte_del_recien_nacido', title: 'Fecha de muerte del recién nacido', sortable: false, width: "200" },
  { key: 'Hora_de_muerte_del_recien_nacido', title: 'Hora de muerte del recién nacido', sortable: false, width: "200" },
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
