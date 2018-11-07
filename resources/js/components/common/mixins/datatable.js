/**
 * Default mix in for general data table behavior.  Defines look and feel of sorting,
 * pagination transformers, etc.
 *
 */
import Vuetable from "vuetable-2/src/components/Vuetable";
import Pagination from "../../../components/common/Pagination";

/*
 * Global adjustment parameters for moment.js.
 */
import moment from "moment"
import moment_timezone from "moment-timezone";
moment.tz.setDefault(window.ProcessMaker.user.timezone);
moment.defaultFormat= window.ProcessMaker.user.datetime_format;
/***********/

export default {
    components: {
        Vuetable,
        Pagination
    },
    created () {
        // Use our api to fetch our role listing
        this.fetch();
    },
    watch: {
        filter: _.debounce(function () {
            if (!this.loading) {
                this.fetch();
            }
        }, 250)
    },
    methods: {
        // Handler to properly format date/time columns according to localized format
        formatDate (value, format) {
            format = format || '';
            if (value) {
                return moment(value)
                    .format(format);
            }
            return "n/a";
        },
        // Data manager takes new sorting and calls our fetch method
        dataManager (sortOrder, pagination) {
            this.orderBy = sortOrder[0].field;
            this.orderDirection = sortOrder[0].direction;
            this.fetch();
        },
        // Handler to change what page of results we are on
        changePerPage (value) {
            this.perPage = value;
            this.fetch();
        },
        // Transformers our API meta data to a format understood by vuetable 2
        transform (data) {
            // Clean up fields for meta pagination so vue table pagination can understand
            data.meta.last_page = data.meta.total_pages;
            data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
            data.meta.to = data.meta.from + data.meta.count;
            return data;
        },
        // Handler to set pagination data on our pagination based off of data passed into vuetable
        onPaginationData (data) {
            this.$refs.pagination.setPaginationData(data);
        },
        // Handler to change the page based on events fired from our pagination component
        onPageChange (page) {
            if (page == "next") {
                this.page = this.page + 1;
            } else if (page == "prev") {
                this.page = this.page - 1;
            } else {
                this.page = page;
            }
            if (this.page <= 0) {
                this.page = 1;
            }
            if (this.page > this.data.meta.last_page) {
                this.page = this.data.meta.last_page;
            }
            this.fetch();
        }

    },
    data () {
        return {
            // The returned data that will be loaded into the vuetable
            data: [],
            // What page of results we are on
            page: 1,
            // How many items per page
            perPage: 10,
            // Our loading flag
            loading: false,
            // What column to order by (default of name)
            orderBy: "name",
            // What direction to order by (default of ascending)
            orderDirection: "asc",
            // Cancel token which should be stored from axios if you want to cancel the current in progress request
            cancelToken: null,
            css: {
                tableClass: "table table-hover",
                loadingClass: "loading",
                detailRowClass: "vuetable-detail-row",
                handleIcon: "grey sidebar icon",
                sortableIcon: "fas fa-sort",
                ascendingIcon: "fas fa-sort-up",
                descendingIcon: "fas fa-sort-down",
                ascendingClass: "ascending",
                descendingClass: "descending",
                renderIcon (classes, options) {
                    return `<i class="${classes.join(" ")}"></i>`;
                }
            }
        };
    }
};
