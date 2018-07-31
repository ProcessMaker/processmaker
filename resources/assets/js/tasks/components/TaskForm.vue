<template>
    <div>
        TODO:<br><!-- TODO: component to load the form -->
        processUid: {{processUid}}<br>
        instanceUid: {{instanceUid}}<br>
        tokenUid: {{tokenUid}}<br>
        formUid: {{formUid}}<br>
        data: {{data}}<br>
        <vue-form-renderer @submit="submit" @update="update" :config="json" />
    </div>
</template>

<script>

    import VueFormRenderer from "@processmaker/vue-form-builder/src/components/vue-form-renderer";

    export default {
        components: {
            VueFormRenderer
        },
        props: [
            'processUid',
            'instanceUid',
            'tokenUid',
            'formUid',

            'data',
        ],
        data() {
            return {
                json: [
                    {
                        name: "Default",
                        items: []
                    }
                ]
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
            },
            update() {

            }
        }

    }

</script>

<style lang="scss" scoped>
</style>
