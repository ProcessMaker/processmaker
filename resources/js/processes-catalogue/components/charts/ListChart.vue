<template>
  <div
    v-if="rendered"
    class="list-chart w-100 h-100"
  >
    <div
      v-if="preview"
      class="list-chart-preview d-flex align-items-center justify-content-center w-100 h-100 rounded-sm text-center"
    >
      <i class="fas fa-fw fa-list" />
    </div>
    <div
      v-else
      class="list-chart-container w-100 h-100"
      :style="{ backgroundColor: backgroundColor }"
    >
      <div
        v-if="!config?.display?.pivot && config?.display?.searchable"
        :class="textVariant"
        class="list-chart-search-container d-flex flex-column flex-md-row"
        :style="{ height: pmqlInputHeight + 3 + 'px' }"
      >
        <div class="list-chart-search d-flex w-100">
          <pmql-input
            :search-type="savedSearchType ? savedSearchType + 's' : ''"
            class="mb-2 w-100"
            :value="query"
            :ai-enabled="false"
            :styles="pmqlInputStyles"
            @submit="onNLQConversion"
            @inputresize="onInputResize"
          />
        </div>
      </div>
      <b-table
        ref="table"
        class="list-chart-table m-0 h-100 w-100"
        :class="textVariant"
        :head-variant="headVariant"
        :tbody-tr-class="{ clickable: isClickable }"
        :sticky-header="stickyHeaderHeight"
        :striped="striped"
        :borderless="striped"
        :bordered="false"
        :outlined="false"
        no-border-collapse
        :current-page="currentPage"
        :per-page="perPage"
        hover
        :items="dataProvider"
        :fields="fields"
        :sort-by="orderBy"
        :sort-desc="orderDesc"
        :filter="filter"
        show-empty
        @row-clicked="onRowClicked"
        @row-middle-clicked="onRowMiddleClicked"
      >
        <template #cell(actions)="row">
          <b-link
            v-if="!config?.display?.pivot && config?.display?.linkButton"
            v-b-tooltip.hover.left
            v-bind="{ href: row.item.ProcessMaker__url }"
            class="p-2"
            :class="linkVariant"
            :title="$t('Open Record')"
          >
            <i class="fas fa-caret-square-right fa-lg fa-fw" />
          </b-link>
        </template>
        <template #emptyfiltered>
          <div class="list-chart-empty h-100 w-100 text-center">
            No Data Available
          </div>
        </template>
      </b-table>
      <div
        class="sticky-footer text-secondary d-flex align-items-center"
        :class="textVariant"
      >
        <div class="flex-grow-1">
          <span v-if="totalRows">
            <span v-if="from == to">
              {{ from }}
            </span>
            <span v-else> {{ from }} - {{ to }} </span>
            of {{ totalRows }}
            <span v-if="totalRows == 1">
              {{ $t("Item") }}
            </span>
            <span v-else>
              {{ $t("Items") }}
            </span>
          </span>
        </div>
        <b-pagination
          v-model="currentPage"
          class="m-0"
          :total-rows="totalRows"
          :per-page="perPage"
          :aria-label="$t('Pagination')"
          hide-ellipsis
          limit="3"
        >
          <template #first-text>
            <i class="fas fa-step-backward fa-sm" />
          </template>
          <template #last-text>
            <i class="fas fa-step-forward fa-sm" />
          </template>
          <template #prev-text>
            <i
              class="fas fa-caret-left fa-lg"
              style="padding-top: 9px"
            />
          </template>
          <template #next-text>
            <i
              class="fas fa-caret-right fa-lg"
              style="padding-top: 9px"
            />
          </template>
        </b-pagination>
      </div>
    </div>
  </div>
</template>

<script>
import { PmqlInput } from "SharedComponents";
import ChartDataMixin from "../mixins/ChartData.js";

