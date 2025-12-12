<script setup lang="ts">
const emits = defineEmits(["success", "cancel"]);

const componentData = reactive({
  isDialogVisible: false,
  isLoading: false,
  principalIcon: "tabler-alert-circle",
  title: "¿Está seguro que desea proceder con esta acción?",
  subTitle: "",
  html: null,
  btnSuccessText: "Si",
  btnSuccessIcon: "tabler-check",
  btnCancelText: "Cancelar",
  btnCancelIcon: "tabler-x",
  showActions: true,
  showBtnCancel: true,
  showBtnSuccess: true,
  dialogMaxWidth: '40rem',
  id: null as number | string | null,
});

const handleIsDialogVisible = () => {
  componentData.isDialogVisible = !componentData.isDialogVisible;
};

const openModal = async (id: number | string | null = null) => {
  handleIsDialogVisible();
  componentData.id = id
};

const handleSubmit = async () => {
  handleIsDialogVisible();
  emits("success", componentData.id);
};
const handleCancel = async () => {
  handleIsDialogVisible();
  emits("cancel");
};

defineExpose({
  openModal,
  componentData,
});
</script>
<template>
  <VDialog v-model="componentData.isDialogVisible" :max-width="componentData.dialogMaxWidth" persistent>
    <DialogCloseBtn @click="handleIsDialogVisible()" />
    <VCard :loading="componentData.isLoading">
      <VCardText class="text-center">
        <VIcon :icon="componentData.principalIcon" size="5rem" />
        <h2>{{ componentData.title }}</h2>
        <span>{{ componentData.subTitle }}</span>

      </VCardText>
      <VCardText class="text-center">
        <div v-if="componentData.html" v-html="componentData.html"></div>

      </VCardText>
      <VCardText class="d-flex justify-center" v-if="componentData.showActions">
        <VBtn v-if="componentData.showBtnCancel" variant="flat" color="secondary" class="mr-3" @click="handleCancel()">
          <VIcon start :icon="componentData.btnCancelIcon" />
          {{ componentData.btnCancelText }}
        </VBtn>
        <VBtn v-if="componentData.showBtnSuccess" variant="flat" @click="handleSubmit()">
          <VIcon start :icon="componentData.btnSuccessIcon" />
          {{ componentData.btnSuccessText }}
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
