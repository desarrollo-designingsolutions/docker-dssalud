<script setup lang="ts">
import ModalContract from "@/pages/Filing/New/Components/ModalContract.vue";
import ModalErrors from "@/pages/Filing/New/Components/ModalErrors.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();
const emit = defineEmits(["reloadTable", "refreshCountAllDataInvoices"]);

// Referencias
const refModalQuestion = ref();
const refLoading = ref();
const refModalErrors = ref();
const refModalContract = ref();

const titleModal = ref<string>("Cargar archivo Json");
const isDialogVisible = ref<boolean>(false);
const disabledFiledsView = ref<boolean>(false);
const isLoading = ref<boolean>(false);
const progress = ref(0);
const filingData = ref<any>({ id: null, contract_id: null, validationTxt: null });

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const openModal = async (itemData: any) => {
  handleDialogVisible();
  resetValues();
  progress.value = 0;
  if (itemData) {
    filingData.value = cloneObject(itemData);
  }
};

const submitForm = async () => {
  if (fileData.value.length === 0) {
    if (refModalQuestion.value) {
      refModalQuestion.value.componentData.isDialogVisible = true;
      refModalQuestion.value.componentData.showBtnSuccess = false;
      refModalQuestion.value.componentData.btnCancelText = "Ok";
      refModalQuestion.value.componentData.title = "Por favor, seleccione un archivo antes de continuar.";
    }
    return;
  }

  const formData = new FormData();
  fileData.value.forEach((fileItem) => {
    formData.append("files[]", fileItem.file);
    fileItem.status = "uploading";
    fileItem.progress = 0;
  });

  formData.append("company_id", String(authenticationStore.company.id));
  formData.append("user_id", String(authenticationStore.user.id));
  formData.append("id", String(filingData.value.id));

  isLoading.value = true;
  const { response, data } = await useApi(`/filing/uploadJson`).post(formData);
  isLoading.value = false;

  if (response.value?.ok && data.value) {
    progress.value = 0;
    filingData.value = cloneObject(data.value);
    if (refLoading.value) {
      refLoading.value.startLoading();
    }
    startEchoChannel(data.value); // Inicia el canal dinámicamente
  }
};

defineExpose({
  openModal,
  disabledFiledsView,
});

// DropZone
const { dropZoneRef, fileData, open, error, resetValues } = useFileDrop(0, ["json"]);

watch(error, (newError) => {
  if (newError && refModalQuestion.value) {
    refModalQuestion.value.componentData.isDialogVisible = true;
    refModalQuestion.value.componentData.showBtnSuccess = false;
    refModalQuestion.value.componentData.btnCancelText = "Ok";
    refModalQuestion.value.componentData.title = newError;
  }
});

const openModalErrors = (item: any) => {
  if (refModalErrors.value) {
    refModalErrors.value.openModal(item);
  }
};

const openModalContract = async () => {
  if (!error.value && filingData.value.contract_id == null) {
    if (refModalContract.value) {
      refModalContract.value.openModal(filingData.value.id, filingData.value.type);
    }
  } else {
    isLoading.value = true;
    const { response, data } = await useApi(`/filing/updateContract`).post({
      filing_id: filingData.value.id,
      contract_id: filingData.value.contract_id,
    });
    isLoading.value = false;

    if (response.value?.ok && data.value) {
      emit("reloadTable"); // Recarga la tabla
      emit("refreshCountAllDataInvoices"); // Recarga Contadores
    }
  }
  handleDialogVisible();
};

// Canal dinámico
let channel = null;
const startEchoChannel = (data: any) => {
  if (channel) {
    stopEchoChannel(); // Limpia los eventos previos antes de volver a suscribirse
  }

  channel = window.Echo.channel(`filing.${data.id}`);
  channel.listen(".FilingFinishProcessJob", (event: any) => {

    setTimeout(() => {
      if (refLoading.value) {
        refLoading.value.stopLoading();
      }
    }, 1000);

    if (event.error_status.has_errors) {
      openModalErrors(event);
    } else {
      if (refModalQuestion.value) {
        refModalQuestion.value.componentData.isDialogVisible = true;
        refModalQuestion.value.componentData.title = "¿Deseas radicar los archivos?";
        refModalQuestion.value.componentData.subTitle = "Todo ha finalizado sin novedad...";
      }
      stopEchoChannel(); // Detiene los eventos específicos
    }
  }).listen(".FilingProgressEvent", (event: any) => {
    progress.value = Number(event.progress);
  });
};

const stopEchoChannel = () => {
  if (channel) {
    channel.stopListening(".FilingFinishProcessJob");
    channel.stopListening(".FilingProgressEvent");
    channel = null; // Limpia la referencia local
  }
};

onUnmounted(() => {
  stopEchoChannel(); // Limpia los eventos cuando el componente se desmonta
});

// Resto de las funciones
const cancelOperation = async (data: any) => {
  if (data.has_invoices) {
    updateValidationTxt();
  } else {
    deleteFiling();
  }
};

const deleteFiling = async () => {
  isLoading.value = true;
  const { response, data } = await useApi(`/filing/delete/${filingData.value.id}`).delete();
  isLoading.value = false;
  filingData.value.id = null

};

const updateValidationTxt = async () => {
  isLoading.value = true;
  const { response, data } = await useApi(`/filing/updateValidationTxt/${filingData.value.id}`).get();
  isLoading.value = false;
};

const openFileDialog = () => {
  error.value = null;
  open();
};

// Asegúrate de que cloneObject esté definido
function cloneObject<T>(data: T): T {
  if (data === undefined) {
    return undefined as T;
  }
  return JSON.parse(JSON.stringify(data));
}
</script>

<template>
  <div>
    <LoadingBase ref="refLoading" :progress="progress" :is-loading="isLoading" />
    <VDialog v-model="isDialogVisible" :overlay="false" max-width="90rem" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" :disabled="isLoading" class="w-100">
        <VToolbar color="primary">
          <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
        </VToolbar>
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
                      <VCol cols="12" md="4" lg="3">
                        <VCard :ripple="false" border class="d-flex flex-column">
                          <VCardText @click.stop>
                            <VImg :src="item.url" />
                            <div class="mt-2">
                              <span class="clamp-text text-wrap">{{ item.file.name }}</span>
                              <span>{{ item.file.size / 1000 }} KB</span>
                            </div>
                            <div v-if="item.status === 'uploading'" class="mt-2">
                              <VProgressCircular :size="24" :value="item.progress" color="primary" />
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
                            <VBtn variant="outlined" @click.stop="fileData.splice(index, 1)">
                              <VIcon icon="tabler-trash" />
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
          <VBtn :disabled="isLoading" :loading="isLoading" @click="submitForm()" color="primary">Continuar</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <ModalQuestion ref="refModalQuestion" @cancel="cancelOperation" @success="openModalContract" />
    <ModalErrors ref="refModalErrors" @cancel="cancelOperation" @continue="openModalContract" />
    <ModalContract ref="refModalContract" />
  </div>
</template>
