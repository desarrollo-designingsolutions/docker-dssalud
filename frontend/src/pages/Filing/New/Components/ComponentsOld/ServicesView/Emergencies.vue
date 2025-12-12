<script setup lang="ts">
const { dataList } = defineProps({
  dataList: {
    type: Array<object>,
    default: [],
  }
})
//table 
const inputsTableFilter = ref([
  { key: "numFactura", title: 'Número de la factura', sortable: false, width: "200" },
  { key: "Codigo_del_prestador_de_servicios_de_salud", title: 'Código del prestador de servicios de salud', sortable: false, width: "350" },
  { key: "Tipo_de_identificacion_del_usuario", title: 'Tipo de identificación del usuario', sortable: false, width: "350" },
  { key: "numDocumentoIdentificacion", title: 'Número de identificación del usuario en el sistema', sortable: false, width: "350" },
  { key: "Fecha_de_ingreso_del_usuario_a_observacion", title: 'Fecha de ingreso del usuario a observacion', sortable: false, width: "350" },
  { key: "Hora_de_ingreso_del_usuario_a_observacion", title: 'Hora de ingreso del usuario a observacion', sortable: false, width: "350" },
  { key: "Numero_de_autorizacion", title: 'Numero de autorizacion', sortable: false, width: "350" },
  { key: "Causa_externa", title: 'Causa externa', sortable: false, width: "350" },
  { key: "Diagnostico_a_la_salida", title: 'Diagnostico a la salida', sortable: false, width: "350" },
  { key: "Diagnostico_relacionado_Nro_1_a_la_salida", title: 'Diagnóstico relacionado Nro. 1 a la salida', sortable: false, width: "200" },
  { key: "Diagnostico_relacionado_Nro_2_a_la_salida", title: 'Diagnóstico relacionado Nro. 2 a la salida', sortable: false, width: "200" },
  { key: "Diagnostico_relacionado_Nro_3_a_la_salida", title: 'Diagnóstico relacionado Nro. 3 a la salida', sortable: false, width: "200" },
  { key: "Destino_del_usuario_a_la_salida_de_observacion", title: 'Destino del usuario a la salida de observación', sortable: false, width: "200" },
  { key: "Estado_a_la_salida", title: 'Estado a la salida', sortable: false, width: "200" },
  { key: "Causa_basica_de_muerte_en_urgencias", title: 'Causa básica de muerte en urgencias', sortable: false, width: "200" },
  { key: "Fecha_de_la_salida_del_usuario_en_observacion", title: 'Fecha de la salida del usuario en observación', sortable: false, width: "200" },
  { key: "Hora_de_la_salida_del_usuario_en_observacion", title: 'Hora de la salida del usuario en observación', sortable: false, width: "200" },
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
