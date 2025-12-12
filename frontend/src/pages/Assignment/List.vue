<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { useRouter } from 'vue-router';

definePage({
  path: "assignmentList/:assignment_batch_id",
  name: "Assignment-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "assignmentBatche.list",
  },
});

const authenticationStore = useAuthenticationStore();

const router = useRouter();

const route = useRoute();
const assignment_batch_id = route.params.assignment_batch_id;

//TABLE
const refTableFull = ref()

const optionsTable = {
  url: "/assignment/paginateThirds/" + assignment_batch_id,
  paramsGlobal: {
    company_id: authenticationStore.company.id,
  },
  headers: [
    { key: 'nit', title: 'Nit', width: 100  },
    { key: 'name', title: 'Razón Social', width: 250 },
    { key: 'count_invoice_total', title: 'Cant Facturas' , width: 100},
    { key: 'count_invoice_pending', title: 'Cant Fact Pendientes' , width: 100},
    { key: 'count_invoice_finish', title: 'Cant Fact Finalizadas', width: 100 },
    { key: 'total_value_sum', title: 'Valores', width: 170 },
    { key: 'status', title: 'Estado', sortable: false, width: 100 },
    { key: 'user_names', title: 'Usuarios' , width: 250 },
    // { key: 'actions', title: 'Acciones', sortable: false, width: 100 },
  ],
  actions: {
    delete: { url: '/assignment/delete' }
  }
}


//FILTER
const optionsFilter = ref({
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

const goViewInvoiceAudit = (data: any = { id: null }) => {

  router.push({ name: "AssignmentInvoiceAudit-List", params: { assignment_batch_id: assignment_batch_id, third_id: data.id } })

}

const goViewAssignmentBatchesList = () => {

  router.push({ name: "AssignmentBatche-List" })

}

const tableLoading = ref(false); // Estado de carga de la tabla

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(null, false, true); // Forzamos la búsqueda
  }
};


const formatNames = (names: string | null) => {
  // Si names es null o una cadena vacía, devolver valores por defecto
  if (!names) {
    return {
      firstName: null,
      restNames: [],
    };
  }

  // Dividir la cadena por ", " para obtener un array de nombres
  const nameArray = names.split(', ').filter(name => name.trim() !== '');

  if (nameArray.length === 1) {
    // Si solo hay un nombre, devolverlo como firstName y restNames vacío
    return {
      firstName: nameArray[0],
      restNames: [],
    };
  } else if (nameArray.length > 1) {
    // Si hay más de un nombre, devolver el primero y el resto en un array
    return {
      firstName: nameArray[0],
      restNames: nameArray.slice(1),
    };
  } else {
    // Si el array está vacío después de dividir (caso improbable pero manejado)
    return {
      firstName: null,
      restNames: [],
    };
  }
};

</script>

<template>

  <CountAllData :assignment_batch_id="assignment_batch_id" />

  <VRow>
    <VCol>
      <VCard>
        <VCardTitle class="d-flex justify-space-between">
          <span>
            Prestadores
          </span>

          <div class="d-flex justify-end gap-3 flex-wrap ">
            <VBtn @click="goViewAssignmentBatchesList">
              Regresar
            </VBtn>
          </div>
        </VCardTitle>

        <VCardText>
          <FilterDialogNew :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
          </FilterDialogNew>
        </VCardText>

        <VCardText class="mt-2">
          <TableFullNew ref="refTableFull" :options="optionsTable" @update:loading="tableLoading = $event">

            <template #item.nit="{ item }">
              <div style="cursor: pointer;" @click="goViewInvoiceAudit({ id: item.id })">
                {{ item.nit }}
              </div>
            </template>

            <template #item.name="{ item }">
              <div style="cursor: pointer;" @click="goViewInvoiceAudit({ id: item.id })">
                {{ item.name }}
              </div>
            </template>

            <template #item.count_invoice_assignment="{ item }">
              <div style="cursor: pointer;" @click="goViewInvoiceAudit({ id: item.id })">
                {{ item.count_invoice_assignment }}
              </div>
            </template>

            <template #item.count_invoice_pending="{ item }">
              <div style="cursor: pointer;" @click="goViewInvoiceAudit({ id: item.id })">
                {{ item.count_invoice_pending }}
              </div>
            </template>

            <template #item.count_invoice_finish="{ item }">
              <div style="cursor: pointer;" @click="goViewInvoiceAudit({ id: item.id })">
                {{ item.count_invoice_finish }}
              </div>
            </template>

            <template #item.values="{ item }">
              <div style="cursor: pointer;" @click="goViewInvoiceAudit({ id: item.id })">
                {{ item.values }}
              </div>
            </template>

            <template #item.status="{ item }">
              <div style="cursor: pointer;" @click="goViewInvoiceAudit({ id: item.id })">
                {{ getInvoiceAuditStatus(item.status).title }}
              </div>
            </template>


            <template #item.user_names="{ item }">
              <div>
                <template v-if="item.count_users > 1">
                  {{ formatNames(item.user_names).firstName }}

                  <VBtn color="primary" variant="outlined"
                    v-if="formatNames(item.user_names).restNames.length > 0">
                    <template #prepend>
                      <VIcon icon="tabler-plus"></VIcon>
                    </template>
                    {{ item.count_users - 1 }}
                    <VMenu activator="parent">
                      <VList>
                        <VListItem v-for="(insurance, i) in formatNames(item.user_names).restNames" :key="i">
                          <span>{{ insurance }}</span>
                        </VListItem>
                      </VList>
                    </VMenu>
                  </VBtn>
                </template>

                <template v-if="item.count_users == 1">
                  {{ formatNames(item.user_names).firstName }}
                </template>
              </div>
            </template>

          </TableFullNew>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

</template>
