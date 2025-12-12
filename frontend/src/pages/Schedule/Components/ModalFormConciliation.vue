<script setup lang="ts">
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { VForm } from "vuetify/components/VForm";

const authenticationStore = useAuthenticationStore();
const { company } = storeToRefs(authenticationStore)

const errorsBack = ref<IErrorsBack>({});
const refForm = ref<VForm>();
const emit = defineEmits(["execute"])

const titleModal = ref<string>("Crear Evento Conciliación")
const isDialogVisible = reactive({ modal_user: false, modal_more_information: false })
const disabledFiledsView = ref<boolean>(false)

const loading = reactive({
  form: false,
  selectInfiniteDepartment: false,
})

interface ISelect {
  title: string;
  value: string;
}

const form = ref({
  id: null as null | string,
  company_id: null as null | string,
  title: null as null | string,
  start_date: null as null | Date | string,
  start_hour: '12:00' as null | string,
  end_date: null as null | string,
  end_hour: null as null | string,
  all_day: false as null | boolean | string,
  description: null as null | string,
  emails: null as null | string,
  user_id: null as null | string,
  third_id: null as null | string,
  link: null as null | string,
  reconciliation_group_id: null as null | string, 
  scheduleable_id: null as null | string,
})

const user_arrayInfo = ref<ISelect[]>([])
const third_arrayInfo = ref<ISelect[]>([])
const reconciliationGroup_arrayInfo = ref<ISelect[]>([])

const reschedule = ref<boolean>(false)

const handleClearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
  form.value.start_hour = '12:00' 
}

const handleDialogVisible = () => {
  isDialogVisible.modal_user = !isDialogVisible.modal_user;
};

const openModal = async (id: string | null = null, date: string | null, disabled: boolean = false) => {
  disabledFiledsView.value = disabled
  reschedule.value = false


  handleClearForm()
  handleDialogVisible();

  form.value.start_date = date
  form.value.end_date = date

  form.value.start_hour = '00:00';
  form.value.end_hour = '23:55';

  titleModal.value = id ? "Editar evento Conciliación" : "Crear evento Conciliación"

  form.value.id = id
  form.value.company_id = company.value.id
  await fetchDataForm();
};

const openModalToReschedule = async (id: string) => {
  reschedule.value = true

  handleClearForm()
  handleDialogVisible();

  form.value.start_hour = '00:00';
  form.value.end_hour = '23:55';

  titleModal.value = "Reagendar evento Conciliación"

  form.value.id = id
  form.value.company_id = company.value.id
  await fetchDataForm();
};

const roles = ref([])
const companies = ref([])
const fetchDataForm = async () => {

  const url = form.value.id ? `/schedule/conciliation/edit/${form.value.id}` : `/schedule/conciliation/create`

  loading.form = true
  const { data, response } = await useAxios(url).get({
    params: {
      company_id: company.value.id
    }
  });

  if (response.status == 200 && data) {
    roles.value = data.roles
    companies.value = data.companies

    //select infinitos
    user_arrayInfo.value = data.user_arrayInfo
    third_arrayInfo.value = data.third_arrayInfo
    reconciliationGroup_arrayInfo.value = data.reconciliationGroup_arrayInfo

    if (data.form) {
      form.value = data.form;

      //solo si es para reagendar
      if(reschedule.value){
        form.value.id = null
      }
    }
  }
  loading.form = false
}

const submitForm = async () => {
  const validation = await refForm.value?.validate();

  if (validation?.valid) {

    const url = form.value.id ? `/schedule/conciliation/update/${form.value.id}` : `/schedule/conciliation/store`

    loading.form = true

    const { response, data } = await useAxios(url).post(form.value);
    loading.form = false

    if (response.status == 200 && data) {
      emit("execute")
      handleDialogVisible()
    }

  }
}

// Computed que verifica si al menos uno de los valores es true
const isLoading = computed(() => {
  return Object.values(loading).some(value => value);
});

defineExpose({
  openModal,
  openModalToReschedule
})

const end_dateValidation = computed(() => {
  if (form.value.all_day) {
    return {
      rules: [
        value => requiredValidator(value),
      ],
      requiredField: true
    }
  }

  return {
    rules: [],
    requiredField: false
  }
})

const start_hourValidation = computed(() => {
  if (!form.value.all_day) {
    return {
      rules: [
        value => requiredValidator(value),
        value => validateStartTime(value, form.value.end_hour),

      ],
      requiredField: true
    }
  }

  return {
    rules: [],
    requiredField: false
  }
})

