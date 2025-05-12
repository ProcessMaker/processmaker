export default {};

export const api = window.ProcessMaker?.apiClient;

export const user = window.ProcessMaker?.user;

export const i18n = window.ProcessMaker?.i18n;

export const t = (key) => window.ProcessMaker?.i18n.t(key);

export const alert = window.ProcessMaker?.alert;

export const defaultColumns = window.ProcessMaker?.defaultColumns;

export const { debounce } = _;

export const ellipsisPermission = window.ProcessMaker?.ellipsisPermission;
