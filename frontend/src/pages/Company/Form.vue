<script lang="ts" setup>
import { useToast } from '@/composables/useToast';
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import IImageSelected from '@/interfaces/FileUpload/IImageSelected';
import { router } from '@/plugins/1.router';
import moment from "moment";
import type { VForm } from 'vuetify/components/VForm';

definePage({
  path: "company-form/:action/:id?",
  name: "Company-Form",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "company.list",
  },
});

const { toast } = useToast()
const errorsBack = ref<IErrorsBack>({});
const disabledFiledsView = ref<boolean>(false);
const route = useRoute()
const formValidation = ref<VForm>()
const loading = reactive({
  form: false,
  countries: false,
  states: false,
  cities: false,
})

const form = ref({
  id: null as string | null,
  name: null as string | null,
  nit: null as string | null,
  phone: null as string | null,
  country_id: null as string | null,
  state_id: null as string | null,
  city_id: null as string | null,
  address: null as string | null,
  email: null as string | null,
  logo: null as string | null | File,
  start_date: null as string | null,
  final_date: null as string | null,
})

const clearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
}

const countries_arrayInfo = ref<Array<{
  value: number,
  title: string
}>>([])
const countries_countLinks = ref<number>(1)



const fetchDataForm = async () => {

  form.value.id = route.params.id || null

  const url = form.value.id ? `/company/${form.value.id}/edit` : `/company/create`

  loading.form = true
  const { response, data } = await useApi(url).get();

  if (response.value?.ok && data.value) {

    //select infinitos
    select_countries.dataNueva.value = data.value.countries_arrayInfo
    select_countries.totalLinks.value = data.value.countries_countLinks

    //formulario 
    if (data.value.form) {
      form.value = data.value.form
      const formClone = cloneObject(data.value.form)

      if (data.value.form.id) {
        await changeCountry(formClone.country_id)
        await changeState(formClone.state_id)

        form.value.country_id = formClone.country_id
        form.value.state_id = formClone.state_id
        form.value.city_id = formClone.city_id
      }
    }
  }
  loading.form = false
}

const states = ref<Array<object>>([])
const cities = ref<Array<object>>([])

//SELECT DEPARTAMENTOS
const changeCountry = async (event: any) => {
  form.value.state_id = null;
  form.value.city_id = null;
  loading.states = true;
  const { data, response } = await useApi(
    `/selectStates/${event.value}`
  ).get();

  if (response.value?.ok && data.value) {
    states.value = data.value.states;
    cities.value = [];
    loading.states = false;
  }
}

//SELECT CIUDADES
const changeState = async (event: Event) => {
  form.value.city_id = null;

  loading.cities = true;
  const { data, response } = await useApi(`/selectCities/${event}`).get();
  loading.cities = false;

  if (response.value?.ok && data.value) {
    cities.value = data.value.cities;
  }
}

