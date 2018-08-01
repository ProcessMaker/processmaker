<template>
    <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Forms List">
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center col-sm-12">
                        <h1 class="page-title">tasks</h1>
                        <input id="tasks-listing-search" v-model="filter" class="form-control col-sm-3"
                               placeholder="...">
                    </div>
                </div>
                <forms-list :uid="processUid" :filter="filter"></forms-list>
            </div>
        </div>

    </b-modal>
</template>

<script>
    import FormsList from "../formsList/forms-list"
    export default {
        components: [FormsList],
        props: ["processUid"],
        data() {
            return {
                filter: ""
            }
        },
        methods: {
            onHidden() {
                this.$emit('hidden')
            }
        },
        mounted() {
            debugger


        }
    };
</script>
<style lang="scss" scoped>

</style>
