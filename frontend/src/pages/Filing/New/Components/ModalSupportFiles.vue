<script setup lang="ts">
import type { VForm } from 'vuetify/components/VForm';

import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
const authenticationStore = useAuthenticationStore();

const emits = defineEmits(["execute"]);

const titleModal = ref<string>("Cargar soportes");
const isDialogVisible = ref<boolean>(false);
const disabledFiledsView = ref<boolean>(false);
const isLoading = ref<boolean>(false);
const formValidation = ref<VForm>();
const uploadId = ref<string | null>(null);
const progress = ref<number>(0); // Definir progreso general
const countFilesRequest = ref<number>(0);


const form = ref({
  support_type_id: null as null | string,
  filing_invoice_id: null as null | string,
})

const handleClearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
}



const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const openModal = async (filing_invoice: any) => {
  handleClearForm();
  handleDialogVisible();
  resetValues();

  form.value.filing_invoice_id = filing_invoice.id
  titleModal.value = `Cargar soportes a la factura ${filing_invoice.invoice_number}`
};

const submitForm = async () => {
  const validation = await formValidation.value?.validate();
  if (!validation?.valid) return;

  if (fileData.value.length === 0) {
    refModalQuestion.value.componentData.isDialogVisible = true;
    refModalQuestion.value.componentData.showBtnCancel = false;
    refModalQuestion.value.componentData.btnSuccessText = 'Ok';
    refModalQuestion.value.componentData.title = 'Por favor, seleccione un archivo antes de continuar.';
    return;
  }

  isLoading.value = true; // Activar isLoading para mostrar la barra
  progress.value = 0; // Reiniciar progreso

  const formData = new FormData();
  fileData.value.forEach((fileItem) => {
    formData.append('files[]', fileItem.file);
    fileItem.status = 'uploading';
    fileItem.progress = 0; // Aunque no lo usemos en la UI, lo mantenemos por consistencia
  });
  formData.append('support_type_id', form.value.support_type_id.value);
  formData.append('support_type_code', form.value.support_type_id.code);
  formData.append('company_id', authenticationStore.company.id);
  formData.append('third_nit', authenticationStore.user.third.nit);
  formData.append('fileable_type', "FilingInvoice");
  formData.append('fileable_id', form.value.filing_invoice_id);

  try {
    const { data, response } = await useApi('/file/massUpload').post(formData);
    if (response.value?.ok && data.value) {
      uploadId.value = data.value.upload_id;
      countFilesRequest.value = fileData.value.length;
      listenToProgress(data.value.upload_id);
    }
  } catch (error) {
    console.error('Error al enviar archivos:', error);
    fileData.value.forEach((fileItem) => {
      fileItem.status = 'failed';
      fileItem.progress = undefined;
    });
    alert('Error al enviar los archivos');
    isLoading.value = false;
  }
};

const listenToProgress = (uploadId: string) => {
  window.Echo.channel(`upload-progress.${uploadId}`)
    .listen('.file-progress', (event: any) => {

      progress.value = Number(event.progress); // Actualizar progreso general

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

defineExpose({
  openModal,
  disabledFiledsView,
});

const refModalQuestion = ref();
const { dropZoneRef, fileData, open, error, resetValues } = useFileDrop(5);

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


</script>

<template>
  <div>

    <VDialog v-model="isDialogVisible" :overlay="false" max-width="90rem" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" :disabled="isLoading" class="w-100">
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
          </VToolbar>
        </div>

        <VCardText class="d-flex justify-center">
          <ProgressBar ref="refProgressBar" :progress="progress" />
        </VCardText>

        <VCardText :disabled="isLoading">
          <VForm ref="formValidation" @submit.prevent="() => { }">
            <VRow>
              <VCol cols="12" md="6">
                <AppSelectRemote url="/selectInfiniteSupportType" array-info="supportType" label="Tipos de soporte"
                  v-model="form.support_type_id" :rules="[requiredValidator]" />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>

        <VCardText :disabled="isLoading">
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
          <VBtn :disabled="isLoading" :loading="isLoading" @click="submitForm()" color="primary">Guardar</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <ModalQuestion ref="refModalQuestion" />
  </div>
</template>
