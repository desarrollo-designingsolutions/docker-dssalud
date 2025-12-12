<script setup lang="ts">
import ModalShowFiles from "@/pages/InvoiceAudit/Components/ModalShowFiles.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

definePage({
  name: "InvoiceAudit-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "invoiceAudit.list",
  },
});

const authenticationStore = useAuthenticationStore();

//TABLE
const tableFull = ref()

const optionsTable = {
  url: "/invoiceAudit/list",
  headers: [
    { key: 'invoice_number', title: 'Número de factura' },
    { key: 'actions', title: 'Acciones' },
  ],
}


//FILTER
const optionsFilter = ref({
  inputGeneral: {
    relationsGeneral: {
      all: ["invoice_number"],
    },
  },
})

//ModalShowFiles
const refModalShowFiles = ref()

const openModalShowFiles = (item: any) => {
  refModalShowFiles.value.openModal(item.id, "InvoiceAudit")
}

</script>

<template>
  <div>

    <VCard>
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Auditorias Facturas
        </span>
      </VCardTitle>

      <VCardText class="mt-2">
        <TableFull ref="tableFull" :optionsTable="optionsTable" :optionsFilter="optionsFilter">

          <template #item.actions="{ item }">

            <VListItem @click="openModalShowFiles(item)">
              <template #prepend>
                <VIcon size="22" icon="tabler-square-rounded-arrow-right" />
              </template>
              <span>Soportes</span>
            </VListItem>


          </template>

        </TableFull>

        <ModalShowFiles ref="refModalShowFiles"></ModalShowFiles>
      </VCardText>
    </VCard>
  </div>
</template>
