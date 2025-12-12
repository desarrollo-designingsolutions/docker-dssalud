<script setup lang="ts">

const { toast } = useToast();
const props = defineProps(["optionsTable", "optionsFilter"]);

const emit = defineEmits(["goView", "dataFilter", "responseData", "deleteSuccess"]);

const loading = ref<boolean>(false);

const optionsFilter = reactive<IOptionsFilter>({
  showBtnSearch: false,
  inputGeneral: {
    placeholder: "Busqueda general",
    relationsGeneral: {},
  },
  dialog: {
    cols: "12",
    width: 2024,
    inputs: [],
  }
});

// Función recursiva para copiar datos 
const recurrentdata = (target: any, source: any) => {
  for (const key in source) {
    if (Object.prototype.hasOwnProperty.call(source, key)) {
      const element = source[key];

      // Si el elemento es un array, copiamos sus elementos uno por uno
      if (Array.isArray(element)) {

        target[key] = element.map(item => {
          // Copiamos objetos anidados de manera recursiva dentro del array
          if (typeof item === 'object' && item !== null) {
            const newObj = {};
            recurrentdata(newObj, item);
            return newObj;
          }
          return item; // Copiamos valores primitivos directamente
        });
      }
      // Si el elemento es un objeto, llamamos recursivamente a recurrentdata
      else if (typeof element === 'object' && element !== null) {
        recurrentdata(target[key], element);
      } else {
        target[key] = element; // Copiamos valores primitivos directamente
      }
    }
  }
};

// Llamada inicial para copiar los datos de props.optionsFilter a optionsFilter
recurrentdata(optionsFilter, props.optionsFilter);

const validateOptionsFilter = () => {
  // Verificar si dialog.inputs tiene elementos
  const inputsNotEmpty = optionsFilter.dialog.inputs.length > 0;

  // Verificar si relationsGeneral tiene elementos (cualquier propiedad) 
  const relationsGeneralNotEmpty =
    Object.keys(optionsFilter.inputGeneral.relationsGeneral).length > 0;

  // Retornar true si ambos tienen elementos, de lo contrario false
  return inputsNotEmpty || relationsGeneralNotEmpty;
};

const isFilterDialog = validateOptionsFilter();


// posibles valores en los elementos de headers
// title,key,value,sortable,width,minWidth, align, fixed

const optionsTable = reactive<IOptionsTable>({
  url: null,
  searchQuery: {},
  params: {},

  showSelect: false,
  selected: [],
  expandOnClick: false,
  showHeader: true,
  headers: [],
  tableData: [],
  sortBy: [],
  multiSort: true,

  pagination: {
    show: true,
    rowPerPage: 10,
    lastPage: 0,
    totalData: 0,
    totalPage: 0,
    currentPage: 1,
  },

  actions: {
    styleShow: "dropdown", //normal,dropdown
    changeStatus: {
      show: true,
      url: null,
      dbField: "is_active",
      btnActive: {
        text: "Activo",
        value: 1,
        newFunction: undefined,
      },
      btnInactive: {
        text: "Inactivo",
        value: 0,
        newFunction: undefined,
      },
    },
    view: {
      show: true,
      url: null,
      tooltipText: "Visualizar",
      icon: "tabler-eye",
    },
    edit: {
      show: true,
      url: null,
      tooltipText: "Editar",
      icon: "tabler-pencil",
    },
    delete: {
      show: true,
      url: null,
      tooltipText: "Eliminar",
      icon: "tabler-trash",
    },
  },
});

// Llamada inicial para copiar los datos de props.optionsTable a optionsTable
recurrentdata(optionsTable, props.optionsTable);

// FUNCTION paginationData
const paginationData = (tableItems: number) => {
  const firstIndex = tableItems
    ? (optionsTable.pagination.currentPage - 1) *
    optionsTable.pagination.rowPerPage +
    1
    : 0;
  const lastIndex =
    tableItems +
    (optionsTable.pagination.currentPage - 1) *
    optionsTable.pagination.rowPerPage;
  return `Mostrando ${firstIndex} a ${lastIndex} de ${optionsTable.pagination.totalData} registros`;
};

// FUNCTION changeStatus
const changeStatus = async (
  obj: object,
  field: string,
  value: number | string
) => {
  const { data, response } = await useApi(`${optionsTable.actions.changeStatus.url}`).post({
    id: obj.id,
    value: value,
    field: field,
  });
  // if (data.value.code === 200) toast("Éxito", data.value.message, "success");
  if (response.value?.ok && data.value) {
    obj[field] = value
    obj.is_active = value
  };
};

//Funtion optional o change Status
type FuntionNewAction = (item: object) => void; // O reemplaza `object` con el tipo específico que estés usando
const callActionOrChangeStatus = (newFunction: FuntionNewAction, item: object, dbField: string, newValue: number | string) => {
  if (newFunction && typeof newFunction === 'function') {
    newFunction(item); // Llama a la acción si es una función
  } else if (newFunction === undefined) {
    changeStatus(item, dbField, newValue); // Solo llama a changeStatus si action está indefinido
  }
  // Si action es null, no se hace nada
};


