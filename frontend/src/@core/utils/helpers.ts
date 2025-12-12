// 👉 IsEmpty
export const isEmpty = (value: unknown): boolean => {
  if (value === null || value === undefined || value === '')
    return true

  return !!(Array.isArray(value) && value.length === 0)
}

// 👉 IsNullOrUndefined
export const isNullOrUndefined = (value: unknown): value is undefined | null => {
  return value === null || value === undefined
}

// 👉 IsEmptyArray
export const isEmptyArray = (arr: unknown): boolean => {
  return Array.isArray(arr) && arr.length === 0
}

// 👉 IsObject
export const isObject = (obj: unknown): obj is Record<string, unknown> =>
  obj !== null && !!obj && typeof obj === 'object' && !Array.isArray(obj)

// 👉 IsToday
export const isToday = (date: Date) => {
  const today = new Date()

  return (
    date.getDate() === today.getDate()
    && date.getMonth() === today.getMonth()
    && date.getFullYear() === today.getFullYear()
  )
}




export const descargarArchivo = (path: string, name: string | null = null) => {

  // Divide la URL por las barras diagonales '/' para obtener las partes
  const partesURL = path.split('/');

  // Toma la última parte que debería ser el nombre del archivo
  const nombreArchivo = partesURL[partesURL.length - 1];

  // Dividir el nombre del archivo por el punto '.' para obtener la extensión
  const partesNombreArchivo = nombreArchivo.split('.');
  const extension = partesNombreArchivo[partesNombreArchivo.length - 1];

  let nameComplete = name + '.' + extension
  if (name == null) {
    nameComplete = nombreArchivo
  }

  // Código para descargar el archivo
  const link = document.createElement('a');
  link.href = path;
  link.target = "_blank"
  link.setAttribute('download', nameComplete);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}



export const openPdfBase64 = (pdfBase64: string) => {
  // Crear una nueva ventana
  const newWindow = window.open('', '_blank');

  // Verificar si la ventana emergente fue bloqueada por el navegador
  if (newWindow) {
    // Crear un documento HTML en la ventana emergente
    const html = `
      <html>
        <head>
          <title>PDF Viewer</title>
        </head>
        <body>
          <embed width="100%" height="100%" src="data:application/pdf;base64,${pdfBase64}" type="application/pdf">
        </body>
      </html>
    `;

    // Escribir el contenido HTML en la ventana emergente
    newWindow.document.write(html);
  } else {
    // Mostrar un mensaje de error si la ventana emergente fue bloqueada
    alert('La ventana emergente fue bloqueada por el navegador. Asegúrate de permitir ventanas emergentes.');
  }
}

export const downloadExcelBase64 = (
  base64: string,
  fileName: string = "excel",
  ext: string = ".xlsx",
) => {
  if (base64) {
    const link = document.createElement("a");
    link.href = `data:application/vnd.ms-excel;base64,${base64}`;
    link.download = fileName + ext;
    link.click();
  }
};


export function onlyNumbersKeyPress(event: any) {
  if ((!/[0-9,-]/.test(event.key))) {
    event.preventDefault();
  }
}
export function storageBack(path: string) {
  return BASE_BACK_STORAGE + path
}

export function cloneObject(data: any) {
  if (data === undefined) {
    return null; // O podrías devolver {} si prefieres un objeto vacío
  }
  return JSON.parse(JSON.stringify(data));
}

export function getInvoiceAuditStatus(value: string) {
  // Retornar el título y el color, o un valor predeterminado si no coincide
  return INVOICE_AUDIT_STATUS_MAP[value] || { title: 'Desconocido' };
}
