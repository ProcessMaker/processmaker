import Vue from 'vue';
import VuePassword from "vue-password";
import ExportManager from './components/ExportManager';
import ExportManagerView from './components/ExportManagerView';

Vue.component("VuePassword", VuePassword);

const processName = document.head.querySelector('meta[name="export-process-name"]').content;

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