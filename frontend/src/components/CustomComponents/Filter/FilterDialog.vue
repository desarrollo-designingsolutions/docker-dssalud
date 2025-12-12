<script setup lang="ts">
import { debounce } from "lodash";

const props = defineProps(["optionsFilter"]);
const emit = defineEmits(["sendFilter"]);

const optionsFilter = reactive<IOptionsFilter>({
  showBtnSearch: false,
  inputGeneral: {
    placeholder: "Busqueda general",
    relationsGeneral: {},
  },
  dialog: {
    cols: "12",
    width: 2024,
    inputs: [],
  }
});

// Función recursiva para copiar datos 
const recurrentdata = (target: IOptionsFilter, source: IOptionsFilter) => {
  for (const key in source) {
    if (Object.prototype.hasOwnProperty.call(source, key)) {
      const element = source[key];

      // Si el elemento es un array, copiamos sus elementos uno por uno
      if (Array.isArray(element)) {
        target[key] = element.map(item => {
          // Copiamos objetos anidados de manera recursiva dentro del array
          if (typeof item === 'object' && item !== null && key != 'paramsFilter') {
            const newObj = {};

            recurrentdata(newObj, item);
            return newObj;
          }
          return item; // Copiamos valores primitivos directamente
        });
      }
      // Si el elemento es un objeto, llamamos recursivamente a recurrentdata
      else if (typeof element === 'object' && element !== null) {

        recurrentdata(target[key], element);
      } else {
        target[key] = element; // Copiamos valores primitivos directamente
      }
    }
  }
};

const isDialogVisible = ref(false);
const openModalFilter = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const selectYesAndNot = [
  { value: null, title: "Todos" },
  { value: 1, title: "Si" },
  { value: 0, title: "No" },
];

const booleanActive = [
  { value: null, title: "Todos" },
  { value: 1, title: "Activo" },
  { value: 0, title: "Inactivo" },
];

const clearFilter = () => {
  arrayFilter.value.forEach((item) => {
    item.search = item.multiple ? [] : null;
  });
  emit("sendFilter", {
    generalSearch: generalSearch.value,
    relationsGeneral: optionsFilter.inputGeneral.relationsGeneral,
    page: 1,
  });
};

const fecthSearch = () => {
  emit("sendFilter", {
    arrayFilter: arrayFilter.value.filter((item) => {
      return (
        item.search !== null &&
        !(Array.isArray(item.search) && item.search.length === 0)
      );
    }),
    generalSearch: generalSearch.value,
    relationsGeneral: optionsFilter.inputGeneral.relationsGeneral,
    page: 1,
  });
};

const arrayFilter = ref<
  Array<{
    input: null;
    search_key: null;
    title: string;
    type: null | string;
    search: Array<number> | null;
    input_type: null;
    relation_key: null;
    relation: null | string | Array<string>;
    arrayList: Array<Object>;
    multiple: boolean;
    custom_search: boolean;
    chips: boolean;
    paramsFilter: Object;
  }>
>([]);

const selectsInfinites = {};
const variablesDinamicas = {};
const funcionesDinamicas = {};
const selectKey = reactive({});
const resetFilters = ref();

const loadArrayFilter = () => {
  const newInputs = optionsFilter.dialog.inputs;
  resetFilters.value = cloneObject(newInputs);
  newInputs.forEach((element, index) => {

    let search = null;
    if (element.multiple) {
      search = []
      if (element.selected) {
        search = element.selected
      }
    }

    arrayFilter.value.push({
      input: element.key ?? null, //input genera las key de los select
      search_key: element.search_key ?? element.key,
      title: element.title ?? "",
      type: element.type ?? "LIKE",
      search: search,
      input_type: element.input_type ?? "string",
      relation_key: element.relation_key ?? null,
      relation: element.relation ?? null,
      arrayList: element.arrayList ?? [],
      multiple: element.multiple ?? false,
      custom_search: element.custom_search ?? false,
      chips: element.custom_search ?? false,
      paramsFilter: element.paramsFilter ?? {},
    });
  });
};

