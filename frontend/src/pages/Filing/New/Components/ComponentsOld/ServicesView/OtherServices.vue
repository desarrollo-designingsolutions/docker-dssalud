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
  { key: "Codigo_del_prestador_de_servicios_de_salud", title: 'Código del prestador de servicios de salud', sortable: false, width: "200" },
  { key: "Tipo_de_identificacion_del_usuario", title: 'Tipo de identificación del usuario', sortable: false, width: "200" },
  { key: "numDocumentoIdentificacion", title: 'Número de identificación del usuario en el sistema', sortable: false, width: "200" },
  { key: "Numero_de_autorizacion", title: 'Numero de autorizacion', sortable: false, width: "200" },
  { key: "Tipo_de_servicio", title: 'Tipo de servicio', sortable: false, width: "200" },
  { key: "Codigo_del_servicio", title: 'Codigo del servicio', sortable: false, width: "200" },
  { key: "Nombre_del_servicio", title: 'Nombre del servicio', sortable: false, width: "200" },
  { key: "Cantidad", title: 'Cantidad', sortable: false, width: "200" },
  { key: "Valor_unitario_del_material_e_insumo", title: 'Valor unitario del material e insumo', sortable: false, width: "200" },
  { key: "vrServicio", title: 'Valor total del material e insumo', sortable: false, width: "200" },
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