export default {
  components: { PmqlInput },
  mixins: [ChartDataMixin],
  props: [
    "data",
    "options",
    "config",
    "preview",
    "additionalPmql",
    "savedSearchType",
  ],
  data() {
    return {
      originalData: null,
      rendered: false,
      chartData: null,
      chartOptions: null,
      currentPage: 1,
      dataFilter: "",
      dataPmql: "",
      pmqlInputHeight: 38,
      firstLoad: true,
      fields: [],
      perPage: 50,
      filter: "",
      from: 0,
      orderBy: "#",
      orderDirection: "DESC",
      to: 0,
      totalRows: 0,
      totalPages: 0,
      pages: {},
      preloads: {},
      query: "",
      url: null,
    };
  },
  computed: {
    stickyHeaderHeight() {
      const height = 38 + this.pmqlInputHeight;
      return `calc(0 - ${height}px)`;
    },
    pmqlInputStyles() {
      return {
        container: {
          backgroundColor: this.searchInputBackgroundColor,
          borderColor: this.searchInputBorderColor,
        },
        input: {
          color: this.searchInputTextColor,
        },
        pmql: {
          color: this.searchInputTextColor,
        },
        icons: {
          color: this.searchInputTextColor,
        },
        separators: {
          borderColor: this.searchInputBorderColor,
        },
      };
    },
    orderDesc() {
      return this.orderDirection.toLowerCase() === "desc";
    },
    backgroundColor() {
      return this.config?.colorScheme?.colors?.[0] || "";
    },
    searchInputBackgroundColor() {
      if (this.backgroundColor !== "#fff") {
        return "rgba(0, 0, 0, .3)";
      }
      return null;
    },
    searchInputBorderColor() {
      if (this.backgroundColor !== "#fff") {
        return this.backgroundColor;
      }
      return null;
    },
    searchInputTextColor() {
      if (this.backgroundColor !== "#fff") {
        return "#fff";
      }
      return null;
    },
    searchButtonBackgroundColor() {
      if (this.backgroundColor !== "#fff") {
        return this.backgroundColor;
      }
      return null;
    },
    headVariant() {
      if (this.backgroundColor === "#fff") {
        return "light";
      }
      return "dark";
    },
    textVariant() {
      const classes = [];
      if (this.backgroundColor === "#fff") {
        classes.push("list-chart-dark");
      } else {
        classes.push("list-chart-white");
      }

      if (!this.config?.display?.pivot && this.config?.display?.searchable) {
        classes.push("list-chart-searchable");
      }

      return classes.join(" ");
    },
    linkVariant() {
      if (this.backgroundColor === "#fff") {
        return "text-primary";
      }
      return "text-white";
    },
    striped() {
      return this.backgroundColor !== "#fff";
    },
    previewData() {
      return {
        datasets: [
          {
            data: [42],
            label: "Preview",
            icon: "chart-line",
          },
        ],
      };
    },
    previewOptions() {
      return {};
    },
    isClickable() {
      return !this.config?.display?.pivot && this.config?.display?.linkRow;
    },
  },
  watch: {
    data: {
      handler(value) {
        if (JSON.stringify(value) !== this.originalData) {
          this.clear();
          this.render();
        }
      },
      deep: true,
    },
    filter(filter) {
      this.currentPage = 1;
    },
  },
  mounted() {
    this.render();
    this.originalData = JSON.stringify(this.data);
  },
  methods: {
    uid(page, filter) {
      return `${page}::${encodeURIComponent(filter)}`;
    },
    pageFromUid(uid) {
      return parseInt(uid.split("::")[0]);
    },
    filterFromUid(uid) {
      return decodeURIComponent(uid.split("::")[1]);
    },
    dataProvider(context, callback) {
      const uid = this.uid(context.currentPage, context.filter);

      if (
        context.filter
        && typeof context.filter === "string"
        && context.filter.isPMQL()
      ) {
        this.dataPmql = context.filter;
        this.dataFilter = "";
      } else {
        this.dataFilter = context.filter;
        this.dataPmql = "";
      }

      this.setOrder(context);

      if (context.currentPage == 1 && !this.pages[uid] && this.firstLoad) {
        this.pages[uid] = this.chartData;
        this.url = this.chartData.meta.url;
        this.firstLoad = false;
      }

      if (!this.pages[uid]) {
        this.getPage(uid).then((response) => {
          if (response?.rows) {
            callback(response.rows);
          } else {
            callback([]);
          }
          this.$refs.table.$el.scrollTop = 0;
          this.updateMeta(uid);
          this.preload(uid);
        });
      } else {
        callback(this.pages[uid].rows);
        this.$refs.table.$el.scrollTop = 0;
        this.updateMeta(uid);
        this.preload(uid);
      }
    },
    setOrder(context) {
      const prevOrderBy = this.orderBy;
      const prevOrderDirection = this.orderDirection;
      this.orderBy = context.sortBy;
      this.orderDirection = context.sortDesc ? "DESC" : "ASC";
      if (
        prevOrderBy != this.orderBy
        || prevOrderDirection != this.orderDirection
      ) {
        this.clear();
      }
    },
    updateMeta(uid) {
      if (this.pages[uid]) {
        this.fields = this.pages[uid].header;
        this.totalRows = this.pages[uid].meta.total;
        this.totalPages = this.pages[uid].meta.total_pages;
        this.from = this.pages[uid].meta.from;
        this.to = this.pages[uid].meta.to;
      } else {
        this.totalRows = 0;
        this.totalPages = 0;
        this.from = 0;
        this.to = 0;
      }
    },
    getPage(uid) {
      const page = this.pageFromUid(uid);
      if (page <= this.totalPages) {
        if (!this.preloads[uid]) {
          return new Promise((resolve, reject) => {
            ProcessMaker.apiClient.get(this.pageUrl(page)).then((response) => {
              this.pages[uid] = this.transformChartData(response.data);
              resolve(this.pages[uid]);
            });
          });
        }
        return this.preloads[uid];
      }
    },
    preload(uid) {
      const currentPage = this.pageFromUid(uid);
      const nextPage = currentPage + 1;
      const nextUid = this.uid(nextPage, this.filterFromUid(uid));

      if (nextPage <= this.totalPages) {
        if (!this.pages[nextUid] && !this.preloads[nextUid]) {
          this.preloads[nextUid] = this.getPage(nextUid);
        }
      }
    },
    onRowClicked(record, index, event) {
      if (record.ProcessMaker__url) {
        if (!this.config?.display?.pivot && this.config?.display?.linkRow) {
          let target = "_self";
          if (event.metaKey || event.ctrlKey) {
            target = "_blank";
          }
          window.open(record.ProcessMaker__url, target);
        }
      }
    },
    onRowMiddleClicked(record, index, event) {
      if (record.ProcessMaker__url) {
        if (!this.config?.display?.pivot && this.config?.display?.linkRow) {
          window.open(record.ProcessMaker__url, "_blank");
        }
      }
    },
    pageUrl(page) {
      let url = `${this.url}?`
        + `data_page=${page}&`
        + `data_per_page=${this.perPage}&`
        + `data_order_by=${encodeURIComponent(this.orderBy)}&`
        + `data_order_direction=${this.orderDirection}&`
        + `data_filter=${this.dataFilter}&`
        + `data_pmql=${this.dataPmql}`;

      if (this.additionalPmql?.length) {
        url += `&additional_pmql=${this.additionalPmql}`;
      }

      return url;
    },
    clear() {
      this.pages = {};
      this.preloads = {};
      this.currentPage = 1;
    },
    render() {
      if (!this.preview) {
        this.renderChart(this.data, this.options);
      } else {
        this.renderPreview();
      }
      this.$emit("render");
    },
    describe() {
      return this.$t("Data Table");
    },
    renderPreview() {
      this.rendered = true;
    },
    renderChart(data, options) {
      this.rendered = false;
      this.$nextTick(() => {
        this.chartData = data;
        this.chartOptions = options;
        this.originalData = JSON.stringify(data);
        this.rendered = true;
      });
    },
    onInputResize(size) {
      this.pmqlInputHeight = size;
    },
    onNLQConversion(pmql) {
      this.filter = pmql;
    },
    runSearch() {
      this.filter = this.query;
    },
  },
};
</script>

