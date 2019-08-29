<template>
    <div class="jumbotron jumbotron-fluid">
        <div class="container text-center">

            <div v-if="noResults">
                <div class="icon-container">
                    <div v-if="emptyIconType() === 'beach'">
                        <i class="fas fa-umbrella-beach"></i>
                    </div>
                    <div v-if="emptyIconType() === 'noData'">
                        <i class="fas fa-umbrella-beach"></i>
                    </div>
                </div>
                <h3 class="display-6">{{ emptyText() }}</h3>
                <p class="lead">{{ emptyDescText() }}</p>
            </div>
            <div v-else-if="error">
                <div class="icon-container">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="display-6">{{ errorTitleText() }}</h3>
                <p class="lead">{{ errorDescText() }}</p>
            </div>
            <div v-else>
                <div class="icon-container">
                    <div v-if="iconType() === 'gear'">
                        <svg class="lds-gear" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid"><g transform="translate(50 50)">
                        <g transform="rotate(248.825)">
                        <animateTransform attributeName="transform" type="rotate" values="0;360" keyTimes="0;1" dur="4.7s" repeatCount="indefinite"></animateTransform><path d="M37.43995192304605 -6.5 L47.43995192304605 -6.5 L47.43995192304605 6.5 L37.43995192304605 6.5 A38 38 0 0 1 35.67394948182593 13.090810836924174 L35.67394948182593 13.090810836924174 L44.33420351967032 18.090810836924174 L37.83420351967032 29.34914108612188 L29.17394948182593 24.34914108612188 A38 38 0 0 1 24.34914108612188 29.17394948182593 L24.34914108612188 29.17394948182593 L29.34914108612188 37.83420351967032 L18.090810836924184 44.33420351967032 L13.090810836924183 35.67394948182593 A38 38 0 0 1 6.5 37.43995192304605 L6.5 37.43995192304605 L6.500000000000001 47.43995192304605 L-6.499999999999995 47.43995192304606 L-6.499999999999996 37.43995192304606 A38 38 0 0 1 -13.09081083692417 35.67394948182593 L-13.09081083692417 35.67394948182593 L-18.09081083692417 44.33420351967032 L-29.34914108612187 37.834203519670325 L-24.349141086121872 29.173949481825936 A38 38 0 0 1 -29.17394948182592 24.34914108612189 L-29.17394948182592 24.34914108612189 L-37.83420351967031 29.349141086121893 L-44.33420351967031 18.0908108369242 L-35.67394948182592 13.090810836924193 A38 38 0 0 1 -37.43995192304605 6.5000000000000036 L-37.43995192304605 6.5000000000000036 L-47.43995192304605 6.500000000000004 L-47.43995192304606 -6.499999999999993 L-37.43995192304606 -6.499999999999994 A38 38 0 0 1 -35.67394948182593 -13.090810836924167 L-35.67394948182593 -13.090810836924167 L-44.33420351967032 -18.090810836924163 L-37.834203519670325 -29.34914108612187 L-29.173949481825936 -24.34914108612187 A38 38 0 0 1 -24.349141086121893 -29.17394948182592 L-24.349141086121893 -29.17394948182592 L-29.349141086121897 -37.834203519670304 L-18.0908108369242 -44.334203519670304 L-13.090810836924195 -35.67394948182592 A38 38 0 0 1 -6.500000000000005 -37.43995192304605 L-6.500000000000005 -37.43995192304605 L-6.500000000000007 -47.43995192304605 L6.49999999999999 -47.43995192304606 L6.499999999999992 -37.43995192304606 A38 38 0 0 1 13.090810836924149 -35.67394948182594 L13.090810836924149 -35.67394948182594 L18.090810836924142 -44.33420351967033 L29.349141086121847 -37.83420351967034 L24.349141086121854 -29.17394948182595 A38 38 0 0 1 29.17394948182592 -24.349141086121893 L29.17394948182592 -24.349141086121893 L37.834203519670304 -29.349141086121897 L44.334203519670304 -18.0908108369242 L35.67394948182592 -13.090810836924197 A38 38 0 0 1 37.43995192304605 -6.500000000000007 M0 -20A20 20 0 1 0 0 20 A20 20 0 1 0 0 -20"></path></g></g></svg>
                    </div>
                </div>
                <h3 class="display-6">{{ loadingText() }}</h3>
                <p class="lead">{{ descText() }}</p>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        data() {
            return {
                noResults: false,
                dataLoading: true,
                error: false,
            };
        },
        props: ['loading', 'desc', 'icon', 'empty', 'emptyDesc', 'emptyIcon', 'for'],
        watch: {
            dataLoading() {
                ProcessMaker.EventBus.$emit('api-data-loading', this.dataLoading)
            },
            noResults() {
                ProcessMaker.EventBus.$emit('api-data-no-results', this.noResults)
            }
        },

        mounted() {
            ProcessMaker.EventBus.$on('api-client-loading', (request) => {
                if (this.for && this.for.test(request.url)) {
                    this.dataLoading = true
                    this.error = false
                    this.noResults = false
                }
            })
            ProcessMaker.EventBus.$on('api-client-done', (response) => {
                if (response.config && this.for && this.for.test(response.config.url)) {
                    if (response.data && response.data.data && response.data.data.length === 0) {
                        this.noResults = true
                    }
                    this.dataLoading = false
                }
            })
            ProcessMaker.EventBus.$on('api-client-error', (error) => {
                ProcessMaker.alert(error, "danger");
                this.noResults = false
                this.error = true
            })
        },
        methods: {
            loadingText() {
                return this.loading ? this.loading : this.$t('Loading')
            },
            descText() {
                return this.desc ? this.desc: this.$t('Please wait while your content is loaded')
            },
            emptyText() {
                return this.empty? this.empty: this.$t('No Results')
            },
            emptyDescText() {
                return this.emptyDesc ? this.emptyDesc : ''
            },
            iconType() {
                return this.icon ? this.icon : 'gear'
            },
            emptyIconType() {
                return this.emptyIcon ? this.emptyIcon : 'none'
            },
            errorTitleText() {
                return this.$t('Sorry! API failed to load')
            },
            errorDescText() {
                return this.$t('Something went wrong. Try refreshing the application')
            }
        },
    }
</script>

<style lang="scss" scoped>
    .jumbotron {
        background-color: transparent;
    }
    .icon-container {
        display:inline-block;
        width: 5em;
        margin-bottom: 1em;

        i {
            color: #b7bfc5;
            font-size: 5em;
        }

        svg {
            fill: #b7bfc5;
        }
    }
</style>
