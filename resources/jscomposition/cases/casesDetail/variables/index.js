export default {};

export const api = window.ProcessMaker?.apiClient;

export const useStore = () => Vue.globalStore;

export const getRequestId = () => requestId;

export const getCaseNumber = () => request.case_number;

export const getRequest = () => request;

export const getRequestStatus = () => request.status;

export const getComentableType = () => comentable_type;

export const getProcessName = () => request.process.name;

export const getRequestCount = () => requestCount;

export const getErrors = () => errorLogs;

export const isErrors = () => request.status === "ERROR";

export const getInflightData = () => inflightData;

export const getXML = () => inflightData.bpmn;
