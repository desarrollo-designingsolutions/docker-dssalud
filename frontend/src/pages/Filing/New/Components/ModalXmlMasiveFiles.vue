<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();
const emits = defineEmits(["execute"]);

const titleModal = ref<string>("Cargar XML");
const isDialogVisible = ref<boolean>(false);
const disabledFiledsView = ref<boolean>(false);
const isLoading = ref<boolean>(false);
const uploadId = ref<string | null>(null);
const progress = ref<number>(0);
const countFilesRequest = ref<number>(0);

const form = ref({
  filing_id: '' as string,
});

const handleClearForm = () => {
  for (const key in form.value) {
    form.value[key] = null;
  }
};

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const openModal = async (filing_id: string) => {
  handleClearForm();
  handleDialogVisible();
  resetValues();
  form.value.filing_id = filing_id;
  await loadValidData();
};

const submitForm = async () => {

  if (fileData.value.length === 0) {
    refModalQuestion.value.componentData.isDialogVisible = true;
    refModalQuestion.value.componentData.showBtnCancel = false;
    refModalQuestion.value.componentData.btnSuccessText = 'Ok';
    refModalQuestion.value.componentData.title = 'Por favor, seleccione un archivo antes de continuar.';
    return;
  }

  const errors = errorsFiles.value;
  if (errors.length > 0) {
    refModalQuestion.value.componentData.isDialogVisible = true;
    refModalQuestion.value.componentData.showBtnCancel = false;
    refModalQuestion.value.componentData.btnSuccessText = 'Ok';
    refModalQuestion.value.componentData.title = 'Errores en los nombres de archivo:\n' + errors.map(e => e.message).join('\n');
    return;
  }

  isLoading.value = true;
  progress.value = 0;

  const formData = new FormData();
  fileData.value.forEach((fileItem) => {
    formData.append('files[]', fileItem.file);
    fileItem.status = 'uploading';
    fileItem.progress = 0;
  });

  formData.append('company_id', authenticationStore.company.id);
  formData.append('third_nit', authenticationStore.user.third.nit);
  formData.append('filing_id', form.value.filing_id);

  try {
    const { data, response } = await useApi('/filing/saveDataModalXmlMasiveFiles').post(formData);
    if (response.value?.ok && data.value) {
      uploadId.value = data.value.upload_id;
      countFilesRequest.value = fileData.value.length;
      listenToProgress(data.value.upload_id);
    }
  } catch (error) {
    isLoading.value = false;
    console.error('Error al enviar archivos:', error);
    fileData.value.forEach((fileItem) => {
      fileItem.status = 'failed';
      fileItem.progress = undefined;
    });
    alert('Error al enviar los archivos');
  }
  isLoading.value = false;
};

const listenToProgress = (uploadId: string) => {
  window.Echo.channel(`upload-progress.${uploadId}`)
    .listen('.file-progress', (event: any) => {
      progress.value = Number(event.progress);


      const fileItem = fileData.value.find((item) => item.file.name === event.fileName);
      if (fileItem) {
        fileItem.status = 'completed';
        fileItem.path = event.filePath;
      } else {
        console.warn(`No se encontró archivo para ${event.fileName}`);
      }

      if (progress.value == 100) {
        isLoading.value = false;
        refModalQuestion.value.componentData.isDialogVisible = true;
        refModalQuestion.value.componentData.showBtnCancel = false;
        refModalQuestion.value.componentData.btnSuccessText = 'Ok';
        refModalQuestion.value.componentData.title = `Se han cargado exitosamente ${countFilesRequest.value} archivos.`;

        countFilesRequest.value = 0; // Reiniciar contador
        progress.value = 0; // Reiniciar progreso
      }
    });
};

defineExpose({ openModal, disabledFiledsView });

const refModalQuestion = ref();
const { dropZoneRef, fileData, open, error, resetValues } = useFileDrop(0, ['xml']);

watch(error, (newError) => {
  if (newError) {
    refModalQuestion.value.componentData.isDialogVisible = true;
    refModalQuestion.value.componentData.showBtnCancel = false;
    refModalQuestion.value.componentData.btnSuccessText = 'Ok';
    refModalQuestion.value.componentData.title = newError;
  }
});

const openFileDialog = () => {
  error.value = null;
  open();
};

// Lista de facturas y códigos de soporte válidos
const validInvoiceNumbers = ref<string[]>([]);

const loadValidData = async () => {
  try {
    isLoading.value = true;
    const { response, data } = await useApi(`/filing/${form.value.filing_id}/getDataModalXmlMasiveFiles`).get();
    if (response.value?.ok && data.value) {
      validInvoiceNumbers.value = data.value.validInvoiceNumbers;
    }
    isLoading.value = false;
  } catch (err) {
    isLoading.value = false;
    console.error('Error cargando datos válidos:', err);
  }
};

