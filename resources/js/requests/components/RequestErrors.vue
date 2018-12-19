<template>
    <div class="data-table">
        <div class="card card-body table-card">
            <vuetable :api-mode="false"
                :fields='fields' :data="errors" data-path="data">
                <template slot="message" slot-scope="props">
                    <h5>{{props.rowData.message}}</h5>
                    <p class="error-body">{{props.rowData.body}}</p>
                </template>
                <template slot="datetime" slot-scope="props">
                    {{formatDate(props.rowData.created_at)}}
                </template>
                <template slot="element" slot-scope="props">
                    <div class='error-element'>{{props.rowData.element_name}}</div>
                    <span class="badge badge-secondary">{{props.rowData.element_id}}</span>
                </template>
            </vuetable>
        </div>
    </div>
</template>

<script>
    import datatableMixin from "../../components/common/mixins/datatable";
    import moment from "moment";
    export default {
        mixins: [datatableMixin],
        props: ["errors"],
        data() {
            return {
                additionalParams: "",
                fields: [
                    {
                        title: "Error",
                        name: "__slot:message",
                        sortField: "message",
                    },
                    {
                        title: "Time",
                        name: "__slot:datetime",
                        sortField: "created_at",
                    },
                    {
                        title: "Element",
                        name: "__slot:element",
                        sortField: "element_name",
                    },
                ],
            };
        },
        methods: {
            formatDate(date) {
                return moment(date).fromNow();
            },
            fetch() {

            }
        }
    };
</script>

<style lang="scss" scoped>
    p.error-body {
        white-space: pre;
    }
    .error-element {
        white-space: pre;
    }
</style>
