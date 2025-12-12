<script setup lang="ts">
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { VForm } from "vuetify/components/VForm";

const { toast } = useToast()

const authenticationStore = useAuthenticationStore();
const emit = defineEmits(["execute"]);

// Referencias
const errorsBack = ref<IErrorsBack>({});

const titleModal = ref<string>("Cambiar estado de la conciliación")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)
const isLoading = ref<boolean>(false)
const statusReconciliationGroupEnum = ref<String[]>([])
const refForm = ref<VForm>();

const form = ref({
  reason: null as null | string,
  status: null as null | string,
  company_id: null as null | string,
  user_id: null as null | string,
  reconciliation_group_id: null as null | string,
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
  handleDialogVisible();
  getData()

  form.value.reconciliation_group_id = reconciliation_group_id
};

const submitForm = async () => {
  const validation = await refForm.value?.validate();

  if (validation?.valid) {

    form.value.company_id = authenticationStore.company.id;
    form.value.user_id = authenticationStore.user.id;

    isLoading.value = true;
    const { response, data } = await useAxios(`/conciliation/changeStatus/save`).post(form.value);
    isLoading.value = false;

    if (response.status == 200 && data) {

      const statusData = statusReconciliationGroupEnum.value.find(ele => ele.value == form.value.status)
      console.log("statusData",statusData);
      console.log("statusReconciliationGroupEnum.value",statusReconciliationGroupEnum.value);
      console.log("form.value.reconciliation_group_id",form.value.reconciliation_group_id);
      
      if (statusData) {
        emit("execute", {
          status_description: statusData.title
        })
      }
      handleDialogVisible()
    }
  }
  else {
    toast('Faltan Campos Por Diligenciar', '', 'danger')
  }

};

const getData = async () => {

  isLoading.value = true;
  const { response, data } = await useAxios(`/conciliation/changeStatus/form`).get();
  isLoading.value = false;

  if (response.status == 200 && data) {
    statusReconciliationGroupEnum.value = data.statusReconciliationGroupEnum
  }
};

defineExpose({
  openModal,
  disabledFiledsView,
});


</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" max-width="30rem" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" :disabled="isLoading" class="w-100">
        <VToolbar color="primary">
          <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
        </VToolbar>
        <VCardText>

          <VForm ref="refForm" @submit.prevent="() => { }">
            <VRow>
              <VCol cols="12">
                <AppSelect clearable v-model="form.status" label="Estado" :items="statusReconciliationGroupEnum"
                  :requiredField="true" :rules="[requiredValidator]" :error-messages="errorsBack.status"
                  @input="errorsBack.status = ''" />
              </VCol>
              <VCol cols="12">
                <AppTextarea clearable v-model="form.reason" label="Motivo" :requiredField="true"
                  :rules="[requiredValidator]" :error-messages="errorsBack.reason" @input="errorsBack.reason = ''" />
              </VCol>
            </VRow>
          </VForm>

        </VCardText>
        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn :loading="isLoading" color="secondary" variant="tonal" @click="handleDialogVisible()">Cancelar</VBtn>
          <VBtn :disabled="isLoading" :loading="isLoading" @click="submitForm()" color="primary">Guardar</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

  </div>
</template>
