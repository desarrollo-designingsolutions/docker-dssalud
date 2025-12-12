<script setup lang="ts">
import AgendaConciliation from "@/pages/Schedule/Components/AgendaConciliation.vue";
import Schedule from "@/pages/Schedule/Components/Schedule.vue";

definePage({
  path: "Schedule/Index",
  name: "Schedule-Index",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "schedule.menu",
  },
});

const currentTab = ref<number>(0);

</script>

<template>
  <div>
    <VTabs v-model="currentTab" class="v-tabs-pill">
      <VTab>Calendario</VTab>
      <VTab>Agenda de conciliaciones</VTab>
    </VTabs>
    <VCard class="mt-3">
      <VCardText>
        <VWindow v-model="currentTab">
          <VWindowItem>
            <Schedule />
          </VWindowItem>
          <VWindowItem>
            <AgendaConciliation />
          </VWindowItem>
        </VWindow>
      </VCardText>
    </VCard>
  </div>
</template>