<style lang="scss">
$animationLength: 500ms;
$footerHeight: 35px;

$searchHeight: 40px;
$searchPadding: 1px;

$listChartWhite: white !important;
$listChartDark: #212529 !important;

.list-chart {
  .list-chart-header {
    padding: 0;
    width: 120px;
  }

  .list-chart-icon {
    opacity: 0;
    transform: scale(1.75);
    transition: all $animationLength ease-in;
  }

  &[max-width~="220px"] {
    .list-chart-header {
      display: none !important;
    }
  }

  .list-chart-text {
    cursor: default;
    line-height: 1;
    opacity: 0;
    transform: translate(30px, 0);
    transition: all $animationLength ease-in;
  }

  .list-chart-metric {
    font-size: 3rem;
  }

  .list-chart-label {
    font-size: 1rem;
  }

  .list-chart-preview {
    color: #a6cee3;
    font-size: 1.9rem;
  }

  .list-chart-search-container {
    height: $searchHeight + $searchPadding;
    padding: $searchPadding;
    width: 100%;
    z-index: 3;

    &.list-chart-dark {
      background: #fff;
    }

    &.list-chart-white {
      background-color: rgba(0, 0, 0, 0.65) !important;

      @supports (-webkit-backdrop-filter: none) or (backdrop-filter: none) {
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
        background-color: rgba(0, 0, 0, 0.25) !important;
      }
    }

    .list-chart-search {
      .btn {
        height: $searchHeight;
      }

      .form-control,
      .form-control:active,
      .form-control:focus {
        border-bottom-right-radius: 0;
        border-right-width: 0;
        border-top-right-radius: 0;
        color: gray;
        height: $searchHeight;

        &.list-chart-white::placeholder {
          color: rgba(255, 255, 255, 0.4);
        }
      }

      .btn-search-run {
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 2px;
        border-top-left-radius: 0;
        border-top-right-radius: 2px;
      }
    }
  }

  .list-chart-table {
    font-size: 0.8rem;
    th {
      border-bottom-width: 1px !important;
      border-top-width: 0 !important;
    }

    &.list-chart-dark {
      .list-chart-table {
        th {
          background: #fff;
          color: $listChartDark;
          tr:hover {
            color: $listChartDark;
          }
        }
      }
    }

    &.list-chart-white {
      .list-chart-table {
        th {
          background-color: rgba(0, 0, 0, 0.65) !important;

          @supports (-webkit-backdrop-filter: none) or (backdrop-filter: none) {
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
            background-color: rgba(0, 0, 0, 0.25) !important;
          }
          color: $listChartWhite;
          tr:hover {
            color: $listChartWhite;
          }
        }
      }
    }
  }

  .clickable {
    cursor: pointer !important;
  }

  .b-table-bottom-row {
    opacity: 0;
  }

  .bottom-padding {
    display: block;
    height: $footerHeight;
    pointer-events: none;
  }

  .sticky-footer {
    background: white;
    bottom: 0;
    height: $footerHeight;
    padding-left: 8px;
    padding-right: 2px;
    position: relative !important;
    width: 100%;

    &.list-chart-dark {
      border-top: 1px solid #ddd;
    }
  }

  .b-pagination {
    .page-item {
      .page-link {
        background-color: gray;
        border-radius: 2px;
        color: grey;
        cursor: pointer;
        font-size: 12px;
        height: 29px;
        line-height: 29px;
        margin: 1px;
        padding: 0;
        text-align: center;
        width: 29px;
      }
      &:hover {
        .page-link {
          background-color: #c3c3c3;
        }
      }
      &.disabled {
        cursor: not-allowed;
        opacity: 0.5;
        .page-link {
          background-color: #c4c4c4;
        }
      }
      &.active {
        .page-link {
          background-color: #c2c2c2;
          color: white;
        }
        &:hover {
          .page-link {
            background-color: #c1c1c1;
          }
        }
      }
    }
  }
}
</style>
