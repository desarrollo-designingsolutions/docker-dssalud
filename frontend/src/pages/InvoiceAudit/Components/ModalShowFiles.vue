<script setup lang="ts">
const emits = defineEmits(["execute"]);

const titleModal = ref<string>("Visualizar soportes");
const isDialogVisible = ref<boolean>(false);
const isLoading = ref<boolean>(false);

const form = ref({
})

const handleClearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
}

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const openModal = async (fileable_id: string, fileable_type: string, dataExtra: any = {}) => {
  handleClearForm();
  handleDialogVisible();

  form.value.fileable_id = fileable_id
  form.value.fileable_type = fileable_type

  executeFetchTable(1);

};

defineExpose({
  openModal,
});

const files = ref([]);
const optionsTable = reactive({
  lastPage: '',
  totalData: 1,
  totalPage: 1,
  currentPage: 1,
  rowPerPage: 10,
  searchQuery: {},
});

const executeFetchTable = (page: number | null = null) => {
  if (page) optionsTable.currentPage = page;
  searchQuery.value = {
    perPage: Number(optionsTable.rowPerPage),
    page: optionsTable.currentPage,
    searchQuery: optionsTable.searchQuery,
    ...form.value,
  }
  getFiles();
};

const getFiles = async () => {
  isLoading.value = true;
  const { data, response } = await useApi<any>(
    createUrl(`file/paginate`, {
      query: searchQuery.value,
    })
  );
  isLoading.value = false;

  if (response.value?.ok) {
    files.value = data.value.tableData
    optionsTable.lastPage = data.value.lastPage;
    optionsTable.totalData = data.value.totalData;
    optionsTable.totalPage = data.value.totalPage;
    optionsTable.currentPage = data.value.currentPage;
  }
};


// Estado para el archivo seleccionado
const fileSelected = ref(null)
const fileUrlS3 = ref<null | string>(null)

// Determinar tipo de archivo
const esPDF = computed(() => {
  return fileSelected.value?.filename.toLowerCase().endsWith('.pdf')
})

const esImagen = computed(() => {
  const extensionesImagen = ['.jpg', '.jpeg', '.png', '.gif']
  return extensionesImagen.some(ext =>
    fileSelected.value?.filename.toLowerCase().endsWith(ext)
  )
})

// Función para seleccionar archivo
const seleccionarArchivo = async (item) => {
  fileSelected.value = item
  isLoading.value = true;
  const { data, response } = await useAxios(`/file/getUrlS3`).get({
    params:{
      pathname:item.pathname
    }
  });
  isLoading.value = false;

  if (response.status == 200 && data) {
    fileUrlS3.value = data.fileUrlS3
  }
}

const viewFile = (pathname: any) => {
  window.open(
    `${pathname}`,
    "_blank"
  );
};

//FILTER
const optionsFilter = ref({
  inputGeneral: {
    relationsGeneral: {
      all: ["filename"],
    },
  },
})

const searchQuery = ref<any>(null)

const changeFilter = (data: any) => {
  optionsTable.searchQuery = data;
  getFiles();
};

// FUNCTION paginationData
const paginationData = (tableItems: number) => {
  const firstIndex = tableItems
    ? (optionsTable.currentPage - 1) *
    optionsTable.rowPerPage +
    1
    : 0;
  const lastIndex =
    tableItems +
    (optionsTable.currentPage - 1) *
    optionsTable.rowPerPage;
  return `Mostrando ${firstIndex} a ${lastIndex} de ${optionsTable.totalData} registros`;
};
</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" fullscreen transition="dialog-transition" persistent :scrim="false">

      <VCard :loading="isLoading">
        <div>
          <VToolbar color="primary">
            <VBtn icon variant="plain" @click="handleDialogVisible">
              <VIcon color="white" icon="tabler-x" />
            </VBtn>

            <VToolbarTitle>
              {{ titleModal }}
            </VToolbarTitle>
          </VToolbar>
        </div>


        <VCardText class="mt-2">
          <VRow>
            <!-- Lista de archivos -->
            <v-col cols="4">
              <v-card>
                <FilterDialog :optionsFilter="optionsFilter" @sendFilter="changeFilter" />
                <v-list>
                  <v-list-item v-for="(item, index) in files" :key="index" @click="seleccionarArchivo(item)"
                    :class="{ 'active-item': fileSelected && fileSelected.filename === item.filename }">
                    <v-list-item-title>{{ item.filename }}</v-list-item-title>
                  </v-list-item>

                </v-list>
              </v-card>
              <VRow class="mt-5">
                <VCol lg="2" cols="3">
                  <VTextField v-model="optionsTable.rowPerPage" @update:model-value="executeFetchTable(1)"
                    label="Filas Por Página" type="number" min="-1" hide-details variant="underlined" />
                </VCol>

                <VContainer fluid class="d-flex align-center flex-wrap justify-space-between gap-4 py-3 px-5">
                  <span class="text-sm text-disabled">
                    {{ paginationData(files.length) }}
                  </span>
                  <VPagination v-model="optionsTable.currentPage" @click="executeFetchTable()" size="small"
                    :total-visible="5" :length="optionsTable.lastPage" />
                </VContainer>
              </VRow>
            </v-col>

            <!-- Área de previsualización -->
            <v-col cols="8">
              <v-card v-if="fileUrlS3"> 
                <!-- Previsualización de PDF -->
                <iframe v-if="esPDF" :src="fileUrlS3" width="100%"
                  style="block-size: 100vh;"></iframe>

                <!-- Previsualización de Imagen -->
                <v-img v-else-if="esImagen" :src="fileUrlS3" max-height="500" contain></v-img>

                <!-- Mensaje para otros tipos de archivo -->
                <v-card-text v-else>
                  No hay previsualización disponible para este tipo de archivo
                  <VBtn @click="viewFile(fileUrlS3)">
                    descargar
                    <template #append>
                      <VIcon icon="tabler-download"></VIcon>
                    </template>
                  </VBtn>
                </v-card-text>
              </v-card>
              <v-card v-else>
                <v-card-text>Selecciona un archivo para previsualizar</v-card-text>
              </v-card>
            </v-col>
          </VRow>
        </VCardText>

      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.active-item {
  background-color: rgba(var(--v-theme-primary), 0.9);

  /* Color de fondo para el item seleccionado */
  border-inline-start: 4px solid #1976d2;
  color: white;

  /* Ejemplo de indicación visual */
}
</style>
