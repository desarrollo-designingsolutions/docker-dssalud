export const COOKIE_MAX_AGE_1_YEAR = 365 * 24 * 60 * 60

export const API_KEY_EDITOR = import.meta.env.VITE_API_KEY_EDITOR;
export const BASE_BACK_STORAGE = `${import.meta.env.VITE_API_BASE_BACK}/storage/`;
export const ROLE_SUPERADMIN_UUID = "21626ff9-4940-4143-879a-0f75b46eadb7";


 export const StatusRipsEnum = {
  INCOMPLETE: 'INCOMPLETE',
  COMPLETED: 'COMPLETED',
  NOT_SENT: 'NOT SENT',
  IN_PROCESS: 'IN PROCESS',
  ERROR_ZIP: 'ZIP ERROR',
  ERROR_EXCEL: 'EXCEL ERROR',
  PROCESSED: 'PROCESSED',
  NOT_VALIDATED: 'NOT VALIDATED',
  PENDING_XML: 'PENDING XML',
  PENDING_EXCEL: 'PENDING EXCEL',
  VALIDATED: 'VALIDATED',
  ERROR_NIT: 'NIT ERROR',
  ERROR_XML: 'XML ERROR',
};

export const INVOICE_AUDIT_STATUS_MAP = {
  pending: { title: 'Pendiente' },
  finished: { title: 'Finalizado' },
};
