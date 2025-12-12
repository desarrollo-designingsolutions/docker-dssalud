<script setup lang="ts">
import { useRoleStore } from "@/pages/UserAccess/Role/stores/useRoleStore";
import { toRef } from 'vue'; // Importa toRef

const roleStore = useRoleStore();
const { selectedElements } = storeToRefs(roleStore);

const props = defineProps({
  father: {
    required: true,
    type: Object,
  },
  type: {
    required: false,
    type: Array,
    default: [],
  },
});

// Mantén la reactividad con toRef
const type = toRef(props, 'type');

// Verifica si todos los permisos DIRECTOS del grupo están seleccionados
const areAllDirectPermissionsSelected = (father: any) => {
  return father.permissions.every(perm =>
    roleStore.selectedElements.includes(perm.id) && perm.id !== 1
  );
};

// Alterna la selección de todos los permisos DIRECTOS del grupo
const toggleAllDirectPermissions = (father: any) => {
  const allSelected = areAllDirectPermissionsSelected(father);
  if (allSelected) {
    roleStore.deselectAllPermissions(father.id);
  } else {
    roleStore.selectAllPermissions(father.id);
  }
};

// const operatorGroups = ["1", "3", "4", "7"]; // IDs de los grupos afectados
// const mechanicGroups = ["1", "14", "18"]; // IDs de los grupos afectados

// watch(type, (newVal) => {
//   if (newVal.includes('ROLE_TYPE_001')) {
//     roleStore.checkPermissionsSelected(operatorGroups);
//   } else {
//     roleStore.removePermission(operatorGroups);
//   }

//   if (newVal.includes('ROLE_TYPE_002')) {
//     roleStore.checkPermissionsSelected(mechanicGroups);
//   } else {
//     roleStore.removePermission(mechanicGroups);
//   }
// });

</script>

<template>
  <VListGroup>
    <template #activator="{ props }">
      <VListItem v-bind="props" :title="father.title" :prepend-icon="father.icon" />
    </template>

    <VListItem>
      <template v-for="(permission, index) in father.permissions" :key="index">
        <!-- Checkbox "Todos" (solo para el primer elemento y si no es el permiso especial) -->
        <VSwitch v-if="index === 0 && permission.id !== 1" class="mb-2" label="Todos"
          :model-value="areAllDirectPermissionsSelected(father)"
          @update:model-value="toggleAllDirectPermissions(father)" />

        <!-- Checkbox individual -->
        <VSwitch :disabled="permission.id === 1" class="my-0 py-0" :label="permission.description"
          :value="permission.id" v-model="selectedElements" />
      </template>
    </VListItem>

    <!-- Renderizar subgrupos (hijos) -->
    <template v-for="(child, key) in father.children" :key="key">
      <ListGroup :father="child" />
    </template>
  </VListGroup>
</template>
