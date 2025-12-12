<script setup lang="ts">
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import { useRoleStore } from "@/pages/UserAccess/Role/stores/useRoleStore";
import { type VForm } from "vuetify/components";

import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
const authenticationStore = useAuthenticationStore();
const { company } = storeToRefs(authenticationStore)

const emits = defineEmits(['updateDataTable'])
const errorsBack = ref<IErrorsBack>({});

const useStore = useRoleStore();
const { selectedElements, arrayFather, selectedFather } = storeToRefs(useStore);

const componentData = reactive({
  isDialogVisible: false,
  isLoading: false,
  titleForm: "Crear",
  menus: [],
});

const formComponent = ref({
  id: null,
  description: null,
  company_id: null,
  permissions: [] as Array<Number>,
  type: [],
});

const handleIsDialogVisible = (isVisible: boolean = false) => {
  componentData.isDialogVisible = isVisible;
};

const handleClearFormComponent = (): void => {
  formComponent.value.id = null;
  formComponent.value.description = null;
  formComponent.value.permissions = [];
  formComponent.value.type = []
};

const roleTypeEnum_arrayInfo = ref([]);

const handleCreate = async () => {
  useStore.clearPermissionsSelected();
  handleClearFormComponent();
  componentData.isLoading = true;
  handleIsDialogVisible(true);
  componentData.titleForm = "Crear"

  const { data, isFetching } = await useApi(`role/create`).get();

  componentData.isLoading = isFetching.value;

  formComponent.value.company_id = company.value.id
  componentData.menus = data.value.menus;
  arrayFather.value = componentData.menus;
  roleTypeEnum_arrayInfo.value = data.value.roleTypeEnum_arrayInfo;
};

const handleEdit = async ({ id }: any) => {
  useStore.clearPermissionsSelected();
  handleClearFormComponent();
  componentData.isLoading = true;
  handleIsDialogVisible(true);
  componentData.titleForm = "Editar"



  const { response, data, isFetching } = await useApi(`role/${id}/edit`).get();


  if (response.value?.ok && data.value?.code == 200) {

    componentData.menus = data.value.menus;
    formComponent.value.id = data.value.role.id;
    formComponent.value.description = data.value.role.description;
    formComponent.value.company_id = data.value.role.company_id;
    formComponent.value.type = data.value.role.type;

    selectedElements.value = data.value.role.permissions;
    arrayFather.value = componentData.menus;
    roleTypeEnum_arrayInfo.value = data.value.roleTypeEnum_arrayInfo;

  }
  componentData.isLoading = isFetching.value;
};

const handleSubmit = async () => {
  formComponent.value.permissions = selectedElements.value;

  const validation = await formRole.value?.validate();
  if (validation?.valid) {
    componentData.isLoading = true;
    const { response, data, isFetching } = await useApi(`role`).post(
      formComponent.value
    );
    componentData.isLoading = isFetching.value;

    if (response.value?.ok && data.value?.code == 200) {
      emits('updateDataTable');
      handleIsDialogVisible(false);
    }
    if (data.value.code === 422) errorsBack.value = data.value.errors ?? {};

  }
};

const formRole = ref<VForm>();

defineExpose({
  handleCreate,
  handleEdit,
});
</script>
<template>
  <VDialog v-model="componentData.isDialogVisible" max-width="1080px" persistent transition="dialog-transition">
    <DialogCloseBtn @click="handleIsDialogVisible()" />
    <VCard>
      <VCardText class="pa-sm-10 pa-2">
        <h4 class="text-h4 text-center mb-2">
          {{ `${componentData.titleForm} rol` }}
        </h4>
        <p class="text-body-1 text-center mb-6">Establecer permisos del rol</p>
        <VForm ref="formRole">
          <VRow>
            <VCol cols="12">
              <VSkeletonLoader type="text" :loading="componentData.isLoading">
                <AppTextField :requiredField="true" label="Nombre de rol" v-model="formComponent.description"
                  :rules="[requiredValidator, maxCharacters(formComponent.description, 100)]" clearable
                  :error-messages="errorsBack.description" @input="errorsBack.description = ''" />
              </VSkeletonLoader>
            </VCol>
          </VRow>
        </VForm>
        <h5 class="text-h5 my-6">Funciones del rol</h5>
        <VRow :loading="componentData.isLoading">
          <VCol v-for="(item, index) in roleTypeEnum_arrayInfo" :key="index">
            <VCard border>
              <VCheckboxBtn v-model="formComponent.type" :value="item.value" :label="item.title"></VCheckboxBtn>
            </VCard>
          </VCol>
        </VRow>
        <h5 class="text-h5 my-6">Permisos de rol</h5>
        <VSkeletonLoader type="list-item, list-item, list-item, list-item, list-item, list-item"
          :loading="componentData.isLoading">
          <VRow>
            <VCol>
              <VList border>
                <template v-for="(
father, key
                  ) in componentData.menus" :key="key">

                  <ListGroup :father="father" :type="formComponent.type" />
                </template>
              </VList>
            </VCol>
          </VRow>
        </VSkeletonLoader>
      </VCardText>
      <VCardText class="d-flex align-center justify-center gap-4">
        <VBtn color="secondary" @click="handleIsDialogVisible()">Cancelar</VBtn>
        <VBtn prepend-icon="tabler-device-floppy" @click="handleSubmit()" :loading="componentData.isLoading">
          Guardar cambios</VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
