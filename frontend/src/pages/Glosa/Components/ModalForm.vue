<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { VForm } from "vuetify/components/VForm";

const { toast } = useToast()
const authenticationStore = useAuthenticationStore();
const { company } = storeToRefs(authenticationStore)

const errorsBack = ref<IErrorsBack>({});
const refForm = ref<VForm>();
const emit = defineEmits(["execute"])

const titleModal = ref<string>("Glosa")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)

const isLoading = ref<boolean>(false)

const form = ref({
  id: null as string | null,
  user_id: null as string | null,
  service_id: null as string | null,
  code_glosa_id: null as string | null,
  glosa_value: null as number | string | null,
  observation: null as string | null,
})

const typeGlosa = ref<string>('parcial')

const totalValueService = ref<string>('')

const totalValueGlosa = ref<string>('')

const typesValueGlosa = ref([
  'total',
  'parcial',
])

const handleClearForm = () => {
  for (const key in form.value) {
    form.value[key] = null;
  }
}

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
  handleClearForm()
};

const openModal = async ({ id, service_id, total_value }: any, disabled: boolean = false) => {
  disabledFiledsView.value = disabled

  handleDialogVisible();

  form.value.id = id;
  form.value.service_id = service_id;
  totalValueService.value = total_value;

  if (form.value.id) {
    typeGlosa.value = 'parcial'
    disabledTotal.value = false
    await fetchDataForm();
  } else {
    totalValueGlosa.value = total_value;
    typeGlosa.value = 'total'

    form.value.glosa_value = cloneObject(totalValueGlosa.value);
    dataCalculate.real_glosa_value = cloneObject(totalValueGlosa.value);

    disabledTotal.value = true
  }

};

const fetchDataForm = async () => {

  const url = form.value.id ? `/glosa/${form.value.id}/edit` : `/glosa/create`

  isLoading.value = true
  const { data, response } = await useAxios(url).get();

  if (response.status == 200 && data) {
    if (data.form) {
      form.value = cloneObject(data.form)

      totalValueGlosa.value = form.value.glosa_value;

      form.value.glosa_value = currencyFormat(
        formatToCurrencyFormat(totalValueGlosa.value)
      );
      dataCalculate.real_glosa_value = cloneObject(totalValueGlosa.value);

    }
  }
  isLoading.value = false
}


const submitForm = async () => {
  const validation = await refForm.value?.validate()
  if (validation?.valid) {
    const url = form.value.id ? `/glosa/update/${form.value.id}` : `/glosa/store`

    form.value.user_id = authenticationStore.user.id;
    form.value.company_id = authenticationStore.company.id;

    isLoading.value = true;
    const { data, response } = await useAxios(url).post({
      ...form.value,
      glosa_value: dataCalculate.real_glosa_value
    });

    if (response.status == 200 && data) {
      handleDialogVisible();
      emit('execute');
    }
    if (data.code === 422) errorsBack.value = data.errors ?? {};

    isLoading.value = false;
  }
  else {
    toast('Faltan Campos Por Diligenciar', '', 'danger')
  }
}

defineExpose({
  openModal
})

const disabledTotal = ref(false)
const handleTypeGlosaChange = (event: any) => {
  disabledTotal.value = false

  if (event.target.value == 'total') {

    form.value.glosa_value = currencyFormat(
      formatToCurrencyFormat(totalValueService.value)
    );
    disabledTotal.value = true
  }
  dataReal(parseEuropeanNumber(form.value.glosa_value), 'real_glosa_value');
  loadFirstTime.value = false;
  mayorTotalValueServiceValidator();
}

// Regla para que sea mayor que cero
const positiveValidator = (value: number | string) => {
  const num = typeof value === 'string' ? parseFloat(value) : value
  if (isNaN(num) || num <= 0) {
    return 'El valor debe ser mayor que cero'
  }
  return true
}

const mayorTotalValueServiceValidator = () => {
  if (parseEuropeanNumber(form.value.glosa_value) > totalValueService.value) {
    return 'El valor debe ser menor o igual al valor total del servicio'
  }
  return true
}

//FORMATO COMPONENTE MONEDA
const dataCalculate = reactive({
  real_glosa_value: 0 as number,
})

const loadFirstTime = ref(true);

const dataReal = (data: any, field: string) => {
  dataCalculate[field] = data
}

function parseEuropeanNumber(value: string): number {
  if (!value) return 0;
  // Quitar el punto de miles y reemplazar la coma decimal
  const cleaned = value.replace(/\./g, '').replace(',', '.');
  return parseFloat(cleaned);
}

</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" max-width="50rem" transition="dialog-transition" persistent>
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
              <VCol cols="12" md="8">
                <AppSelectRemote :requiredField="true" :rules="[requiredValidator]" :disabled="disabledFiledsView"
                  label="Código Glosa" v-model="form.code_glosa_id" url="/selectInfiniteCodeGlosa"
                  array-info="codeGlosa" clearable :error-messages="errorsBack.code_glosa_id"
                  @input="errorsBack.code_glosa_id = ''">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12">
                <VRadioGroup :requiredField="true" label="Valor a glosar" v-model="typeGlosa" inline>
                  <div>
                    <VRadio v-for="radio in typesValueGlosa" @click="handleTypeGlosaChange($event)" :key="radio"
                      :label="radio" :value="radio.toLocaleLowerCase()" />
                  </div>
                </VRadioGroup>
              </VCol>
              <!-- <VCol cols="12">
                <AppTextField :requiredField="true" clearable :disabled="disabledFiledsView || disabledTotal"
                  label="Valor glosa" :rules="[requiredValidator]" v-model="form.glosa_value"
                  :error-messages="errorsBack.glosa_value" @input="errorsBack.glosa_value = ''" />
              </VCol> -->
              <VCol cols="12">
                <FormatCurrency v-show="!isLoading" :requiredField="true"
                  :disabled="disabledFiledsView || disabledTotal" label="Valor glosa"
                  :rules="[requiredValidator, positiveValidator, mayorTotalValueServiceValidator]"
                  v-model="form.glosa_value" @realValue="dataReal($event, 'real_glosa_value')"
                  :error-messages="errorsBack.glosa_value" @input="errorsBack.glosa_value = ''" clearable />
              </VCol>
              <VCol cols="12">
                <AppTextarea :requiredField="true" clearable :disabled="disabledFiledsView" label="Observación"
                  :rules="[requiredValidator]" v-model="form.observation" :error-messages="errorsBack.observation"
                  @input="errorsBack.observation = ''" />
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
