import { reactive } from "vue";

const store = reactive({
  selectedInstance: {
    id: null,
    name: "",
  },
});

const loadInstance = () => {
  const router = window.ProcessMaker?.Router;
  if (!router?.currentRoute?.value) {
    return;
  }
  const instanceId = router.currentRoute.value.params.id;

  if (instanceId && (!store.selectedInstance.id || store.selectedInstance.id !== instanceId)) {
    window.ProcessMaker.apiClient.get(`/devlink/${instanceId}`).then((response) => {
      store.selectedInstance = response.data;
    });
  }
};

export { store, loadInstance };
