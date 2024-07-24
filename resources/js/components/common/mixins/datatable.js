/**
 * Default mix in for general data table behavior.  Defines look and feel of sorting,
 * pagination transformers, etc.
 *
 */
import Vuetable from "vuetable-2/src/components/Vuetable";
import Pagination from "../../../components/common/Pagination";
import FilterTableBodyMixin from "../../shared/FilterTableBodyMixin";

export default {
  mixins:[FilterTableBodyMixin],
  props: {
    fetchOnCreated: {
      default: true,
    },
  },
  components: {
    Vuetable,
    Pagination,
  },
  created() {
    // Use our api to fetch our role listing
    if (this.fetchOnCreated) {
      this.fetch();
    }
  },
  watch: {
    filter: _.debounce(function () {
      if (!this.loading) {
        this.page = 1;
        this.fetch();
      }
    }, 250),
  },
  methods: {
    // Handler to properly format date/time columns according to localized format
    formatDate(value, format) {
      format = format || "";
      if (value) {
        return window.moment(value)
          .format(format);
      }
      return "n/a";
    },
    // Handler to properly format date/time columns according to configuration of user
    formatDateUser(value, format) {
      let config = "";

      if (typeof ProcessMaker !== "undefined" && ProcessMaker.user && ProcessMaker.user.datetime_format) {
        if (format === "datetime") {
          config = ProcessMaker.user.datetime_format;
        }
        if (format === "date") {
          config = ProcessMaker.user.datetime_format.replace(/[\sHh:msaAzZ]/g, "");
        }
      }

      if (value) {
        if (moment(value).isValid()) {
          return window.moment(value)
            .format(config);
        }

        return value;
      }

      return "n/a";
    },
    // Data manager takes new sorting and calls our fetch method
    dataManager(sortOrder, pagination) {
      if (sortOrder[0].sortField !== undefined) {
        this.orderBy = sortOrder[0].sortField;
      } else {
        this.orderBy = sortOrder[0].field;
      }
      this.orderDirection = sortOrder[0].direction;
      this.fetch();
    },
    // Handler to change what page of results we are on
    changePerPage(value) {
      this.perPage = value;
      if (this.page * value > this.data.meta.total) {
        this.page = Math.floor(this.data.meta.total / value) + 1;
      }
      this.fetch();
    },
    // Transformers our API meta data to a format understood by vuetable 2
    transform(data) {
      // Clean up fields for meta pagination so vue table pagination can understand
      data.meta.last_page = data.meta.total_pages;
      data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
      data.meta.to = data.meta.from + data.meta.count;
      data.data = this.jsonRows(data.data);

      data.data.forEach((record) => {
        // format owner avatar if exists
        if (Object.hasOwn(record, "user")) {
          // eslint-disable-next-line no-param-reassign
          record.owner = this.formatAvatar(record.user);
        }
        // format category if exists
        if (Object.hasOwn(record, "category")) {
          // eslint-disable-next-line no-param-reassign
          record.category_list = this.formatCategory(record.categories);
        }
      });
      return data;
    },
    // Some controllers return each row as a json object to preserve integer keys (ie saved search)
    jsonRows(rows) {
      if (rows.length === 0 || !(_.has(_.head(rows), "_json"))) {
        if (!Array.isArray(rows) && typeof rows === "object") {
          return Object.values(rows);
        }
        return rows;
      }
      return rows.map((row) => JSON.parse(row._json));
    },
    // Handler to set pagination data on our pagination based off of data passed into vuetable
    onPaginationData(data) {
      this.$refs.pagination.setPaginationData(data);
    },
    // Handler to change the page based on events fired from our pagination component
    onPageChange(page) {
      if (page === "next") {
        this.page += 1;
      } else if (page === "prev") {
        this.page -= 1;
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
    },

  },
  data() {
    return {
      // The returned data that will be loaded into the vuetable
      data: [],
      // What page of results we are on
      page: 1,
      // How many items per page
      perPage: 15,
      // Our loading flag
      loading: false,
      // What column to order by (default of name)
      orderBy: "name",
      // What direction to order by (default of ascending)
      orderDirection: "asc",
      // Cancel token which should be stored from axios if you want to cancel the current in progress request
      cancelToken: null,
      css: {
        tableClass: "table table-hover table-responsive-lg text-break mb-0",
        loadingClass: "loading",
        detailRowClass: "vuetable-detail-row",
        handleIcon: "grey sidebar icon",
        sortableIcon: "fas fa-sort",
        ascendingIcon: "fas fa-sort-up",
        descendingIcon: "fas fa-sort-down",
        ascendingClass: "ascending",
        descendingClass: "descending",
        renderIcon(classes, options) {
          return `<i class="${classes.join(" ")}"></i>`;
        },
      },
      noDataTemplate() { return "asdfas#####1111"; },
    };
  },
};
