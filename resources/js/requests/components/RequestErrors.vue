<template>
    <div class="data-table">
        <div>
            <vuetable :api-mode="false"
                      :no-data-template="$t('No Data Available')"
                      :fields='fields'
                      :data="errors"
                      data-path="data">
                <template slot="message" slot-scope="props">
                    <h6>{{props.rowData.message}}</h6>
                    <pre class="error-body">{{props.rowData.body}}</pre>
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
                      title: () => this.$t("Error"),
                        name: "__slot:message",
                        sortField: "message",
                    },
                    {
                      title: () => this.$t("Time"),
                        name: "__slot:datetime",
                        sortField: "created_at",
                    },
                    {
                      title: () => this.$t("Element"),
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
            fetch() {}
        }
    };
</script>

<style lang="scss" scoped>
    .error-body {
      word-break: break-word;
      overflow-x: scroll;
      font-size: 87.5%;
      box-shadow: inset -8px -24px 6px -8px rgba(0,0,0.5);
      max-width: 1200px; // Not a fan of hard-coding this value, but it's equivalent to $screen-md-max
    }

    .error-element {
      white-space: pre;
    }
</style>