const loadSelectsInfinites = () => {
  // Filtra los dialog.inputs que tienen input_type 'selectInfinite'
  const searchSelectsInfinites = optionsFilter.dialog.inputs.filter(
    (ele) => ele.input_type === "selectInfinite"
  );

  // Llena selectsInfinites basado en los dialog.inputs filtrados
  searchSelectsInfinites.forEach(async (element) => {
    selectsInfinites[element.key] = []; // Configuración inicial de selectsInfinites
    element.paramsFilter = element.paramsFilter ? JSON.parse(element.paramsFilter) : 0
    // Crear las variables reactivas utilizando ref
    variablesDinamicas[element.key + "_arrayInfo"] = ref<Array<object>>([]);
    variablesDinamicas[element.key + "_countLinks"] = ref<number>(1);

    // Crear la función asincrónica utilizando async/await
    funcionesDinamicas[
      "select" + element.key.charAt(0).toUpperCase() + element.key.slice(1)
    ] = async (params) => {
      const { data, response } = await useApi("/" + element.api).post(params);

      if (response.value?.ok && data.value) {
        variablesDinamicas[element.key + "_arrayInfo"].value =
          data.value[element.key + "_arrayInfo"];
        variablesDinamicas[element.key + "_countLinks"].value =
          data.value[element.key + "_countLinks"];
      }
    };

    selectKey[element.key] = false;

    const paramsFilter = element.paramsFilter ? cloneObject(element.paramsFilter) : {};

    // Realizar la petición POST
    const { data, response } = await useApi(`/${element.api}`).post(paramsFilter);

    // Verificar la respuesta
    if (response.value?.ok && data.value) {
      variablesDinamicas[element.key + "_arrayInfo"].value =
        data.value[element.key + "_arrayInfo"];
      variablesDinamicas[element.key + "_countLinks"].value =
        data.value[element.key + "_countLinks"];

      const paramsFilter2 = element.paramsFilter ? cloneObject(element.paramsFilter) : {};

      // Ejemplo de uso de useSelect con los datos actualizados
      let newVariable = useSelect(
        funcionesDinamicas[
        "select" + element.key.charAt(0).toUpperCase() + element.key.slice(1)
        ], // Utilizamos la función dinámica correspondiente
        variablesDinamicas[element.key + "_arrayInfo"],
        variablesDinamicas[element.key + "_countLinks"],
        paramsFilter2 // Opciones adicionales si es necesario
      );
      newVariable.dataNueva.value = JSON.parse(
        JSON.stringify(variablesDinamicas[element.key + "_arrayInfo"].value)
      );
      newVariable.totalLinks.value = JSON.parse(
        JSON.stringify(variablesDinamicas[element.key + "_countLinks"].value)
      );

      selectsInfinites[element.key] = newVariable;

      selectKey[element.key] = true;
    }
  });
};

const allTrueSelect = computed(() => {
  for (const key in selectKey) {
    if (!selectKey[key]) {
      return false; // Si encuentra un elemento false, retorna false inmediatamente
    }
  }
  return true; // Si no encuentra ningún elemento false, retorna true
});

onMounted(async () => {

  if (isNullOrUndefined(props.optionsFilter.showFilter)) {
    // Llamada inicial para copiar los datos de props.optionsFilter a optionsFilter
    await recurrentdata(optionsFilter, props.optionsFilter);

    await loadArrayFilter();

    await loadSelectsInfinites();
  }

});


const reloadComponent = async (optionsFilterNew: any) => {
  await recurrentdata(optionsFilter, optionsFilterNew);

  await loadArrayFilter();

  await loadSelectsInfinites();
}
//Busqueda  General
const generalSearch = ref<string>("");

// Crear una función debounced para manejar la búsqueda
const debouncedEmit = debounce((newValue) => {
  emit("sendFilter", {
    arrayFilter: arrayFilter.value.filter((item) => {
      return (
        item.search !== null &&
        !(Array.isArray(item.search) && item.search.length === 0)
      );
    }),
    generalSearch: newValue,
    relationsGeneral: optionsFilter.inputGeneral.relationsGeneral,
    page: 1,
  });
}, 300); // Ajusta el tiempo de espera (en milisegundos) según sea necesario

watch(generalSearch, (newValue, oldValue) => {
  if (!optionsFilter.showBtnSearch) {
    // Llamar a la función debounced en lugar de emitir directamente
    debouncedEmit(newValue);
  }
});

const filterAplicated = computed(() => {
  return arrayFilter.value.some((item) => {
    return (
      item.search && (!Array.isArray(item.search) || item.search.length > 0)
    );
  });
});

defineExpose({
  reloadComponent,
})

const loadFullComponent = computed(() => {
  if (isNullOrUndefined(props.optionsFilter.showFilter)) {
    return allTrueSelect.value
  } else {
    return optionsFilter.showFilter && allTrueSelect
  }
})
</script>

