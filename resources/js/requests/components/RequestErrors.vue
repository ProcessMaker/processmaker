<template>
    <div class="data-table">
        <div class="card card-body table-card">
            <vuetable :api-mode="false"
                :fields='fields' :data="data" data-path="data">
                <template slot="element" slot-scope="props">
                    <span style='white-space: pre'>{{props.rowData.element_name}}</span>
                    (id: {{props.rowData.element_id}})
                </template>
            </vuetable>
        </div>
    </div>
</template>

<script>
    import datatableMixin from "../../components/common/mixins/datatable";
    import AvatarImage from "../../components/AvatarImage";
    import moment from "moment";
    Vue.component("avatar-image", AvatarImage);
    export default {
        mixins: [datatableMixin],
        props: ["data"],
        data() {
            return {
                additionalParams: "",
                fields: [
                    {
                        title: "Message",
                        name: "message",
                        sortField: "message",
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

        }
    };
</script>

