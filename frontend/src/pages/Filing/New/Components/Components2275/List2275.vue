<script setup lang="ts">
import CountAllDataInvoices from "@/pages/Filing/New/Components/CountAllDataInvoices.vue";
import ModalErrorsFiling from "@/pages/Filing/New/Components/ModalErrorsFiling.vue";
import ModalErrorsFilingInvoice from "@/pages/Filing/New/Components/ModalErrorsFilingInvoice.vue";
import ModalSupportFiles from "@/pages/Filing/New/Components/ModalSupportFiles.vue";
import ModalSupportMasiveFiles from "@/pages/Filing/New/Components/ModalSupportMasiveFiles.vue";
import ModalUploadFileXml from "@/pages/Filing/New/Components/ModalUploadFileXml.vue";
import ModalXmlMasiveFiles from "@/pages/Filing/New/Components/ModalXmlMasiveFiles.vue";
import { router } from "@/plugins/1.router";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

import ModalUploadFileJson from "@/pages/Filing/New/Components/ModalUploadFileJson.vue";
import ModalUploadFileZip from "@/pages/Filing/New/Components/ModalUploadFileZip.vue";

const route = useRoute()
const authenticationStore = useAuthenticationStore();
const filing_id = route.params.id;

onMounted(async () => {
  await getData()
})
//TABLE
const refTableFull = ref()

const optionsTable = {
  url: "/filingInvoice/paginate",
  paramsGlobal: {
    company_id: authenticationStore.company.id,
    type: 'FILING_TYPE_002',
    filing_id: filing_id,
  },
  headers: [
    { key: 'invoice_number', title: 'Número de factura' },
    { key: 'users_count', title: 'Cant. Usuarios' },
    { key: 'files_count', title: 'Soportes Cargados' },
    { key: 'case_number', title: 'N° Radicado' },
    { key: 'sumVr', title: 'Valor' },
    { key: "status", title: 'Estado' },
    { key: "date", title: 'Fecha' },
    { key: "status_xml", title: 'XML' },
    { key: 'actions', title: 'Acciones', sortable: false },
  ],
  actions: {
    delete: {
      url: "/filingInvoice/delete",
    },
  }
}


//FILTER
const filterTable = ref()
const optionsFilter = ref({
  dialog: {
    width: 800,
    cols: '4',
    inputs: [
      {
        type: "selectApi",
        label: "Estado",
        arrayInfo: "statusFilingInvoiceTypeEnum",
        url: "selectStatusFilingInvoiceTypeEnum",
        name: "status",
      },
      {
        type: "selectApi",
        label: "XML",
        arrayInfo: "statusXmlFilingInvoiceEnum",
        name: "status_xml",
        url: "selectStatusXmlFilingInvoiceEnum",
      },
      {
        type: "dateRange",
        label: "Fecha",
        name: "date",
      },
    ],
  },
  extraFilters: {
    files_count: { value: '' },
  },
  filterLabels: { inputGeneral: 'Buscar en todo', files_count: "Soportes cargados" }

})
const returnFilter = (filter: any) => {
  filterTable.value = filter
}

const loading = reactive({ excel: false, getData: false, finishFiling: false })

//ModalQuestion
const refModalQuestion = ref()
const refModalQuestionResponseFinishFiling = ref()

const finishFiling = async () => {
  loading.finishFiling = true
  const { data, response } = await useAxios(`/filing/getCountFilingInvoicePreRadicated/${filing_id}`).get()
  if (response.status==200 && data) {
    const countData = data.countData ?? 0
    if (countData) {
      refModalQuestion.value.componentData.isDialogVisible = true
      refModalQuestion.value.componentData.principalIcon = 'tabler-help-hexagon'
      refModalQuestion.value.componentData.btnSuccessText = 'Aceptar'
      refModalQuestion.value.componentData.title = `Desea finalizar ${countData} cantidad de facturas en estado preradicado?`
    }
  }
  loading.finishFiling = false

}
const responseFinishFiling = async () => {
  loading.finishFiling = true
   const { data, response } = await useAxios(`/filing/changeStatusFilingInvoicePreRadicated`).post({
    filing_id: filing_id,
    user_id: authenticationStore.user.id,
    company_id: authenticationStore.company.id,
  })
  if (response.status==200 && data) {
    refModalQuestionResponseFinishFiling.value.componentData.isDialogVisible = true
    refModalQuestionResponseFinishFiling.value.componentData.showBtnCancel = false
    refModalQuestionResponseFinishFiling.value.componentData.principalIcon = 'tabler-circle-check'
    refModalQuestionResponseFinishFiling.value.componentData.btnSuccessText = 'Aceptar'
    refModalQuestionResponseFinishFiling.value.componentData.title = 'Radicación finalizada de manera exitosa'

    reloadTable()
    refreshCountAllDataInvoices()
  }
  loading.finishFiling = false
}

