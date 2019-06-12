import DataLoading from "../../../components/common/DataLoading";

export default {
    components: {
        DataLoading,
    },
    data() {
        return {
            apiDataLoading: true,
            apiNoResults: false,
        }
    },
    computed: {
        shouldShowLoader() {
            return this.apiDataLoading || this.apiNoResults
        }
    },
    mounted() {
        ProcessMaker.EventBus.$on('api-data-loading', (val) => {
            this.apiDataLoading = val
        });
        ProcessMaker.EventBus.$on('api-data-no-results', (val) => {
            this.apiNoResults = val
        });
    }
}