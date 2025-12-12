<script setup lang="ts">
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { VForm } from "vuetify/components/VForm";

const authenticationStore = useAuthenticationStore();
const { company } = storeToRefs(authenticationStore)

const errorsBack = ref<IErrorsBack>({});
const refForm = ref<VForm>();
const emit = defineEmits(["execute"])

const titleModal = ref<string>("Crear contrato")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)
const third_arrayInfo = ref([]);

const loading = reactive({
  form: false,
})

const form = ref({
  id: null as null | string,
  company_id: null as null | string,
  name: null as null | string,
  third_id: null as null | string,
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

  titleModal.value = id ? "Editar contrato" : "Crear contrato"

  form.value.id = id
  form.value.company_id = company.value.id
    await fetchDataForm();
};

const fetchDataForm = async () => {

  const url = form.value.id ? `/contract/${form.value.id}/edit` : `/contract/create`

  loading.form = true
  const { data, response } = await useAxios(url).get({
    params: {
      company_id: authenticationStore.company.id
    }
  });
  loading.form = false

  if (response.status == 200 && data) {
    if(data.form){
      form.value = cloneObject(data.form);
    }

    third_arrayInfo.value = data.third_arrayInfo;

  }
}

const submitForm = async () => {
  const validation = await refForm.value?.validate();

  if (validation?.valid) {

    const url = form.value.id ? `/contract/update/${form.value.id}` : `/contract/store`

    loading.form = true
    const { response, data } = await useAxios(url).post(form.value);

    if (response.status == 200 && data) {
      emit("execute")
      handleDialogVisible()
    }

    if (data.code == 422) {
      errorsBack.value = data.errors ?? {}
    }

    loading.form = false
  }
}

// Computed que verifica si al menos uno de los valores es true
const isLoading = computed(() => {
  return Object.values(loading).some(value => value);
});

defineExpose({
  openModal
})

</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" max-width="40rem" transition="dialog-transition"
      persistent>
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
                <AppTextField :requiredField="true" clearable :disabled="disabledFiledsView" label="Nombre"
                  :rules="[requiredValidator]" v-model="form.name" :error-messages="errorsBack.name"
                  @input="errorsBack.name = ''" />
              </VCol>

              <VCol cols="12">
                <AppSelectRemote v-model="form.third_id" url="selectInfiniteThird" arrayInfo="third" label="Tercero"
                  :params="{ company_id: company?.id }" :requiredField="true"
                  :rules="[requiredValidator]" :error-messages="errorsBack.third_id" clearable
                  :disabled="disabledFiledsView" :firstFetch="false" :itemsData="third_arrayInfo"/>
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
