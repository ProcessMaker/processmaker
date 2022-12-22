import Vue from 'vue';
import VuePassword from "vue-password";
import ExportManager from './components/ExportManager';
import ExportManagerView from './components/ExportManagerView';
import CustomExportView from "./components/CustomExportView.vue";

Vue.component("VuePassword", VuePassword);

const processName = document.head.querySelector('meta[name="export-process-name"]').content;
const processDescription = document.head.querySelector('meta[name="export-process-description"]').content;
const processCategory = document.head.querySelector('meta[name="export-process-category"]').content;
const processManager = document.head.querySelector('meta[name="export-process-manager"]').content;
const processCreatedAt = document.head.querySelector('meta[name="export-process-created-at"]').content;
const processUpdatedAt = document.head.querySelector('meta[name="export-process-updated-at"]').content;
const processUpdatedBy = document.head.querySelector('meta[name="export-process-updated-by"]').content;


const routes = [
    { 
        path: '/processes/:processId/export', 
        name: 'main', 
        component: ExportManagerView,
        props: route => ({
            processId: route.params.processId,
            routeName: 'main',
            processName: processName,
          })
    },
    { 
        path: '/processes/:processId/export/custom',
        name: 'export-custom-process',
        component: CustomExportView,
        props: route => ({
            processId: route.params.processId,
            routeName: 'export-custom-process',
            processName: processName,
            processDescription: processDescription,
            processCategory: processCategory,
            processManager: processManager,
            processCreatedAt: processCreatedAt,
            processUpdatedAt: processUpdatedAt,
            processUpdatedBy: processUpdatedBy,
          }),
    },
];

new Vue({
    router: window.ProcessMaker.Router,
    components: { ExportManager },
    data() {
        return {
        }
    },
    beforeMount() {
        this.$router.addRoutes(routes);
    },
    watch: {
        '$route': {
            handler() {
                // TODO: Add handlers route changes such as breadcrumb updates etc..
            }
        }
    }
}).$mount('#export-manager');
