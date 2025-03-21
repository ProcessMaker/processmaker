export default {};

export const api = window.ProcessMaker?.apiClient;

export const user = currentUser;

export const useStore = () => Vue.globalStore;