//Visualizar usuarios
const goViewUsers = (item: any) => {
  router.push({ name: "Filing-New-ListUsers", params: { id: filing_id, invoice_id: item.id } })
}

const filingData = ref({
  id: "" as string,
  type: "" as string,
  contract_id: "" as string,
  status: "" as string,
})

const getData = async () => {
  loading.getData = true
  const { data, response } = await useAxios(`/filing/showData/${filing_id}`).get()
  if (response.status==200 && data) {
    filingData.value = data
  }
  loading.getData = false
}

//ModalSupportFiles
const refModalSupportFiles = ref()

const openModalSupportFiles = (item: any) => {
  refModalSupportFiles.value.openModal(item)
}

//ModalShowFiles
const refModalShowFiles = ref()

const openModalShowFiles = (item: any) => {
  refModalShowFiles.value.openModal(item.id, "FilingInvoice", {
    status: item.status,
  })
}

const echoChannel = () => {

  refTableFull.value.options.tableData.forEach(element => {
    window.Echo.channel(`filing_invoice.${element.id}`)
      .listen('.FilingInvoiceRowUpdated', (event: any) => {

        element.files_count = event.files_count

        element.status_xml = event.status_xml
        element.status_xml_backgroundColor = event.status_xml_backgroundColor
        element.status_xml_description = event.status_xml_description

        element.path_xml = event.path_xml

      });
  });
}

//ModalSupportMasiveFiles
const refModalSupportMasiveFiles = ref()

const openModalSupportMasiveFiles = () => {
  refModalSupportMasiveFiles.value.openModal(filing_id)
}

//ModalUploadFileXml
const refModalUploadFileXml = ref()

const openModalUploadFileXml = (item: any) => {
  refModalUploadFileXml.value.openModal(item)
}

//ModalXmlMasiveFiles
const refModalXmlMasiveFiles = ref()

const openModalXmlMasiveFiles = () => {
  refModalXmlMasiveFiles.value.openModal(filing_id)
}

//ModalErrorsFiling
const refModalErrorsFiling = ref()

const openModalErrorsFiling = () => {
  refModalErrorsFiling.value.openModal(filing_id)
}

//ModalErrorsFilingInvoice
const refModalErrorsFilingInvoice = ref()

const openModalErrorsFilingInvoice = (item) => {
  refModalErrorsFilingInvoice.value.openModal(item)
}

//descarga de XML
const downloadFileData = async (file: any) => {
  const filePath = storageBack(file);
  descargarArchivo(filePath);
};

//uploadMoreInvoices
const uploadMoreInvoices = () => {
  if (filingData.value.type == 'FILING_TYPE_001') {

    openModalUploadFileZip()

  } else if (filingData.value.type == 'FILING_TYPE_002') {

    openModalUploadFileJson()

  }
}

//ModalUploadFileZip
const refModalUploadFileZip = ref()
const openModalUploadFileZip = () => {

  refModalUploadFileZip.value.openModal(filingData.value)
}


//ModalUploadFileJson
const refModalUploadFileJson = ref()

const openModalUploadFileJson = () => {
  refModalUploadFileJson.value.openModal(filingData.value)
}


const reloadTable = () => {
  refTableFull.value.fetchTableData()
}

const deleteFilingInvoice = (id: string) => {
  refTableFull.value.openDeleteModal(id);
}

//CountAllDataInvoices
const refCountAllDataInvoices = ref()
const refreshCountAllDataInvoices = () => {
  refCountAllDataInvoices.value.fetchCountData();
}


const tableLoading = ref(false); // Estado de carga de la tabla

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(null, false, true); // Forzamos la búsqueda
  }
};

window.Echo.channel(`filing.${filing_id}`)
  .listen('FilingRowUpdatedNow', (event: any) => {
    filingData.value.status = event.status
  });
</script>

