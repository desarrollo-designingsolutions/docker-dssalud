<script setup lang="ts">

import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();


const props = defineProps({
  assignment_batch_id: {
    type: String,
    required: false,
    default: null
  },
  third_id: {
    type: String,
    required: false,
    default: null
  },
  user_id: {
    type: [String, Number],
    required: false,
    default: null
  },
});

interface CountData {
  icon: string;
  color: string;
  title: string;
  value: string | number;
  isHover: boolean;
  progress: string | number,
  // to: any;
}

const countData = ref<CountData[]>([
  {
    "icon": 'tabler-user-shield',
    "color": 'primary',
    "title": 'Cantidad de Prestadores',
    "value": 0,
    "isHover": false,
    "progress": '0',
    // "to": { name: "User-List" },
  },
  {
    "icon": 'tabler-password-user',
    "color": 'info',
    "title": 'Facturas Pendientes',
    "value": 0,
    "isHover": false,
    "progress": '0',
    // "to": { name: "Project-List" },
  },
  {
    "icon": 'tabler-checklist',
    "color": 'primary',
    "title": 'Facturas Finalizadas',
    "value": 80,
    "isHover": false,
    "progress": '0',
    // "to": { name: "Task-List" },
  },
  {
    "icon": 'tabler-users',
    "color": 'success',
    "title": 'Total Facturas',
    "value": 0,
    "isHover": false,
    "progress": '0',
    // "to": { name: "Client-List" },
  },
  {
    "icon": 'tabler-users',
    "color": 'warning',
    "title": 'Porcentaje de Avance',
    "value": 0,
    "isHover": false,
    "progress": '0',
    // "to": { name: "Client-List" },
  },
]);

const loading = ref<boolean>(false)

onMounted(() => {
   getData()
});

const getData = async()=>{
  
  loading.value = true;
  const { data, response } = await useAxios(`/assignment/AssignmentCount`).post({
    assignment_batch_id: props.assignment_batch_id,
    third_id: props.third_id,
    user_id: props.user_id,
    company_id: authenticationStore.company.id,
  });
  loading.value = false;
  
  
  if (response.status == 200 && data) {
    countData.value[0].value = data.countNumberProviders
    countData.value[1].value = data.outstandingInvoices
    countData.value[2].value = data.finalizedInvoices
    countData.value[3].value = data.allInvoices
    countData.value[4].value = data.percentageProgress + '%'
    countData.value[4].progress = data.percentageProgress
  }
}
defineExpose({
  getData
})
</script>

<template>
  <div class="my-4">
    <VRow no-gutters class="d-flex justify-space-between" style="gap: 16px;">
      <VCol v-for="report in countData" :key="report.title" style="flex: 1; min-inline-size: 0;">
        <VSkeletonLoader v-if="loading" type="image, list-item-two-line" :loading="loading">
        </VSkeletonLoader>
        <VCard v-else
          :style="report.isHover ? `border-block-end-color: rgb(var(--v-theme-${report.color}))` : `border-block-end-color: rgba(var(--v-theme-${report.color}),0.38)`"
          @mouseenter="report.isHover = true" @mouseleave="report.isHover = false">
          <VCardText>
            <div class="d-flex align-center">
              <VAvatar rounded size="26" :color="report.color" variant="tonal" class="me-2">
                <VIcon size="18" :icon="report.icon" />
              </VAvatar>
              <h6 class="text-base font-weight-medium">
                {{ report.title }}
              </h6>
            </div>
            <h4 class="text-h4 my-3">
              {{ report.value }}
            </h4>
            <VProgressLinear :model-value="report.progress" :color="report.color" height="4" rounded rounded-bar />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