const submitForm = async () => {
  const validation = await formValidation.value?.validate()
  if (validation?.valid) {

    const url = form.value.id ? `/company/update/${form.value.id}` : `/company/store`

    if (logo.value.imageFile) form.value.logo = logo.value.imageFile

    const formData = new FormData();
    for (const key in form.value) {
      if (!["country_id"].includes(key)) {
        formData.append(key, form.value[key])
      }
    }
    formData.append("country_id", form.value.country_id.value);

    loading.form = true;
    const { data, response } = await useApi(url).post(formData);
    loading.form = false;

    if (response.value?.ok && data.value) {

      if (data.value.code == 200) {
        router.push({ name: 'Company-List' })
      }
    }
    if (data.value.code === 422) errorsBack.value = data.value.errors ?? {};

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



//SELECT INFINITE COUNTRIES
const selectInfiniteCountries = async (params: object) => {
  loading.countries = true;
  const { data, response } = await useApi("/selectInfiniteCountries").post(params)
  loading.countries = false;
  if (response.value?.ok && data.value) {
    countries_arrayInfo.value = data.value.countries_arrayInfo
    countries_countLinks.value = data.value.countries_countLinks
  }
}

const select_countries = useSelect(
  selectInfiniteCountries,
  countries_arrayInfo,
  countries_countLinks,
  {}
);


//OBTENGO EL DIA DE HOY PARA CALCULAR LA FECHA INICIO
const now = new Date();
const currentYear = now.getFullYear();
const currentMonth = now.getMonth() + 1; // Los meses van de 0 a 11, por lo que sumamos 1
const currentDay = now.getDate();

const changeFinalDate = (event: any) => {
  if (event) {
    let d1 = moment(`${currentYear}-${currentMonth.toString().padStart(2, '0')}-${currentDay.toString().padStart(2, '0')}`);
    let d2 = moment(event);
    errorsBack.value.final_date = "";
    if (!d2.isAfter(d1))
      errorsBack.value.final_date = `La fecha debe ser posterior a ${d1.format('YYYY-MM-DD')}`;
  }
}

//File LOGO
const logo = ref(useFileUpload())
logo.value.allowedExtensions = ["jpg", "jpeg", "png"];
watch(logo.value, (newVal, oldVal) => {
  if (newVal.imageFile) form.value.logo = newVal.imageFile
})

const changeFile = (event: Event, logo: any) => {
  // Definir un tipo para la respuesta
  const response: IImageSelected = logo.handleImageSelected(event);

  if (response.success == false && response.icon) {
    openModalQuestion(response)
  }
}

//ModalQuestion
const refModalQuestion = ref()

const openModalQuestion = (response: IImageSelected) => {
  refModalQuestion.value.componentData.isDialogVisible = true
  refModalQuestion.value.componentData.dialogMaxWidth = '20rem'
  refModalQuestion.value.componentData.showBtnCancel = false
  refModalQuestion.value.componentData.btnSuccessText = 'Ok'
  refModalQuestion.value.componentData.icon = response.icon
  refModalQuestion.value.componentData.title = response.title
  refModalQuestion.value.componentData.subTitle = response.text
}

// Computed que verifica si al menos uno de los valores es true
const isLoading = computed(() => {
  return Object.values(loading).some(value => value);
});

const nameRules = [
  value => maxCharacters(value, 100),
  value => minCharacters(value, 2),
  value => requiredValidator(value),
];

const nitRules = [
  value => (!value || /^[0-9]{9}-[0-9]{1}$/.test(value)) || 'El NIT debe tener el formato 000000000-0',
  value => requiredValidator(value),
];

const phoneRules = [
  value => integerValidator(value),
  value => (!value || value.length <= 10) || "El número no debe tener mas de 10 caracteres",
  value => positiveNumberValidator(value),
  value => requiredValidator(value),
];

const stateRules = computed(() => {
  return states.value.length > 0 ? [value => !!value || "La región es obligatoria"] : [];
});

const cityRules = computed(() => {
  return cities.value.length > 0 ? [value => !!value || "La ciudad es obligatoria"] : [];
});

const addressRules = [
  value => (!value || value.length <= 150) || "La direccion no debe tener mas de 150 caracteres",
  value => requiredValidator(value),
];
</script>


<template>
  <div>
    <VCard :disabled="loading.form" :loading="loading.form">
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Formulario Compañia
        </span>
      </VCardTitle>

      <VCardText>
        <VForm ref="formValidation" @submit.prevent="() => { }" :disabled="disabledFiledsView">
          <VRow>
            <VCol sm="4">
              <VRow>
                <VCol>
                  <VLabel>Logo</VLabel>
                  <VFileInput accept="image/*" :loading="logo.loading" @change="changeFile($event, logo)"
                    :key="logo.key">
                  </VFileInput>
                </VCol>
              </VRow>

              <VRow v-if="logo.imageUrl || form.logo">
                <VCol class="d-flex justify-center ">
                  <VImg :src="logo.imageUrl ?? storageBack(form.logo)" alt="alt"></VImg>
                </VCol>
              </VRow>

            </VCol>
            <VCol sm="8">
              <VRow>

                <VCol sm="4">
                  <AppTextField :requiredField="true" :rules="nameRules" v-model="form.name" label="Nombre"
                    :error-messages="errorsBack.name" @input="errorsBack.name = ''" clearable counter maxlength="100"
                    persistent-counter />
                </VCol>
                <VCol sm="4">
                  <AppTextField :requiredField="true" :rules="nitRules" v-model="form.nit" label="Nit"
                    :error-messages="errorsBack.nit" @input="errorsBack.nit = ''" clearable />
                </VCol>
                <VCol sm="4">
                  <AppTextField :requiredField="true" :rules="phoneRules" v-model="form.phone" label="Teléfono"
                    :error-messages="errorsBack.phone" @input="errorsBack.phone = ''" counter maxlength="10"
                    persistent-counter />
                </VCol>

                <VCol cols="12" sm="4">
                  <SelectInfinite :requiredField="true" label="País" returnObject v-model="form.country_id"
                    :select="select_countries" @update:model-value="changeCountry($event)"
                    :error-messages="errorsBack.country_id" @input="errorsBack.country_id = ''" clearable
                    :loading="loading.countries" :rules="[requiredValidator]">
                  </SelectInfinite>
                </VCol>

                <VCol cols="12" sm="4">
                  <AppAutocomplete :disabled="disabledFiledsView || states.length <= 0" :loading="loading.states"
                    :requiredField="states.length > 0" clearable :items="states" v-model="form.state_id" label="Región"
                    @update:model-value="changeState($event)" :error-messages="errorsBack.state_id"
                    @input="errorsBack.state_id = ''" :rules="[...stateRules]">
                  </AppAutocomplete>
                </VCol>
                <VCol cols="12" sm="4">
                  <AppAutocomplete :disabled="disabledFiledsView || cities.length <= 0" :loading="loading.cities"
                    :requiredField="cities.length > 0" clearable :items="cities" v-model="form.city_id" label="Ciudad"
                    :error-messages="errorsBack.city_id" @input="errorsBack.city_id = ''" :rules="[...cityRules]">
                  </AppAutocomplete>
                </VCol>

                <VCol sm="4">
                  <AppTextField :requiredField="true" clearable v-model="form.address" :rules="addressRules"
                    label="Dirección" :error-messages="errorsBack.address" counter maxlength="150" persistent-counter />
                </VCol>

                <VCol sm="4">
                  <AppTextField :requiredField="true" clearable :rules="[requiredValidator, emailValidator]"
                    v-model="form.email" label="Email" :error-messages="errorsBack.email"
                    @input="errorsBack.email = ''" />
                </VCol>

              </VRow>
            </VCol>
          </VRow>

          <VRow>
            <VCol sm="4">
              <AppDateTimePicker clearable :requiredField="true" disabled label="Fecha Inicio" v-model="form.start_date"
                :error-messages="errorsBack.start_date" :rules="[requiredValidator]"
                :config="{ dateFormat: 'Y-m-d' }" />
            </VCol>
            <VCol sm="4">
              <AppDateTimePicker clearable :error-messages="errorsBack.final_date" @input="errorsBack.final_date = ''"
                v-model="form.final_date" label="Fecha Final" @update:model-value="changeFinalDate($event)"
                :config="{ dateFormat: 'Y-m-d', disable: [{ from: `2020-01-01`, to: `${currentYear}-${currentMonth}-${currentDay}` }] }" />
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

    <ModalQuestion ref="refModalQuestion" />
  </div>
</template>