// FUNCTION goView
const goView = (action: string = "create", id: number | undefined = undefined, obj: object | undefined = undefined) => {
  emit("goView", {
    action: action,
    id: id,
    obj: obj,
  })
}



// FUNCTION deleteData
const deleteData = async (id: number | string) => {
  loading.value = true;
  const { data, response } = await useApi(`${optionsTable.actions.delete.url}/${id}`).delete();
  loading.value = false;
  //if (data.value.code === 200) toast("Éxito", data.value.message, "success");
  if (response.value?.ok && data.value) {
    emit("deleteSuccess")
    await executeFetchTable()
  };
};

// FUNCTION fetchDataTable
const fetchDataTable = async (dataFilter: object = {}) => {
  loading.value = true;
  const { data, response } = await useApi<any>(
    createUrl(`${optionsTable.url}`, {
      query: dataFilter,
    })
  );
  loading.value = false;

  if (data.value.code != 401 && response.value?.ok && data.value) {
    optionsTable.tableData = data.value.tableData;

    if (optionsTable.pagination.show) {
      optionsTable.pagination.lastPage = data.value.lastPage;
      optionsTable.pagination.totalData = data.value.totalData;
      optionsTable.pagination.totalPage = data.value.totalPage;
      optionsTable.pagination.currentPage = data.value.currentPage;
    }
  }

  emit("responseData", data.value)

};

const executeFetchTable = (page: number | null = null) => {
  if (page) optionsTable.pagination.currentPage = page;

  fetchDataTable({
    perPage: Number(optionsTable.pagination.rowPerPage),
    page: optionsTable.pagination.currentPage,
    searchQuery: optionsTable.searchQuery,
    sortBy: optionsTable.sortBy,
    ...optionsTable.params,
  });
};
const changeFilter = (data: object) => {
  optionsTable.searchQuery = data;
  executeFetchTable(1);
  emit("dataFilter", data);
};

// Update data table options
const updateSortBy = (sortBy: any) => {
  optionsTable.sortBy = [sortBy];
  executeFetchTable();
};

onMounted(async () => {
  if (optionsTable.url) {
    await executeFetchTable();
  }
});

//ModalQuestion
const refModalQuestion = ref()
const openModalQuestionDelete = (id: string) => {
  refModalQuestion.value.componentData.title = "¿Está seguro que desea eliminar el registro?"
  refModalQuestion.value.openModal(id)
}

defineExpose({
  executeFetchTable,
  fetchDataTable,
  changeFilter,
  deleteData,
  optionsTable,
  changeStatus,
  openModalQuestionDelete
});
</script>

