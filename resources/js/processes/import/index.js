import Vue from 'vue';
import ImportManagerView from './components/ImportManagerView';
import ProcessDetailConfigs from './components/ProcessDetailConfigs';


const routes = [
    { 
        path: '/processes/import', 
        name: 'main', 
        component: ImportManagerView
    },
    { 
        path: '/processes/import/new-process', 
        name: 'import-new-process', 
        component: ProcessDetailConfigs,
        props: route => ({
            file: route.params.file,
            routeName: 'import-new-process',
        })
    },
];

new Vue({
    router: window.ProcessMaker.Router,
    components: { },
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
}).$mount('#import-manager');