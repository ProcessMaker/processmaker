<template>
    <div>
        TODO:<!-- TODO: component to load the form -->
        processUid: {{processUid}}<br>
        instanceUid: {{instanceUid}}<br>
        tokenUid: {{tokenUid}}<br>
        formUid: {{formUid}}<br>
        data: {{data}}<br>
    </div>
</template>

<script>

    export default {
        props: [
            'processUid',
            'instanceUid',
            'tokenUid',
            'formUid',

            'data',
        ],
        data() {
            return {
            };
        },
        mounted() {
        },
        methods: {
            submit() {
                ProcessMaker.apiClient.post(
                    'processes/' + this.processUid +
                    '/instances/' + this.instanceUid +
                    '/tokens/' + this.tokenUid +
                    '/complete', 
                    this.data
                )
            }
        }

    }

</script>

<style lang="scss" scoped>
</style>
