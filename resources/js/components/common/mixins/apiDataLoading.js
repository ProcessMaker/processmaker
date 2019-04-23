import Loading from "../../../components/common/DataLoading";

export default {
    components: {
        Loading,
    },
    data() {
        return {
            apiDataLoading: true,
            apiNoResults: false,
        }
    },
    mounted() {
        this.$refs.loader.$on('api-data-loading', (val) => {
            this.apiDataLoading = val
        });
        this.$refs.loader.$on('api-data-no-results', (val) => {
            this.apiNoResults = val
        });
    }
}