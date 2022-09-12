import Vue from 'vue';
import ExportManager from './components/ExportManager';
import ExportManagerView from './components/ExportManagerView';


const routes = [
    { 
        path: '/processes/:processId/export', 
        name: 'main', 
        component: ExportManagerView,
        props: route => (console.log('route', route), {
            processId: route.params.processId,
            routeName: 'main',
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