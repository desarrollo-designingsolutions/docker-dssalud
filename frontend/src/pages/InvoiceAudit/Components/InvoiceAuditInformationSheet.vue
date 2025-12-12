<script setup lang="ts">
import ModalFormMasiveGlosa from "@/pages/Glosa/Components/ModalFormMasive.vue";
import ModalListGlosa from "@/pages/Glosa/Components/ModalList.vue";
import ModalErrorsGlosas from "@/pages/InvoiceAudit/Components/ModalErrorsGlosas.vue";
import ModalShowFiles from "@/pages/InvoiceAudit/Components/ModalShowFiles.vue";
import ModalUploadGlosaFileCsv from "@/pages/InvoiceAudit/Components/ModalUploadGlosaFileCsv.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { useRouter } from 'vue-router';

const { toast } = useToast();

definePage({
  path: "InvoiceAuditInformationSheet/invoice-audit/:assignment_batch_id/:third_id/:invoice_audit_id/:patient_id",
  name: "InvoiceAuditInformationSheet-Info",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "invoiceAuditAssignmentBatche.list",
  },
});

const showBtnsView = ref(true);
const invoiceAudit = ref({});
const assignment = ref({});
const third = ref({});
const patient = ref({});
const value_glosa = ref(0);
const value_approved = ref(0);

const authenticationStore = useAuthenticationStore();

const router = useRouter();

const route = useRoute();

const assignment_batch_id = route.params.assignment_batch_id;
const third_id = route.params.third_id;
const invoice_audit_id = route.params.invoice_audit_id;
const patient_id = route.params.patient_id;

//LOADING
const isLoading = ref<boolean>(false)
const isLoadingExcelList = ref<boolean>(false)
const isLoadingSuccessFinalizedAudit = ref<boolean>(false)
const isLoadingSuccessReturnAudit = ref<boolean>(false)

//FILTER
const optionsFilter = ref({
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

const goViewPatient = (data: any = { id: null }) => {

  router.push({ name: "InvoiceAuditPatient-List", params: { assignment_batch_id: assignment_batch_id, third_id: third_id, invoice_audit_id: invoice_audit_id } })

}

const fetchData = async () => {

  const url = `/invoiceAudit/getInformationSheet/${third_id}/${invoice_audit_id}/${patient_id}`

  isLoading.value = true
  const { data, response } = await useAxios(url).get({
    params: {
      company_id: authenticationStore.company.id,
      user_id: authenticationStore.user.id,
    }
  });

  if (response.status == 200 && data) {
    invoiceAudit.value = data.data.invoice_audit
    third.value = data.data.third
    patient.value = data.data.patient
    value_glosa.value = data.data.value_glosa
    value_approved.value = data.data.value_approved
    assignment.value = data.assignment

    if (assignment.value && (assignment.value.status == 'ASSIGNMENT_EST_003' || assignment.value.status == 'ASSIGNMENT_EST_004')) {
      showBtnsView.value = false
    } else {
      showBtnsView.value = true
    }
  }
  isLoading.value = false
}

onMounted(() => {
  fetchData();
})


//TABLE
const refTableFull = ref()

const optionsTable = {
  url: `/invoiceAudit/getServices/${invoice_audit_id}/${patient_id}`,
  headers: [ 
    { key: 'detail_code', title: 'Código' },
    { key: 'description', title: 'Descripcion' },
    { key: 'quantity', title: 'Cantidad' },
    { key: "unit_value", title: 'Valor Unitario' },
    { key: "moderator_value", title: 'Valor Moderadora' },
    { key: "total_value", title: 'Valor Total' },
    { key: 'actionsBtn', title: 'Acciones', sortable: false },
  ],
  showSelect: true,
  paramsGlobal: {
    company_id: authenticationStore.company.id,
    user_id: authenticationStore.user.id,
  },
}

const tableLoading = ref(false); // Estado de carga de la tabla

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(null, false, true); // Forzamos la búsqueda
  }
};

//ModalShowFiles
const refModalShowFiles = ref()

const openModalShowFiles = () => {
  refModalShowFiles.value.openModal(invoice_audit_id, "InvoiceAudit")
}

//ModalUploadGlosaFileCsv
const refModalUploadGlosaFileCsv = ref()
const openModalUploadGlosaFileCsv = () => {
  refModalUploadGlosaFileCsv.value.openModal({
    assignment_batch_id: assignment_batch_id,
    third_id: third_id,
    invoice_audit_id: invoice_audit_id,
    patient_id: patient_id,
    user_id: authenticationStore.user.id,
  })
}


const { downloadExcel, isLoadingExcel } = useGlosaInportExport()

const downloadExport = async () => {
  downloadExcel({
    invoice_audit_id: invoice_audit_id,
    patient_id: patient_id,
    user_id: authenticationStore.user.id,
    from: "InvoiceAuditInformationSheet",
  })
}

