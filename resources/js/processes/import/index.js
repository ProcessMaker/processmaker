import Vue from 'vue';
import ImportManager from './components/ImportManager';
import ImportManagerView from './components/ImportManagerView';


const routes = [
    { 
        path: '/processes/import', 
        name: 'main', 
        component: ImportManagerView
    },
];

new Vue({
    router: window.ProcessMaker.Router,
    components: { ImportManager },
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