<template>
  <div>

    <template v-if="isFilterDialog">
      <FilterDialog :optionsFilter="optionsFilter" @sendFilter="changeFilter" />
    </template>
    <VDataTable :multi-sort="optionsTable.multiSort" :show-select="optionsTable.showSelect"
      :expand-on-click="optionsTable.expandOnClick" v-model="optionsTable.selected" class="mt-5"
      :headers="optionsTable.headers" :items-per-page="optionsTable.pagination.rowPerPage"
      :page="optionsTable.pagination.currentPage" :items="optionsTable.tableData" :loading="loading"
      @update:sortBy="updateSortBy">
      <!-- Codigo que itera sobre las ranuras disponibles en el componente padre e individualmente rinde cada ranura con sus propias propiedades -->
      <template v-for="(_, name) in $slots" #[name]="slotProps">
        <slot :name="name" v-bind="slotProps || {}" />
      </template>
      <!-- Codigo que itera sobre las ranuras disponibles en el componente padre e individualmente rinde cada ranura con sus propias propiedades -->

      <template v-slot:headers="{
        columns,
        isSorted,
        getSortIcon,
        toggleSort,
        selectAll,
        someSelected,
        allSelected,
      }">
        <tr v-show="optionsTable.showHeader">
          <template v-for="column in columns" :key="column.key">
            <td v-if="column.key == 'data-table-select'">
              <div>
                <VCheckbox :indeterminate="someSelected && !allSelected" :model-value="allSelected" color="primary"
                  @update:model-value="selectAll(!allSelected)"></VCheckbox>
              </div>
            </td>
            <td v-else>
              <div :class="`d-flex justify-${column.align}`">
                <span class="mr-2 cursor-pointer" @click="() => toggleSort(column)">{{ column.title }}</span>
                <template v-if="isSorted(column)">
                  <v-icon :icon="getSortIcon(column)"></v-icon>
                </template>
              </div>
            </td>
          </template>
        </tr>
      </template>

      <template #item.is_active="{ item, index }">
        <slot name="item.is_active" :item="item" :index="index">
          <div class="d-flex align-center" v-if="optionsTable.actions.changeStatus.show">
            <VChip color="success" v-if="
              item.is_active ==
              optionsTable.actions.changeStatus.btnActive.value
            "
              @click="callActionOrChangeStatus(optionsTable.actions.changeStatus.btnActive.newFunction, item, optionsTable.actions.changeStatus.dbField, optionsTable.actions.changeStatus.btnInactive.value)">
              <VIcon start size="16" icon="tabler-bell" />
              {{ optionsTable.actions.changeStatus.btnActive.text }}
              <VTooltip location="top" transition="scale-transition" activator="parent" text="Estado">
              </VTooltip>
            </VChip>

            <VChip color="error" v-else
              @click="callActionOrChangeStatus(optionsTable.actions.changeStatus.btnInactive.newFunction, item, optionsTable.actions.changeStatus.dbField, optionsTable.actions.changeStatus.btnActive.value)">
              <VIcon start size="16" icon="tabler-alert-circle" />
              {{ optionsTable.actions.changeStatus.btnInactive.text }}
              <VTooltip location="top" transition="scale-transition" activator="parent" text="Estado">
              </VTooltip>
            </VChip>
          </div>
        </slot>
      </template>

      <template #item.actions="{ item, index }">
        <div>
          <div v-if="optionsTable.actions.styleShow == 'dropdown'">
            <VBtn color="primary">
              <template #append>
                <VIcon icon="tabler-square-rounded-chevron-down"></VIcon>
              </template>
              Acciones
              <VMenu activator="parent">
                <VList>
                  <VListItem v-if="optionsTable.actions.view.show" @click="goView('view', item.id, item)">
                    <template #prepend>
                      <VIcon :icon="optionsTable.actions.view.icon" />
                    </template>
                    <span>Ver</span>
                  </VListItem>
                  <VListItem v-if="optionsTable.actions.edit.show" @click="goView('edit', item.id, item)">
                    <template #prepend>
                      <VIcon :icon="optionsTable.actions.edit.icon" />
                    </template>
                    <span>Editar</span>
                  </VListItem>
                  <VListItem v-if="optionsTable.actions.delete.show" @click="openModalQuestionDelete(item.id)">
                    <template #prepend>
                      <VIcon :icon="optionsTable.actions.delete.icon" />
                    </template>
                    <span>Eliminar</span>
                  </VListItem>

                  <slot name="item.actions2" :item="item" :index="index"></slot>

                </VList>
              </VMenu>
            </VBtn>
          </div>

          <div v-if="optionsTable.actions.styleShow == 'normal'">
            <VBtn v-if="optionsTable.actions.view.show" color="warning" icon class="mr-2" @click="goView('view', item)">
              <VIcon :icon="optionsTable.actions.view.icon" />
              <VTooltip location="top" transition="scale-transition" activator="parent"
                :text="optionsTable.actions.view.tooltipText">
              </VTooltip>
            </VBtn>

            <VBtn v-if="optionsTable.actions.edit.show" color="primary" icon class="mr-2" @click="goView('edit', item)">
              <VIcon :icon="optionsTable.actions.edit.icon" />
              <VTooltip location="top" transition="scale-transition" activator="parent"
                :text="optionsTable.actions.edit.tooltipText">
              </VTooltip>
            </VBtn>

            <VBtn v-if="optionsTable.actions.delete.show" color="error" icon class="mr-2"
              @click="openModalQuestionDelete(item.id)">
              <VIcon :icon="optionsTable.actions.delete.icon" />
              <VTooltip location="top" transition="scale-transition" activator="parent"
                :text="optionsTable.actions.delete.tooltipText">
              </VTooltip>
            </VBtn>
            <slot name="item.actions2" :item="item" :index="index"></slot>
          </div>
        </div>
      </template>

      <template #bottom>
        <VCardText class="pt-2" v-if="optionsTable.pagination.show">
          <VRow>
            <VCol lg="2" cols="3">
              <VTextField v-model="optionsTable.pagination.rowPerPage" @update:model-value="executeFetchTable(1)"
                label="Filas Por Página" type="number" min="-1" hide-details variant="underlined" />
            </VCol>

            <VContainer fluid class="d-flex align-center flex-wrap justify-space-between gap-4 py-3 px-5">
              <span class="text-sm text-disabled">
                {{ paginationData(optionsTable.tableData.length) }}
              </span>
              <VPagination v-model="optionsTable.pagination.currentPage" @click="executeFetchTable()" size="small"
                :total-visible="5" :length="optionsTable.pagination.lastPage" />
            </VContainer>
          </VRow>
        </VCardText>
      </template>

      <template #loading>
        <VSkeletonLoader type="table-row@10, divider" loading></VSkeletonLoader>
      </template>
      <template #no-data>
        <span>No se encontraron resultados</span>
      </template>
    </VDataTable>

    <ModalQuestion ref="refModalQuestion" @success="deleteData" />

  </div>
</template>