const downloadExcelList = async () => {
  isLoadingExcelList.value = true;
  const { data, response } = await useAxios("/invoiceAudit/exportListServicesExcel").post({
    invoice_audit_id: invoice_audit_id,
    patient_id: patient_id
  })
  isLoadingExcelList.value = false;

  if (response.status == 200 && data && data.code == 200) {
    downloadExcelBase64(data.excel, "Listado de Servicios Factura: " + invoiceAudit.value?.invoice_number)
  }
}

//ModalFormMasiveGlosa
const refModalFormMasiveGlosa = ref()

const openModalFormMasiveGlosa = () => {
  if (servicesIds.value.length > 0) {
    refModalFormMasiveGlosa.value.openModal(servicesIds.value)
  } else {
    toast("Debe seleccionar almenos un servicio", "", "info")
  }
}

//ModalListGlosa
const refModalListGlosa = ref()

const openModalListGlosa = (data: any) => {
  refModalListGlosa.value.openModal({
    ...data,
    showBtnsView: showBtnsView.value
  })
}

const servicesIds = ref<Array<string>>([]);

const channels = reactive({
  invoiceAuditData: `invoice_audit.${invoice_audit_id}`,
  glosa_service_jobs: `glosa_service_jobs.${authenticationStore.user.id}`,
})

const channelInvoiceAuditData = window.Echo.channel(channels.invoiceAuditData);
channelInvoiceAuditData.listen('ChangeInvoiceAuditData', (event: any) => {

  value_glosa.value = event.data.value_glosa
  value_approved.value = event.data.value_approved
});



//ModalQuestion
const refModalQuestion = ref()

const openModalQuestion = () => {
  if (refModalQuestion.value) {
    refModalQuestion.value.componentData.isDialogVisible = true;
    refModalQuestion.value.componentData.btnSuccessText = 'Si';
    refModalQuestion.value.componentData.btnCancelText = 'No';
    refModalQuestion.value.componentData.title = '¿Esta seguro que deseea finalizar la auditoria?';
  }
}

const successFinalizedAudit = async () => {
  isLoadingSuccessFinalizedAudit.value = true;
  const { data, response } = await useAxios("/invoiceAudit/successFinalizedAudit").post({
    assignments_ids: [assignment.value.id],
  })
  isLoadingSuccessFinalizedAudit.value = false;

  if (response.status == 200 && data && data.code == 200) {
    showBtnsView.value = false
  }
}

//ModalQuestionReturnt
const refModalQuestionReturn = ref()

const openModalQuestionReturn = () => {
  if (value_glosa.value == invoiceAudit.value.total_value) {
    if (refModalQuestionReturn.value) {
      refModalQuestionReturn.value.componentData.isDialogVisible = true;
      refModalQuestionReturn.value.componentData.btnSuccessText = 'Si';
      refModalQuestionReturn.value.componentData.btnCancelText = 'No';
      refModalQuestionReturn.value.componentData.title = '¿Esta seguro que deseea hacer devolución de la factura?';
    }
  } else {
    toast("La factura debe estar totalmente glosada para hacer la devolucion", "", "info")
    return
  }

}

const successReturnAudit = async () => {
  isLoadingSuccessReturnAudit.value = true;
  const { data, response } = await useAxios("/invoiceAudit/successReturnAudit").post({
    assignments_ids: [assignment.value.id],
  })
  isLoadingSuccessReturnAudit.value = false;

  if (response.status == 200 && data && data.code == 200) {
    showBtnsView.value = false
  }
}

const isLoadingBtn = computed(() => {

  let loading = [
    isLoadingExcel.value,
    isLoadingSuccessFinalizedAudit.value,
    isLoadingSuccessReturnAudit.value,
    isLoadingExcelList.value,
  ]

  return Object.values(loading).some(value => value);
});
</script>

