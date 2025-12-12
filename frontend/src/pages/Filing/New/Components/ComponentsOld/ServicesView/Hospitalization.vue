<script setup lang="ts">
const { dataList } = defineProps({
  dataList: {
    type: Array<object>,
    default: [],
  }
})
//table 
const inputsTableFilter = ref([
  { key: 'num_factura', title: 'Número de la factura', sortable: false, width: "350" },
  { key: 'Codigo_del_prestador_de_servicios_de_salud', title: 'Código del prestador de servicios de salud', sortable: false, width: "350" },
  { key: 'Tipo_de_identificacion_del_usuario', title: 'Tipo de identificación del usuario', sortable: false, width: "350" },
  { key: 'numDocumentoIdentificacion', title: 'Número de identificación del usuario en el sistema', sortable: false, width: "350" },
  { key: 'Via_de_ingreso_a_la_institucion', title: 'Via de ingreso a la institucion', sortable: false, width: "350" },
  { key: 'Fecha_de_ingreso_del_usuario_a_la_institucion', title: 'Fecha de ingreso del usuario a la institución', sortable: false, width: "350" },
  { key: 'Hora_de_ingreso_del_usuario_a_la_Institucion', title: 'Hora de ingreso del usuario a la Institución', sortable: false, width: "350" },
  { key: 'Numero_de_autorizacion', title: 'Numero de autorizacion', sortable: false, width: "350" },
  { key: 'Causa_externa', title: 'Causa externa', sortable: false, width: "350" },
  { key: 'Diagnostico_principal_de_ingreso', title: 'Diagnostico principal de ingreso', sortable: false, width: "350" },
  { key: 'Diagnostico_principal_de_egreso', title: 'Diagnóstico principal de egreso', sortable: false, width: "350" },
  { key: 'Diagnostico_relacionado_Nro_1_de_egreso', title: 'Diagnóstico relacionado Nro. 1 de egreso', sortable: false, width: "350" },
  { key: 'Diagnostico_relacionado_Nro_2_de_egreso', title: 'Diagnóstico relacionado Nro. 2 de egreso', sortable: false, width: "350" },
  { key: 'Diagnostico_relacionado_Nro_3_de_egreso', title: 'Diagnóstico relacionado Nro. 3 de egreso', sortable: false, width: "350" },
  { key: 'Diagnostico_de_la_complicacion', title: 'Diagnóstico de la complicacion', sortable: false, width: "350" },
  { key: 'Estado_a_la_salida', title: 'Estado a la salida', sortable: false, width: "350" },
  { key: 'Diagnostico_de_la_causa_basica_de_muerte', title: 'Diagnóstico de la causa básica de muerte', sortable: false, width: "350" },
  { key: 'Fecha_de_egreso_del_usuario_a_la_institucion', title: 'Fecha de egreso del usuario a la institución', sortable: false, width: "350" },
  { key: 'Hora_de_egreso_del_usuario_de_la_institucion', title: 'Hora de egreso del usuario de la institución', sortable: false, width: "350" },
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
