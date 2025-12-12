<script setup lang="ts">
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import ISelect from "@/interfaces/Axios/ISelect";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { VForm } from "vuetify/components/VForm";
const authenticationStore = useAuthenticationStore();
const emit = defineEmits(["reloadTable"]);
const { toast } = useToast()

// Referencias  
const errorsBack = ref<IErrorsBack>({});

const titleModal = ref<string>("Formulario para acta de conciliación")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)
const isLoading = ref<boolean>(false)
const user_arrayInfo = ref<ISelect[]>([])

const refForm = ref<VForm>();
const form = ref({
  reconciliation_group_id: null as null | string,
  company_id: null as null | string,
  user_id: null as null | string,
  dateConciliation: null as null | string,
  nameIPSrepresentative: null as null | string,
  positionIPSrepresentative: null as null | string,

  elaborator_id: null as null | string,
  elaborator_position: null as null | string,
  reviewer_id: null as null | string,
  reviewer_position: null as null | string,
  approver_id: null as null | string,
  approver_position: null as null | string,
  legal_representative_id: null as null | string,
  legal_representative_position: null as null | string,
  health_audit_director_id: null as null | string,
  health_audit_director_position: null as null | string,
  vp_planning_control_id: null as null | string,
  vp_planning_control_position: null as null | string,
})

const clearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
}
const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const openModal = async (reconciliation_group_id: string) => {
  clearForm()
  form.value.reconciliation_group_id = reconciliation_group_id
  handleDialogVisible();
  getData();

};

const submitForm = async () => {
  const validation = await refForm.value?.validate();

  if (validation?.valid) {

    form.value.company_id = authenticationStore.company.id;
    form.value.user_id = authenticationStore.user.id;


    isLoading.value = true;
    const { response, data } = await useAxios(`/conciliation/generateConciliationReport/save`).post(form.value);
    isLoading.value = false;

    if (response.status == 200 && data) {
    downloadExcelBase64(data.excel, "Acta")

      // handleDialogVisible()
    }

  }
  else {
    toast('Faltan Campos Por Diligenciar', '', 'danger')
  }
};

const paramsSelectUsers = {
      company_id: authenticationStore.company.id

}
const getData = async () => {

  isLoading.value = true;
  const { response, data } = await useAxios(`/conciliation/generateConciliationReport/form`).get({
    params: {
      company_id: authenticationStore.company.id,
      reconciliation_group_id: form.value.reconciliation_group_id 
    }
  });
  isLoading.value = false;

  if (response.status == 200 && data) {
    //select infinitos
    user_arrayInfo.value = data.user_arrayInfo
    
    if(data.form){
      form.value = cloneObject(data.form)
    }
  }

};

defineExpose({
  openModal,
  disabledFiledsView,
});

