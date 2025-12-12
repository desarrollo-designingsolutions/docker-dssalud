<script setup lang="ts"> 
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();
const globalLoading = useGlobalLoading();
 

const form = ref({
  logo: null as string | null | File,
})


const logo = ref(useFileUpload())
// logo.value.allowedExtensions = ["csv"];
watch(logo.value, (newVal, oldVal) => {
  form.value.logo = null
  console.log("ewVal.imageFile",newVal.imageFile);
  
  if (newVal.imageFile) form.value.logo = newVal.imageFile
  console.log("form.value.logo",form.value.logo);
})

const changeFile = (event: Event, logo: any) => {
  // Definir un tipo para la respuesta
  const response = logo.handleImageSelected(event);
}

const loading = ref<boolean>(false)
const submitForm = async () => {
  try {
    loading.value=true
    let formData = new FormData();
    formData.append("file", form.value.logo);
    formData.append("company_id", String(authenticationStore.company.id));
    formData.append("user_id", String(authenticationStore.user.id));
  
    const { response, data } = await useAxios(`/conciliation/uploadFile`).post(formData);
  
    if (response.status == 200 && data) {
  
      if (data.status === 'success') {
        const success = globalLoading.startLoading(data.batch_id);
      }
    }
  } catch (error) {
    loading.value=false
    
  } finally{
    loading.value=false

  }
};

</script>

<template>
  <div>
  <VCard :loading="loading" :disabled="loading">
    <VCardText>
      <VFileInput @change="changeFile($event, logo)">
      </VFileInput> 
      <VBtn @click="submitForm()">cargar</VBtn>

    </VCardText>
  </VCard>
  </div>
</template>