<template>
  <div>
    <div v-if="!loadFullComponent">
      <VAlert color="primary" icon="tabler-search" variant="tonal">
        Cargando filtro...
      </VAlert>
    </div>

    <template v-if="loadFullComponent">
      <div class="d-flex justify-end gap-3 flex-wrap">
        <VRow>
          <VCol cols="12" md="6">
            <AppTextField :label="optionsFilter.inputGeneral.placeholder" clearable v-model="generalSearch"
              :placeholder="optionsFilter.inputGeneral.placeholder" prepend-inner-icon="tabler-search">
            </AppTextField>
          </VCol>
          <VCol cols="12" md="6" class="mt-4">
            <div class="d-flex justify-start gap-3 flex-wrap">
              <VBtn class="mt-2" v-if="arrayFilter.length > 0" :loading="!allTrueSelect" :disabled="!allTrueSelect"
                color="primary" @click="openModalFilter()">
                <template #prepend>
                  <VIcon icon="tabler-filter" start />
                </template>
                Filtros
              </VBtn>
              <VBtn class="mt-2" v-if="filterAplicated" :loading="!allTrueSelect" :disabled="!allTrueSelect"
                color="success" @click="clearFilter">
                <VTooltip activator="parent" location="bottom"> Limpiar </VTooltip>
                <VIcon icon="tabler-filter-cancel"></VIcon>
              </VBtn>
              <VBtn v-if="!optionsFilter.showBtnSearch" class="mt-2" @click="fecthSearch()">
                <VTooltip activator="parent" location="bottom"> Actualizar </VTooltip>
                <VIcon icon="tabler-rotate-clockwise" />
              </VBtn>
              <VBtn v-if="optionsFilter.showBtnSearch" class="mt-2" @click="fecthSearch()">
                <template #prepend>
                  <VIcon icon="tabler-search" />
                </template>
                Buscar
              </VBtn>

              <div class="mt-2">
                <slot name="newBtns"></slot>
              </div>

            </div>
          </VCol>
          <slot name="newFields"></slot>
        </VRow>
      </div>
    </template>

    <VDialog v-model="isDialogVisible" :width="optionsFilter.dialog.width" persistent>
      <!-- Dialog close btn -->
      <DialogCloseBtn @click="openModalFilter()" />

      <!-- Dialog Content -->
      <VCard title="Filtros">
        <VCardText>
          <VRow>
            <template v-for="(obj, objIndex) in arrayFilter" :key="objIndex">
              <VCol :cols="optionsFilter.dialog.cols" v-if="obj.input_type == 'select'">
                <AppSelect clearable v-model="obj.search" :items="obj.arrayList" :label="obj.title"
                  :multiple="obj.multiple" :chips="obj.multiple" :closable-chips="obj.multiple">
                </AppSelect>
              </VCol>

              <VCol :cols="optionsFilter.dialog.cols" v-else-if="obj.input_type == 'selectChipCount'">
                <AppSelect v-model="obj.search" :items="obj.arrayList" :label="obj.title" :multiple="obj.multiple">
                  <template v-slot:selection="{ item, index }">
                    <v-chip closable @click:close="obj.search?.splice(index, 1)" v-if="index < 5">
                      <span>{{ item.title }}</span>
                    </v-chip>
                    <span v-if="index === 5" class="text-grey text-caption align-self-center">
                      (+{{ obj.search?.length - 5 }} otros)
                    </span>
                  </template>
                </AppSelect>
              </VCol>

              <VCol :cols="optionsFilter.dialog.cols" v-else-if="obj.input_type == 'booleanActive'">
                <AppSelect v-model="obj.search" clearable :items="booleanActive" :label="obj.title"
                  :chips="obj.multiple" :closable-chips="obj.multiple" />
              </VCol>

              <VCol :cols="optionsFilter.dialog.cols" v-else-if="obj.input_type == 'selectYesAndNot'">
                <AppSelect v-model="obj.search" clearable :items="selectYesAndNot" :label="obj.title"
                  :chips="obj.multiple" :closable-chips="obj.multiple" />
              </VCol>

              <VCol :cols="optionsFilter.dialog.cols" v-else-if="obj.input_type == 'selectInfinite'">
                <SelectInfinite return-object clearable v-model="obj.search" :items="obj.arrayList" :label="obj.title"
                  :multiple="obj.multiple" :chips="obj.multiple" :closable-chips="obj.multiple"
                  :select="selectsInfinites[obj.input]" :key="objIndex" />
              </VCol>

              <VCol :cols="optionsFilter.dialog.cols" v-else-if="obj.input_type == 'dateRange'">
                <AppDateTimePicker v-model="obj.search" :label="obj.title" placeholder="Desde-Hasta"
                  :config="{ mode: 'range' }" />
              </VCol>

              <VCol :cols="optionsFilter.dialog.cols" v-else-if="obj.input_type == 'date'">
                <AppDateTimePicker v-model="obj.search" :label="obj.title" />
              </VCol>

              <VCol :cols="optionsFilter.dialog.cols" v-else>
                <AppTextField clearable v-model="obj.search" :label="obj.title"></AppTextField>
              </VCol>
            </template>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn @click="openModalFilter()" color="secondary">
            <VIcon start icon="tabler-x" />
            Cancelar
          </VBtn>
          <VBtn @click="fecthSearch()">
            <VIcon start icon="tabler-search" />
            Buscar
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
