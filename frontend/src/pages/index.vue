<script setup lang="ts">
definePage({
  path: "/home",
  name: "Home",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "menu.home",
  },
})

import { useAuthenticationStore } from '@/stores/useAuthenticationStore';

const { company, user } = storeToRefs(useAuthenticationStore())

</script>

<template>
  <div>
    <VCard class="mb-6">
      <VCardTitle>Bienvenido a: {{ company.name }}</VCardTitle>
    </VCard>
  </div>
</template>
