<script setup lang="ts">
const { dataList } = defineProps({
  dataList: {
    type: Array<object>,
    default: [],
  }
})
//table 
const inputsTableFilter = ref([
  { key: "numFactura", title: 'Número de la factura', sortable: true },
  { key: "Codigo_del_prestador_de_servicios_de_salud", title: 'Código del prestador de servicios de salud', sortable: true },
  { key: "Tipo_de_identificacion_del_usuario", title: 'Tipo de identificación del usuario', sortable: true },
  { key: "numDocumentoIdentificacion", title: 'Número de identificación del usuario en el sistema', sortable: true },
  { key: "Fecha_de_la_consulta", title: 'Fecha de la consulta', sortable: true },
  { key: "Numero_de_autorizacion", title: 'Número de autorización', sortable: true },
  { key: "Codigo_de_la_consulta", title: 'Código de la consulta', sortable: true },
  { key: "Finalidad_de_la_consulta", title: 'Finalidad de la consulta', sortable: true },
  { key: "Causa_externa", title: 'Causa externa', sortable: true },
  { key: "Codigo_de_diagnostico_principal", title: 'codigo de diagnostico principal', sortable: true },
  { key: "Codigo_del_diagnostico_relacionado_No_1", title: 'Código del diagnóstico relacionado No. 1', sortable: true },
  { key: "Codigo_del_diagnostico_relacionado_No_2", title: 'Código del diagnóstico relacionado No. 2', sortable: true },
  { key: "Codigo_del_diagnostico_relacionado_No_3", title: 'Código del diagnóstico relacionado No. 3', sortable: true },
  { key: "Tipo_de_diagnostico_principal", title: 'Tipo de diagnóstico principal', sortable: true },
  { key: "vrServicio", title: 'Valor de la consulta', sortable: true },
  { key: "Valor_de_la_cuota_moderadora", title: 'Valor de la cuota moderadora', sortable: true },
  { key: "Valor_neto_a_pagar", title: 'Valor neto a pagar', sortable: true },
])



const options = ref({ page: 1, itemsPerPage: 10, sortBy: [''], sortDesc: [false] })
const search = ref('')

</script>
<template>
  <div>
    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" offset-md="8" md="4">
            <AppTextField v-model="search" density="compact" placeholder="Search ..." append-inner-icon="tabler-search"
              single-line hide-details dense outlined clearable />
          </VCol>
        </VRow>
      </VCardText>

      <VCardText>
        <VDataTable :search="search" :headers="inputsTableFilter" :items="dataList"
          :items-per-page="options.itemsPerPage" :page="options.page" :options="options">

        </VDataTable>

      </VCardText>
    </VCard>
  </div>
</template>
