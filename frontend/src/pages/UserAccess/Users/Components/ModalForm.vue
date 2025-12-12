<script setup lang="ts">
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import type { VForm } from "vuetify/components/VForm";

import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
const authenticationStore = useAuthenticationStore();
const { company } = storeToRefs(authenticationStore)

const errorsBack = ref<IErrorsBack>({});
const refForm = ref<VForm>();
const emit = defineEmits(["execute"])

const titleModal = ref<string>("Crear usuario")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)
const third_arrayInfo = ref<Array<object>>([])

const isLoading = ref<boolean>(false)

const form = ref({
  id: null as null | string,
  name: null as null | string,
  surname: null as null | string,
  email: null as null | string,
  password: null as null | string,
  confirmedPassword: null as null | string,
  role_id: null as null | string,
  company_id: null as null | string,
  third_id: null as null | string,
  thirds_id: null as null | string,
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

  titleModal.value = id ? "Editar usuario" : "Crear usuario"

  form.value.id = id
  form.value.company_id = company.value.id
  await fetchDataForm();
};

const roles = ref([])
const companies = ref([])
const fetchDataForm = async () => {

  const url = form.value.id ? `/user/${form.value.id}/edit` : `/user/create`

  isLoading.value = true
  const { data, response } = await useApi<any>(
    createUrl(url, {
      query: {
        company_id: company.value.id
      },
    })
  );
  isLoading.value = false

  if (response.value?.ok && data.value) {
    roles.value = data.value.roles
    companies.value = data.value.companies

    if (data.value.form) {
      form.value = data.value.form
    }

    if (data.value.third_arrayInfo) {
      third_arrayInfo.value = data.value.third_arrayInfo
    }
  }
}


const submitForm = async () => {
  const validation = await refForm.value?.validate();


  if (validation?.valid) {
    const url = form.value.id ? `/user/update/${form.value.id}` : `/user/store`

    isLoading.value = true
    const { response, data } = await useApi(url).post(form.value);
    isLoading.value = false

    if (response.value?.ok && data.value) {
      emit("execute")
      handleDialogVisible()
    }
    if (data.value.code === 422) errorsBack.value = data.value.errors ?? {};
  }
}

const rulesFieldPassword = computed(() => {
  if (!form.value.id) {
    return [
      value => requiredValidator(value),
    ]
  }
  return []
})
const rulesFieldConfirmedPassword = computed(() => {
  if (!form.value.id) {
    return [
      value => requiredValidator(value),
      value => confirmedValidator(form.value.password, value),
    ]
  }
  return [
    value => confirmedValidator(form.value.password, value),
  ]
})

defineExpose({
  openModal
})

const showThirdField = computed(() => {
  const selectedRole = roles.value.find(role => role.value === form.value.role_id);
  if (!selectedRole) return false;
  return selectedRole.type.includes("ROLE_TYPE_001");
});

const showUserThirdsField = computed(() => {
  const selectedRole = roles.value.find(role => role.value === form.value.role_id);
  if (!selectedRole) return false;
  return selectedRole.type.includes("ROLE_TYPE_002");
});

const clearRole = () => {
  errorsBack.value.role_id = ''

  form.value.third_id = null
  form.value.thirds_id = null
}
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
                <AppTextField :requiredField="true" clearable :disabled="disabledFiledsView" label="Nombres"
                  :rules="[requiredValidator]" v-model="form.name" :error-messages="errorsBack.name" />
              </VCol>
              <VCol cols="12">
                <AppTextField :requiredField="true" clearable :disabled="disabledFiledsView" label="Apellidos"
                  :rules="[requiredValidator]" v-model="form.surname" :error-messages="errorsBack.surname" />
              </VCol>

              <VCol cols="12">
                <AppTextField :requiredField="true" clearable :disabled="disabledFiledsView" label="Correo" type="email"
                  :rules="[requiredValidator, emailValidator]" v-model="form.email" :error-messages="errorsBack.email"
                  @input="errorsBack.email = ''" />
              </VCol>

              <VCol cols="12">
                <AppTextField :requiredField="form.id ? false : true" clearable :disabled="disabledFiledsView" label="Contraseña"
                  type="password" :rules="rulesFieldPassword" v-model="form.password"
                  :error-messages="errorsBack.password" />
              </VCol>

              <VCol cols="12">
                <AppTextField :requiredField="form.id ? false : true" label="Confirmar Contraseña" type="password"
                  :rules="rulesFieldConfirmedPassword" v-model="form.confirmedPassword" clearable />
              </VCol>
              <VCol cols="12">
                <AppSelect :requiredField="true" :items="roles" label="Rol" :rules="[requiredValidator]"
                  v-model="form.role_id" :error-messages="errorsBack.role_id" clearable @update:modelValue="clearRole()"
                  :disabled="disabledFiledsView" />
              </VCol>
              <VCol cols="12" v-if="showThirdField">
                <AppSelectRemote v-model="form.third_id" url="selectInfiniteThird" arrayInfo="third" label="Terceros"
                  :params="{ company_id: company?.id }" :requiredField="true"
                  :rules="[showThirdField ? requiredValidator : null]" :error-messages="errorsBack.third_id" clearable
                  :disabled="disabledFiledsView" :itemsData="third_arrayInfo" :firstFetch="false" />
              </VCol>
              
              <VCol cols="12" v-if="showUserThirdsField">
                <AppSelectRemote v-model="form.thirds_id" url="selectInfiniteThird" arrayInfo="third" label="Terceros"
                  :params="{ company_id: company?.id }" :requiredField="true" multiple
                  :rules="[showUserThirdsField ? requiredValidator : null]" :error-messages="errorsBack.thirds_id" clearable
                  :disabled="disabledFiledsView" :itemsData="third_arrayInfo" :firstFetch="false" />
              </VCol>
              <!-- <VCol cols="12" v-if="user.role_id == ROLE_SUPERADMIN_UUID">
                <AppSelect :requiredField="true" :items="companies" label="Compañia" :rules="[requiredValidator]"
                  v-model="form.company_id" :error-messages="errorsBack.company_id" clearable
                  :disabled="disabledFiledsView" />
              </VCol> -->
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
