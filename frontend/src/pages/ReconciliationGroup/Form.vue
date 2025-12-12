<script lang="ts" setup>
import { useToast } from '@/composables/useToast';
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import ISelect from '@/interfaces/Axios/ISelect';
import { router } from '@/plugins/1.router';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { VForm } from 'vuetify/components/VForm';

definePage({
  path: "reconciliationGroup-form/:action/:id?",
  name: "ReconciliationGroup-Form",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "reconciliationGroup.list",
  },
}); 

const authenticationStore = useAuthenticationStore();
const { toast } = useToast()
const errorsBack = ref<IErrorsBack>({});
const disabledFiledsView = ref<boolean>(false);
const route = useRoute()
const formValidation = ref<VForm>()
const loading = reactive({
  form: false,
  thirds: false,
}) 
const third_arrayInfo = ref<ISelect[]>([])
const form = ref({
  id: null as string | null,
  company_id: null as string | null,
  name: null as string | null, 
  third_id: null as string | null, 
})

const clearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
}

const fetchDataForm = async () => {

  form.value.id = route.params.id || null

  const url = form.value.id ? `/reconciliationGroup/${form.value.id}/edit` : `/reconciliationGroup/create`

  loading.form = true
  const { response, data } = await useApi(url).get();
  loading.form = false

  if (response.value?.ok && data.value) {
 
   //select infinitos
    third_arrayInfo.value = data.value.third_arrayInfo

    //formulario 
    if (data.value.form) {
      form.value = data.value.form
    }
  }
}


const submitForm = async (isCreateAndNew: boolean = false) => {
  const validation = await formValidation.value?.validate()
  if (validation?.valid) {

    form.value.company_id = authenticationStore.company.id;

    const url = form.value.id ? `/reconciliationGroup/update/${form.value.id}` : `/reconciliationGroup/store`

    loading.form = true;
    const { data, response } = await useAxios(url).post(form.value);
    loading.form = false;

    if (response.status == 200 && data) {
        router.push({ name: 'ReconciliationGroup-List' }); // Redirigir si es "Crear"
    }
    if (data.code === 422) errorsBack.value = data.errors ?? {};

  }
  else {
    toast('Faltan Campos Por Diligenciar', '', 'danger')
  }
}


if (route.params.action == 'view') disabledFiledsView.value = true

onMounted(async () => {
  clearForm()
    await fetchDataForm()
})

// Computed que verifica si al menos uno de los valores es true
const isLoading = computed(() => {
  return Object.values(loading).some(value => value);
});
 
</script>


<template>
  <div>
    <VCard :disabled="loading.form" :loading="loading.form">
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Formnulario de Grupos de conciliación
        </span>
      </VCardTitle>
      <VCardText>

        <VForm ref="formValidation" @submit.prevent="() => { }" :disabled="disabledFiledsView">
          <VRow>
            <VCol cols="12" md="6">
              <AppTextField :requiredField="true" :rules="[requiredValidator]" v-model="form.name" label="Nombre"
                :error-messages="errorsBack.name" @input="errorsBack.name = ''" clearable />
            </VCol> 
            <VCol cols="12" md="6">
               <AppSelectRemote :requiredField="true" label="Terceros" v-model="form.third_id"
                url="f" arrayInfo="third"  :itemsData="third_arrayInfo" :firstFetch="false"
                :error-messages="errorsBack.third_id" @input="errorsBack.third_id = ''" clearable
                :loading="loading.thirds" :rules="[requiredValidator]" :disabled="disabledFiledsView"/>
            </VCol> 
          </VRow>

        </VForm>

      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap mt-5">
        <BtnBack :disabled="isLoading" :loading="isLoading" /> 
        <VBtn v-if="!disabledFiledsView" :disabled="isLoading" :loading="isLoading" @click="submitForm()"
          color="primary">
          Guardar
          </VBtn>
      </VCardText>
    </VCard>
  </div>
</template>