<template>
  <div>
    <VRow class="mb-4">
      <VCol cols="12" sm="8">
        <h1 class="text-h2 font-weight-bold">Factura: {{ invoiceAudit.invoice_number }}</h1>
      </VCol>
      <VCol cols="12" sm="4" class="d-flex justify-sm-end">
        <VBtn @click="goViewPatient">Regresar</VBtn>
      </VCol>
    </VRow>

    <VCard :loading="isLoading">
      <template v-slot:prepend>
        <div class="text-h6">Información de Factura</div>
      </template>
      <template v-slot:append>
        <div class="text-h5 font-weight-bold text-primary">
          {{ invoiceAudit.total_value }}
        </div>
      </template>
      <VCardText class="mt-2">
        <div class="mb-4">
          <VRow>
            <VCol cols="12" sm="6" md="3">
              <div>N° de radicación</div>
              <div></div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>Fecha Radicación</div>
              <div></div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>N° Factura</div>
              <div>{{ invoiceAudit.invoice_number }}</div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>Fecha Factura</div>
              <div>{{ invoiceAudit.date_entry }}</div>
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12" sm="6" md="3">
              <div>Nota Credito</div>
              <div></div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>Valor Copago</div>
              <div></div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>Valor Cuota Moderada</div>
              <div></div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>Valor Pago Moderador</div>
              <div></div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>Valor Aprobado</div>
              <div>{{ value_approved }}</div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>Valor Glosado</div>
              <div>{{ value_glosa }}</div>
            </VCol>
          </VRow>
        </div>
      </VCardText>
    </VCard>

    <VExpansionPanels variant="accordion" class="my-6">
      <VExpansionPanel>
        <VExpansionPanelTitle>
          Información Prestador
        </VExpansionPanelTitle>
        <VExpansionPanelText>
          <VRow>
            <VCol cols="12" sm="6" md="3">
              <div>Razón Social</div>
              <div>{{ third.name }}</div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>NIT</div>
              <div>{{ third.nit }}</div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>Código Habilitación</div>
              <div></div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>Sede</div>
              <div></div>
            </VCol>
          </VRow>
        </VExpansionPanelText>
      </VExpansionPanel>

      <VExpansionPanel>
        <VExpansionPanelTitle>
          Información Paciente
        </VExpansionPanelTitle>
        <VExpansionPanelText>
          <VRow>
            <VCol cols="12" sm="6" md="3">
              <div>Nombre</div>
              <div>{{ patient.full_name }}</div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>Documento</div>
              <div>{{ patient.identification_number }}</div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>Edad</div>
              <div></div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>Fecha de Ingreso</div>
              <div></div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>Fecha de Egreso</div>
              <div></div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div>Días Estancia</div>
              <div></div>
            </VCol>
          </VRow>
        </VExpansionPanelText>
      </VExpansionPanel>
    </VExpansionPanels>

    <VCard>
      <VCardItem class="d-flex justify-center">
        <div class="text-h4">Gestión Cuenta</div>
      </VCardItem>

      <VCardText>
        <VRow class="mb-2">
          <VCol cols="12" class="d-flex justify-center">

            <VBtn class="me-2 mb-2" @click="openModalShowFiles">
              <template #prepend>
                <VIcon start icon="tabler-files" />
              </template>
              Soportes
            </VBtn>
            <VBtn class="me-2 mb-2" v-if="showBtnsView" @click="openModalFormMasiveGlosa">
              <template #prepend>
                <VIcon start icon="tabler-folder" />
              </template>
              Glosa Masiva
            </VBtn>

            <VBtn color="primary" class="mb-2" append-icon="tabler-chevron-down" :loading="isLoadingBtn">
              Más Acciones
              <VMenu activator="parent">
                <VList>
                  <VListItem v-if="showBtnsView" @click="openModalUploadGlosaFileCsv()">
                    <template #prepend>
                      <VIcon start icon="tabler-file-upload" />
                    </template>
                    <span>Importar</span>
                  </VListItem>
                  <VListItem v-if="showBtnsView" @click="downloadExport()" :loading="isLoadingExcel"
                    :disabled="isLoadingExcel">
                    <template #prepend>
                      <VIcon start icon="tabler-file-download" />
                    </template>
                    <span>Exportar</span>
                  </VListItem>
                  <VListItem @click="downloadExcelList()" :loading="isLoadingExcelList" :disabled="isLoadingExcelList">
                    <template #prepend>
                      <VIcon start icon="tabler-download" />
                    </template>
                    <span>Descargar</span>
                  </VListItem>

                  <VListItem v-if="showBtnsView" @click="openModalQuestionReturn()">
                    <template #prepend>
                      <VIcon start icon="tabler-file-download" />
                    </template>
                    <span>Devolución</span>
                  </VListItem>

                  <VListItem v-if="showBtnsView" @click="openModalQuestion()">
                    <template #prepend>
                      <VIcon start icon="tabler-file-download" />
                    </template>
                    <span>Finalizar auditoria</span>
                  </VListItem>

                </VList>
              </VMenu>
            </VBtn>

          </VCol>
        </VRow>

        <FilterDialogNew :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
        </FilterDialogNew>

        <TableFullNew v-model:selected="servicesIds" ref="refTableFull" :options="optionsTable"
          @update:loading="tableLoading = $event">

          <template #item.actionsBtn="{ item }">
            <VBtn color="primary" append-icon="tabler-chevron-down">
              Acciones
              <VMenu activator="parent">

                <VList>
                  <VListItem @click="openModalListGlosa(item)">
                    <template #prepend>
                      <VIcon size="22" icon="tabler-square-rounded-arrow-right" />
                    </template>
                    <span>Listado Glosas</span>
                  </VListItem>
                </VList>
              </VMenu>
            </VBtn>
          </template>

        </TableFullNew>

      </VCardText>
    </VCard>

    <ModalShowFiles ref="refModalShowFiles"></ModalShowFiles>

    <ModalUploadGlosaFileCsv ref="refModalUploadGlosaFileCsv" />

    <ModalFormMasiveGlosa ref="refModalFormMasiveGlosa"></ModalFormMasiveGlosa>

    <ModalListGlosa ref="refModalListGlosa"></ModalListGlosa>

    <ModalErrorsGlosas ref="refModalErrorsGlosas" />

    <ModalQuestion ref="refModalQuestion" @success="successFinalizedAudit" />

    <ModalQuestion ref="refModalQuestionReturn" @success="successReturnAudit" />
  </div>
</template>
