<template>
    <div>
        TODO<!-- TODO: component to load the form -->
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
