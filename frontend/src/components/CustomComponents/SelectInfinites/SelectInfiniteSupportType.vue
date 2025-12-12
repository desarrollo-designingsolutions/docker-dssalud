<script setup lang="ts">
// import SupportTypeForm from "@/pages/SupportType/Form.vue";

import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
const authenticationStore = useAuthenticationStore();

const props = defineProps({
  showBtnCreate: {
    type: Boolean,
    default: true,
    required: false,
  }
})

const emit = defineEmits(["execute", "update:modelValue"])

const elementId = computed(() => {
  const attrs = useAttrs();
  const _elementIdToken = attrs.id || attrs.label;
  return _elementIdToken
    ? `app-selectInifinite-field-${_elementIdToken}-${Math.random()
      .toString(36)
      .slice(2, 7)}`
    : undefined;
});

const isDialogVisible = ref<boolean>(false)
const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const openDialog = async () => {
  handleDialogVisible();
};
defineExpose({
})


const disabled = computed(() => useAttrs().disabled as boolean | undefined);
const title = ref<string>("Formulario proyectos")
const isLoading = ref<boolean>(false)
const valueSelectInfinite = ref()

//SELECT INFINITE

const supportType_arrayInfo = ref<Array<object>>([])
const supportType_countLinks = ref<number>(1)
const fetchSelectInifiniteSupportType = async (params: object) => {
  const { data, response } = await useApi("/selectInfiniteSupportType").post(params)

  if (response.value?.ok && data.value) {
    supportType_arrayInfo.value = data.value.supportType_arrayInfo
    supportType_countLinks.value = data.value.supportType_countLinks
  }
}
const select_supportType = useSelect(
  fetchSelectInifiniteSupportType,
  supportType_arrayInfo,
  supportType_countLinks,
  {
    company_id: authenticationStore.company.id
  }
);

const loadSelectInfinite = async () => {
  await fetchSelectInifiniteSupportType({
    company_id: authenticationStore.company.id
  })

  select_supportType.dataNueva.value = supportType_arrayInfo.value
  select_supportType.totalLinks.value = supportType_countLinks.value

}
onMounted(() => {
  valueSelectInfinite.value = useAttrs().modelValue
  emit("update:modelValue", useAttrs().modelValue);

  loadSelectInfinite()
})

const getDataSaved = (data: any) => {
  if (isObject(data)) {
    const title = `${data.name}`
    valueSelectInfinite.value = {
      value: data.id,
      title: title,
    }
    emit("update:modelValue", valueSelectInfinite.value);

    loadSelectInfinite()
  }

  handleDialogVisible()

}

</script>

<template>
  <div>
    <SelectInfinite closable-chips class="mt-1" :loading="isLoading" returnObject
      @update:modelValue="emit('update:modelValue', $event)" v-model="valueSelectInfinite" :select="select_supportType"
      clearable v-bind="{
        ...$attrs,
        id: elementId,
      }">


      <!-- <template #append  >
        <VBtn v-if="props.showBtnCreate" icon color="success" @click="openDialog()">
          <VIcon icon="tabler-plus" />
        </VBtn>
      </template> -->

    </SelectInfinite>

    <!-- <VDialog v-model="isDialogVisible" :overlay="false" max-width="1400px" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :title="title" :loading="isLoading" :disabled="isLoading">
        <VCardText>
          <SupportTypeForm @closeModal="getDataSaved" :btnBack="false" :saveAndCloseModal="true" :btnCancel="true" />
        </VCardText>
      </VCard>
    </VDialog> -->

  </div>
</template>