// Validación de nombres de archivo
const errorsFiles = computed(() => {
  const errors: { fileName: string; message: string }[] = [];
  const thirdNit = authenticationStore.user.third.nit;
  const seenNames = new Set<string>();

  fileData.value.forEach((item) => {
    const fileName = item.file.name;
    const [nameWithoutExt, extension] = fileName.split('.');
    const parts = nameWithoutExt.split('_');
    const [nit, numFac, name] = parts;

    if (parts.length !== 3 || !extension) {
      errors.push({
        fileName,
        message: 'Formato inválido. Debe ser NIT_NUMFAC_NAME.XML',
      });
      return;
    }

    if (nit !== thirdNit) {
      errors.push({
        fileName,
        message: `El NIT (${nit}) no coincide con el del tercero (${thirdNit})`,
      });
      return;
    }

    if (!validInvoiceNumbers.value.includes(numFac)) {
      errors.push({
        fileName,
        message: `El número de factura (${numFac}) no es válido`,
      });
      return;
    }

    const key = `${nit}_${numFac}_${name}`;
    if (seenNames.has(key)) {
      errors.push({
        fileName,
        message: `El nombre (${name}) está duplicado para NIT_${numFac}_`,
      });
    } else {
      seenNames.add(key);
    }
  });

  return errors;
});

// Función para eliminar un archivo individual con error
const removeFileWithError = (fileName: string) => {
  const index = fileData.value.findIndex(item => item.file.name === fileName);
  if (index !== -1) {
    fileData.value.splice(index, 1);
  }
};

// Función para eliminar todos los archivos con error
const removeAllFilesWithErrors = () => {
  const errorFileNames = errorsFiles.value.map(error => error.fileName);
  fileData.value = fileData.value.filter(item => !errorFileNames.includes(item.file.name));
};
</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" max-width="90rem" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" class="w-100" :disabled="isLoading">
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
          </VToolbar>
        </div>

        <VCardText class="d-flex justify-center">
          <ProgressBar ref="refProgressBar" :progress="progress" />
        </VCardText>

        <!-- Sección de errores mejorada -->
        <VCardText v-if="errorsFiles.length > 0" class="pa-4">
          <VAlert type="error" title="Errores encontrados"
            :text="`${errorsFiles.length} problema(s) detectado(s) en los nombres de los archivos:`" border="top"
            class="mb-4">
            <div class="d-flex justify-space-between align-center mb-2">
              <span></span>
              <VBtn color="error" variant="elevated" size="small" prepend-icon="mdi-delete" class="bg-white"
                @click="removeAllFilesWithErrors">
                Eliminar todos los errores
              </VBtn>
            </div>
            <VList density="compact" class="mt-2">
              <VListItem v-for="error in errorsFiles" :key="error.fileName" :title="error.fileName"
                :subtitle="error.message" prepend-icon="mdi-alert-circle-outline" class="text-error">
                <template #append>
                  <VBtn variant="text" color="error" size="small" @click="removeFileWithError(error.fileName)">
                    Eliminar
                  </VBtn>
                </template>
              </VListItem>
            </VList>
          </VAlert>
        </VCardText>

        <VCardText>
          <div class="flex">
            <div class="w-full h-auto relative">
              <div ref="dropZoneRef" class="cursor-pointer" @click="openFileDialog">
                <div v-if="fileData.length === 0"
                  class="d-flex flex-column justify-center align-center gap-y-3 px-6 py-10 border-dashed drop-zone">
                  <IconBtn variant="tonal" class="rounded-sm">
                    <VIcon icon="tabler-upload" />
                  </IconBtn>
                  <div class="text-base text-high-emphasis font-weight-medium">
                    Arrastra y suelta tu archivo aquí.
                  </div>
                  <span class="text-disabled">o</span>
                  <VBtn variant="tonal">Explorar archivos</VBtn>
                </div>

                <div v-else class="d-flex justify-center align-center gap-3 pa-8 border-dashed drop-zone flex-wrap">
                  <VRow class="match-height w-100">
                    <template v-for="(item, index) in fileData" :key="index">
                      <VCol cols="12" sm="3">
                        <VCard :ripple="false" border class="d-flex flex-column">
                          <VCardText @click.stop>
                            <VImg :src="item.url" />
                            <div class="mt-2">
                              <span class="clamp-text text-wrap">{{ item.file.name }}</span>
                              <span>{{ item.file.size / 1000 }} KB</span>
                            </div>

                            <div v-if="item.status === 'uploading'" class="mt-2">
                              <span>Cargando...</span>
                            </div>
                            <div v-if="item.status === 'completed'" class="mt-2 text-success">
                              <span>¡Archivo cargado exitosamente!</span>
                            </div>
                            <div v-if="item.status === 'failed'" class="mt-2 text-danger">
                              <span>Error al cargar el archivo</span>
                            </div>
                          </VCardText>
                          <VSpacer />
                          <VCardActions>
                            <VBtn variant="outlined" block @click.stop="fileData.splice(index, 1)">
                              Eliminar archivo
                            </VBtn>
                          </VCardActions>
                        </VCard>
                      </VCol>
                    </template>
                  </VRow>
                </div>
              </div>
            </div>
          </div>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn :loading="isLoading" color="secondary" variant="tonal" @click="handleDialogVisible()">Cancelar</VBtn>
          <VBtn :disabled="isLoading || errorsFiles.length > 0" :loading="isLoading" @click="submitForm()"
            color="primary">
            Guardar</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <ModalQuestion ref="refModalQuestion" />
  </div>
</template>
