export default {};

export const getRequestId = () => requestId;

export const getRequestStatus = () => request.status;

export const getComentableType = () => comentable_type;

export const getProcessName = () => request.process.name;

export const api = window.ProcessMaker?.apiClient;
