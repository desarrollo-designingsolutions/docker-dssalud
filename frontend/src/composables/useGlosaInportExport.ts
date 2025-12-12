
export function useGlosaInportExport() {

  const isLoadingExcel = ref(false);

  const downloadExcel = async (params: {}) => {
    isLoadingExcel.value = true;
    const { data, response } = await useAxios("/invoiceAudit/exportDataToGlosasImportCsv").post(params)
    isLoadingExcel.value = false;

    if (response?.status == 200 && data && data.code == 200) {
      downloadExcelBase64(data.excel, "Servicios " + new Date().toLocaleDateString() + ".xlsx")
    }
  }

  return {
    downloadExcel,
    isLoadingExcel,
  }
}
