import Vue from 'vue';
import ImportManagerView from './components/ImportManagerView';
import ProcessDetailConfigs from './components/ProcessDetailConfigs';
import State from '../export/state';
import CustomExportView from "../export/components/CustomExportView";


const routes = [
    { 
        path: '/processes/import', 
        name: 'main', 
        component: ImportManagerView
    },
    { 
        path: '/processes/import/custom', 
        name: 'custom', 
        component: CustomExportView,
    },
    { 
        path: '/processes/import/new-process', 
        name: 'import-new-process', 
        component: ProcessDetailConfigs,
    },
];

new Vue({
    mixins: [State],
    router: window.ProcessMaker.Router,
    components: { },
    data() {
        return {
        }
    },
    beforeMount() {
        this.$root.isImport = true;
        this.$router.addRoutes(routes);
    },
    watch: {
        '$route': {
            handler() {
                // TODO: Add handlers route changes such as breadcrumb updates etc..
            }
        }
    }
}).$mount('#import-manager');