<script setup lang="ts">
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import type { VForm } from "vuetify/components/VForm";

import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
const authenticationStore = useAuthenticationStore();
const { company } = storeToRefs(authenticationStore)

const errorsBack = ref<IErrorsBack>({});
const refForm = ref<VForm>();
const emit = defineEmits(["execute"])

const titleModal = ref<string>("Crear paquete")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)

const isLoading = ref<boolean>(false)

const form = ref({
  id: null as null | string,
  description: null as null | string,
  due_date: null as null | string,
  company_id: null as null | string,
})

const handleClearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
}

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const openModal = async (id: string | null = null, disabled: boolean = false) => {
  disabledFiledsView.value = disabled

  handleClearForm()
  handleDialogVisible();

  titleModal.value = id ? "Editar paquete" : "Crear paquete"

  form.value.id = id
  if (form.value.id) {
    await fetchDataForm();
  }
};

const fetchDataForm = async () => {

  const url = form.value.id ? `/assignmentBatche/${form.value.id}/edit` : `/assignmentBatche/create`

  isLoading.value = true
  const { data, response } = await useApi<any>(
    createUrl(url, {
      query: {
        company_id: company.value.id
      },
    })
  );

  if (response.value?.ok && data.value) {
    if (data.value.form) {
      form.value = cloneObject(data.value.form)
    }
  }
  isLoading.value = false
}


const submitForm = async () => {
  const validation = await refForm.value?.validate();


  if (validation?.valid) {

    form.value.company_id = authenticationStore.company.id;

    const url = form.value.id ? `/assignmentBatche/update/${form.value.id}` : `/assignmentBatche/store`

    isLoading.value = true
    const { response, data } = await useApi(url).post(form.value);

    if (response.value?.ok && data.value) {
      emit("execute")
      await handleDialogVisible()
    }
    if (data.value.code === 422) errorsBack.value = data.value.errors ?? {};
    isLoading.value = false
  }
}

defineExpose({
  openModal
})

</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" max-width="40rem" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" :disabled="isLoading" class="w-100">
        <!-- Toolbar -->
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
          </VToolbar>
        </div>

        <VCardText>
          <VForm ref="refForm" @submit.prevent="() => { }">

            <VRow>
              <VCol cols="12">
                <AppTextField :requiredField="true" clearable :disabled="disabledFiledsView" label="Descripcion"
                  :rules="[requiredValidator]" v-model="form.description" :error-messages="errorsBack.name"
                  @input="errorsBack.name = ''" />
              </VCol>

              <VCol cols="12">
                <AppDateTimePicker :requiredField="true" clearable :disabled="disabledFiledsView"
                  label="Fecha de vencimiento" :rules="[requiredValidator]" v-model="form.due_date"
                  :error-messages="errorsBack.due_date" @input="errorsBack.due_date = ''" />
              </VCol>
            </VRow>

          </VForm>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap mt-5">
          <VBtn color="secondary" :disabled="isLoading" :loading="isLoading" @click="handleDialogVisible">Cancelar
          </VBtn>
          <VBtn v-if="!disabledFiledsView" :disabled="isLoading" :loading="isLoading" @click="submitForm()"
            color="primary">
            Guardar
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