const end_hourValidation = computed(() => {
  if (!form.value.all_day) {
    return {
      rules: [
        value => requiredValidator(value),
        value => validateEndTime(value, form.value.start_hour),
      ],
      requiredField: true
    }
  }

  return {
    rules: [],
    requiredField: false
  }
})

const computedNow = computed(() => {
  return new Date().toISOString().substring(0, 10);
});

</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible.modal_user" :overlay="false" max-width="40rem" transition="dialog-transition"
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
                <AppTextField :requiredField="true" clearable :disabled="disabledFiledsView" label="Titulo"
                  :rules="[requiredValidator]" v-model="form.title" :error-messages="errorsBack.title" />
              </VCol>

              <VCol cols="12">
                <AppSelectRemote :disabled="disabledFiledsView" :requiredField="true"
                  label="Asignado" v-model="form.user_id" :errorMessages="errorsBack.user_id"
                  @input="errorsBack.user_id = ''" url="/selectInfiniteUser" arrayInfo="user" clearable
                  :itemsData="user_arrayInfo" :firstFetch="false" :rules="[requiredValidator]">
                </AppSelectRemote>
              </VCol>

              <VCol cols="12">
                <AppSelectRemote :disabled="disabledFiledsView" :requiredField="true"
                  label="Tercero" v-model="form.third_id" :errorMessages="errorsBack.third_id"
                  @input="errorsBack.third_id = ''" url="/selectInfiniteThird" arrayInfo="third" clearable
                  :itemsData="third_arrayInfo" :firstFetch="false" :rules="[requiredValidator]">
                </AppSelectRemote>
              </VCol>

              <VCol cols="12">
                <AppSelectRemote :disabled="disabledFiledsView" :requiredField="true"
                  label="Grupo de conciliación" v-model="form.reconciliation_group_id" :errorMessages="errorsBack.reconciliation_group_id"
                  @input="errorsBack.reconciliation_group_id = ''" url="/selectInfiniteReconciliationGroup" arrayInfo="reconciliationGroup" clearable
                  :itemsData="reconciliationGroup_arrayInfo" :firstFetch="false" :rules="[requiredValidator]">
                </AppSelectRemote>
              </VCol>

              <VCol cols="12">
                <AppTextField :requiredField="true" clearable :disabled="disabledFiledsView" label="Emails Invitados"
                  :rules="[requiredValidator]" v-model="form.emails" :error-messages="errorsBack.emails" />
              </VCol> 

              <VCol cols="12">
                <div class="d-flex">
                  <VSwitch v-model="form.all_day" label="Todo el dia" />
                </div>
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField type="date" :requiredField="true" label="Fecha inicio" v-model="form.start_date" clearable
                  :errorMessages="errorsBack.start_date" @input="errorsBack.start_date = ''"
                  :rules="[requiredValidator]" :min="computedNow" />
              </VCol>

              <VCol cols="12" md="3" v-if="!form.all_day">
                <AppDateTimePicker :requiredField="start_hourValidation.requiredField" label="Hora inicio"
                  v-model="form.start_hour" clearable :errorMessages="errorsBack.start_hour"
                  @input="errorsBack.start_hour = ''" :rules="start_hourValidation.rules" :config="{
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true,
                  }" />
              </VCol>

              <VCol cols="12" md="6" v-if="form.all_day">
                <AppTextField type="date" :requiredField="end_dateValidation.requiredField" label="Fecha fin"
                  v-model="form.end_date" clearable :errorMessages="errorsBack.end_date"
                  @input="errorsBack.end_date = ''" :rules="end_dateValidation.rules" :min="form.start_date" />
              </VCol>

              <VCol cols="12" md="3" v-if="!form.all_day">
                <AppDateTimePicker :requiredField="end_hourValidation.requiredField" label="Hora de fin"
                  v-model="form.end_hour" clearable :errorMessages="errorsBack.end_hour"
                  @input="errorsBack.end_hour = ''" :rules="end_hourValidation.rules" :config="{
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true,
                  }" />
              </VCol>

              <VCol cols="12">
                <AppTextarea :requiredField="true" :rules="[requiredValidator]" clearable v-model="form.description"
                  label="Descripcion" />
              </VCol>

              <VCol cols="12">
                <AppTextField :requiredField="true" clearable :disabled="disabledFiledsView" label="Link"
                  :rules="[requiredValidator]" v-model="form.link" :error-messages="errorsBack.link" />
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