</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" max-width="60rem" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" :disabled="isLoading" class="w-100">
        <VToolbar color="primary">
          <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
        </VToolbar>
        <VCardText>

          <VForm ref="refForm" @submit.prevent="() => { }">
            <VRow>
              <VCol cols="12">
                <AppTextField type="date" :requiredField="true" label="Fecha Conciliación"
                  v-model="form.dateConciliation" clearable :errorMessages="errorsBack.dateConciliation"
                  @input="errorsBack.dateConciliation = ''" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField :requiredField="true" label="Nombre representante de la IPS : "
                  v-model="form.nameIPSrepresentative" clearable :errorMessages="errorsBack.nameIPSrepresentative"
                  @input="errorsBack.nameIPSrepresentative = ''" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField :requiredField="true" label="Cargo representante de la IPS : "
                  v-model="form.positionIPSrepresentative" clearable
                  :errorMessages="errorsBack.positionIPSrepresentative"
                  @input="errorsBack.positionIPSrepresentative = ''" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" md="6"> 
                <AppSelectRemote :disabled="disabledFiledsView" :requiredField="true" label="Elaboro (Conciliador)"
                  v-model="form.elaborator_id" :errorMessages="errorsBack.elaborator_id"
                  @input="errorsBack.elaborator_id = ''" url="/selectInfiniteUser" arrayInfo="user" clearable
                  :itemsData="user_arrayInfo" :firstFetch="false" :rules="[requiredValidator]" :params="paramsSelectUsers">
                </AppSelectRemote> 
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField :requiredField="true" label="Cargo : " v-model="form.elaborator_position" clearable
                  :errorMessages="errorsBack.elaborator_position" @input="errorsBack.elaborator_position = ''"
                  :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote :disabled="disabledFiledsView"  label="Reviso (Lider de glosas y conciliaciones)"
                  v-model="form.reviewer_id" :errorMessages="errorsBack.reviewer_id"
                  @input="errorsBack.reviewer_id = ''" url="/selectInfiniteUser" arrayInfo="user" clearable
                  :itemsData="user_arrayInfo" :firstFetch="false" :params="paramsSelectUsers">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField  label="Cargo : " v-model="form.reviewer_position" clearable
                  :errorMessages="errorsBack.reviewer_position" @input="errorsBack.reviewer_position = ''"
                  />
              </VCol>
              <VCol cols="12" md="6">
                 <AppSelectRemote :disabled="disabledFiledsView"  label="Aprobó (Coordinador de glosas y Conciliaciones)"
                  v-model="form.approver_id" :errorMessages="errorsBack.approver_id"
                  @input="errorsBack.approver_id = ''" url="/selectInfiniteUser" arrayInfo="user" clearable
                  :itemsData="user_arrayInfo" :firstFetch="false" :params="paramsSelectUsers">
                </AppSelectRemote> 
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField  label="Cargo : " v-model="form.approver_position" clearable
                  :errorMessages="errorsBack.approver_position" @input="errorsBack.approver_position = ''"
                  />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote :disabled="disabledFiledsView"  label="Aprobó (Representante Legal / Director Nacional de Cuentas Medicas)"
                  v-model="form.legal_representative_id" :errorMessages="errorsBack.legal_representative_id"
                  @input="errorsBack.legal_representative_id = ''" url="/selectInfiniteUser" arrayInfo="user" clearable
                  :itemsData="user_arrayInfo" :firstFetch="false" :params="paramsSelectUsers">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField  label="Cargo : " v-model="form.legal_representative_position"
                  clearable :errorMessages="errorsBack.legal_representative_position"
                  @input="errorsBack.legal_representative_position = ''" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote :disabled="disabledFiledsView"   label="Revisión por Director de auditoria en Salud"
                  v-model="form.health_audit_director_id" :errorMessages="errorsBack.health_audit_director_id"
                  @input="errorsBack.health_audit_director_id = ''" url="/selectInfiniteUser" arrayInfo="user" clearable
                  :itemsData="user_arrayInfo" :firstFetch="false" :params="paramsSelectUsers">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField  label="Cargo : " v-model="form.health_audit_director_position"
                  clearable :errorMessages="errorsBack.health_audit_director_position"
                  @input="errorsBack.health_audit_director_position = ''" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote :disabled="disabledFiledsView"   label="Revisión por Vicepresidencia de Planeación y Control Financiero"
                  v-model="form.vp_planning_control_id" :errorMessages="errorsBack.vp_planning_control_id"
                  @input="errorsBack.vp_planning_control_id = ''" url="/selectInfiniteUser" arrayInfo="user" clearable
                  :itemsData="user_arrayInfo" :firstFetch="false" :params="paramsSelectUsers">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField  label="Cargo : " v-model="form.vp_planning_control_position"
                  clearable :errorMessages="errorsBack.vp_planning_control_position"
                  @input="errorsBack.vp_planning_control_position = ''" />
              </VCol>
            </VRow>
          </VForm>

        </VCardText>
        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn :loading="isLoading" color="secondary" variant="tonal" @click="handleDialogVisible()">Cancelar</VBtn>
          <VBtn :disabled="isLoading" :loading="isLoading" @click="submitForm()" color="primary">Guardar y generar</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

  </div>
</template>
