
export function useAssignmentInportExport() {

  const isLoadingExcel = ref(false);

  const downloadExcel = async (params: {}) => {
    isLoadingExcel.value = true;
    const { data, response } = await useAxios("/assignment/exportDataToAssignmentImportCsv").post(params)
    
    if (response?.status == 200 && data && data.code == 200) {
      downloadExcelBase64(data.excel, "Paquetes " + new Date().toLocaleDateString() + ".xlsx")
    }
    isLoadingExcel.value = false;
  }

  return {
    downloadExcel,
    isLoadingExcel,
  }
}
