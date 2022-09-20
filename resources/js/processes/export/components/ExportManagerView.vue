<template>
    <div class="container" id="exportProcess">
        <div class="row">
            <div class="col">
                <div class="card text-center">
                    <div class="card-header bg-light" align="left">
                        <h5>{{ $t('Export Process')}}</h5>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">{{ $t('You are about to export a Process.')}}</h5>
                        <p class="card-text">{{ $t('User assignments and sensitive Environment Variables will not be exported.')}}</p>
                    </div>
                    <div class="card-footer bg-light" align="right">
                        <button type="button" class="btn btn-outline-secondary" @click="onCancel">{{ $t('Cancel')}}</button>
                        <button type="button" class="btn btn-secondary ml-2" @click="onExport">{{ $t('Download')}}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['processId'],
    components: {},
    mixins: [],
    data() {
        return {
        }
    },
    methods: {
        onCancel() {
            window.location = '/processes';
        },
        onExport() {
            ProcessMaker.apiClient.post('processes/' + this.processId + '/export')
            .then(response => {
                window.location = response.data.url;
                ProcessMaker.alert(this.$t('The process was exported.'), 'success');
            })
            .catch(error => {
                ProcessMaker.alert(error.response.data.message, 'danger');
            });
        }
    },
}
</script>
