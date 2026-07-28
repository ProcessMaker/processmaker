export default {};

export const api = window.ProcessMaker?.apiClient;

export const user = window.ProcessMaker.user;

export const useStore = () => Vue.globalStore;
