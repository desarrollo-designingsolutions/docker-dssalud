<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
const authenticationStore = useAuthenticationStore();

const props = defineProps({
  filing_id: {
    type: String,
    required: true,
  }
})
interface CountData {
  icon: string;
  color: string;
  title: string;
  value: string | number;
  isHover: boolean;
  to: any;
}

const countData = ref<CountData[]>([]);
const isLoading = ref<boolean>(true)

const fetchCountData = async () => {
  isLoading.value = true;
  const { data, response } = await useApi<any>(
    createUrl(`/filingInvoice/countAllDataFiling`, {
      query: {
        company_id: authenticationStore.company.id,
        filing_id: props.filing_id,
      },
    })
  );

  isLoading.value = false;

  if (response.value?.ok && data.value) {
    countData.value = data.value.data

  }
}

onMounted(async () => {
  fetchCountData();
});

defineExpose({
  fetchCountData
});

</script>

<template>
  <AppCardActions v-if="isLoading" noActions v-model:loading="isLoading">
    <VCardText>
    </VCardText>
  </AppCardActions>

  <VRow v-if="!isLoading">
    <VCol v-for="(data, index) in countData" :key="index" cols="12" md="3" sm="6">

      <VCard :to="data.to"
        :style="data.isHover ? `border-block-end-color: rgb(var(--v-theme-${data.color}))` : `border-block-end-color: rgba(var(--v-theme-${data.color}),0.38)`"
        @mouseenter="data.isHover = true" @mouseleave="data.isHover = false">
        <VSkeletonLoader :loading="isLoading" type="avatar,list-item, list-item">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <VAvatar variant="tonal" :color="data.color" rounded>
                <VIcon :icon="data.icon" size="28" />
              </VAvatar>
              <h4 class="text-h4">
                {{ data.value }}
              </h4>
            </div>
            <div class="text-body-1 mb-1">
              {{ data.title }}
            </div>
          </VCardText>
        </VSkeletonLoader>
      </VCard>
    </VCol>
  </VRow>

</template>
