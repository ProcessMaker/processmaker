import { reactive, onMounted } from "vue";
import { useRouter } from "vue-router/composables";

const store = reactive({
  selectedInstance: {
    id: null,
    name: '',
  },
});

const loadInstance = () => {
  const router = useRouter();
  const instanceId = router.currentRoute.params.id;

  if (instanceId && (!store.selectedInstance.id || store.selectedInstance.id !== instanceId)) {
    window.ProcessMaker.apiClient.get(`/devlink/${instanceId}`).then((response) => {
      store.selectedInstance = response.data;
    });
  }
};

export { store, loadInstance }