<template>
  <div>
    <CountAllDataInvoices ref="refCountAllDataInvoices" :filing_id="filing_id" />

    <VCard class="mt-5">
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Lista de Facturas
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
          <ProgressCircularChannel :channel="'filingSupport.' + filing_id" tooltipText="Subiendo soportes masivos" />
          <ProgressCircularChannel :channel="'filingXml.' + filing_id" tooltipText="Subiendo XML masivos" />

          <VBtn :loading="loading.finishFiling" :disabled="loading.finishFiling" color="primary" @click="finishFiling" v-if="filingData.status == 'FILING_EST_008'">
            Finalizar radicación
          </VBtn>
          <VBtn color="primary">
            <template #append>
              <VIcon icon="tabler-chevron-compact-down"></VIcon>
            </template>
            Más opciones
            <VMenu activator="parent">
              <VList>
                <VListItem @click="uploadMoreInvoices()" v-if="filingData.status == 'FILING_EST_008'">Subir más facturas</VListItem>
                <VListItem @click="openModalSupportMasiveFiles()" v-if="filingData.status == 'FILING_EST_008'">Subir soportes masivo</VListItem>
                <VListItem @click="openModalXmlMasiveFiles()" v-if="filingData.status == 'FILING_EST_008'">Subir XML masivo</VListItem>
                <VListItem @click="() => { }">Descargar certificacion de radicación</VListItem>
                <VListItem @click="() => { }">Descargar CSV de radicación</VListItem>
                <VListItem @click="openModalErrorsFiling()">Ver inconsistencias</VListItem>
              </VList>
            </VMenu>
          </VBtn>
        </div>
      </VCardTitle>

      <VCardText>
        <FilterDialogNew :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
          <template #default="{ extraFilters }">
            <VCol cols="3">
              <AppTextField prepend-inner-icon="tabler-search" v-model="extraFilters.files_count.value"
                label="Soportes cargados" placeholder="Filtrar por soportes cargados" type="text" name="files_count" clearable />
            </VCol>
          </template>
        </FilterDialogNew>
      </VCardText>

      <!-- @dataFilter="returnFilter" -->

      <VCardText class=" mt-2">
        <TableFullNew ref="refTableFull" :options="optionsTable" @dataFetched="echoChannel"
          @deleteSuccess="refreshCountAllDataInvoices()" @update:loading="tableLoading = $event">

          <template #item.actions="{ item }">
            <div class="d-flex justify-end gap-3">

              <ProgressCircularChannel :channel="'filing_invoice.' + item.id"
                tooltipText="Subiendo soportes a la factura" />

              <VBtn icon color="primary">
                <VIcon icon="tabler-square-rounded-chevron-down"></VIcon>
                <VMenu activator="parent">
                  <VList>

                    <VListItem v-if="item.status != 'FILINGINVOICE_EST_002'" @click="openModalSupportFiles(item)">Subir
                      soportes</VListItem>

                    <VListItem v-if="item.files_count > 0" @click="openModalShowFiles(item)">Ver soportes</VListItem>

                    <VListItem
                      v-if="item.status != 'FILINGINVOICE_EST_002' && item.status_xml != 'FILINGINVOICE_EST_003'"
                      @click="openModalUploadFileXml(item)">
                      Subir XML
                    </VListItem>

                    <VListItem @click="goViewUsers(item)">Ver usuarios</VListItem>

                    <VListItem v-if="item.status_xml == 'FILINGINVOICE_EST_003'"
                      @click="downloadFileData(item.path_xml)">
                      Descargar XML
                    </VListItem>

                    <VListItem v-if="item.status != 'FILINGINVOICE_EST_002'" @click="deleteFilingInvoice(item.id)">
                      Eliminar factura
                    </VListItem>

                    <VListItem @click="openModalErrorsFilingInvoice(item)">Ver inconsistencias</VListItem>

                  </VList>
                </VMenu>
              </VBtn>

            </div>
          </template>

          <template #item.status="{ item }">
            <div>
              <VChip :color="item.status_backgroundColor">{{ item.status_description }}</VChip>
            </div>
          </template>
          <template #item.status_xml="{ item }">
            <div>
              <VChip :color="item.status_xml_backgroundColor">{{ item.status_xml_description }}</VChip>
            </div>
          </template>

        </TableFullNew>
      </VCardText>
    </VCard>

    <ModalQuestion ref="refModalQuestion" @success="responseFinishFiling" />
    <ModalQuestion ref="refModalQuestionResponseFinishFiling" />

    <ModalSupportFiles ref="refModalSupportFiles" />
    <ModalShowFiles ref="refModalShowFiles" />
    <ModalSupportMasiveFiles ref="refModalSupportMasiveFiles" />
    <ModalUploadFileXml ref="refModalUploadFileXml" />
    <ModalXmlMasiveFiles ref="refModalXmlMasiveFiles" />

    <ModalUploadFileZip ref="refModalUploadFileZip" @reloadTable="reloadTable()" />
    <ModalUploadFileJson ref="refModalUploadFileJson" @reloadTable="reloadTable()"
      @refreshCountAllDataInvoices="refreshCountAllDataInvoices()" />

    <ModalErrorsFiling ref="refModalErrorsFiling" />
    <ModalErrorsFilingInvoice ref="refModalErrorsFilingInvoice" />

  </div>
</template>
