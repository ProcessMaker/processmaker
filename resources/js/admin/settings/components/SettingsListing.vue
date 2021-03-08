<template>
  <div class="settings-listing data-table">
    <basic-search class="mb-3" @submit="onSearch"></basic-search>
    <div class="card card-body table-card">
      <b-table
        class="settings-table table table-responsive-lg text-break m-0 h-100 w-100"
        :current-page="currentPage"
        :per-page="perPage"
        :items="dataProvider"
        :fields="fields"
        :sort-by="orderBy"
        :sort-desc="orderDesc"
        ref="table"
        :filter="searchQuery"
        show-empty
        responsive
      >
        <template v-slot:cell(name)="row">
          <div v-if="row.item.name">{{ $t(row.item.name) }}</div>
          <div v-else>{{ row.item.key }}</div>
          <b-form-text v-if="row.item.helper">{{ $t(row.item.helper) }}</b-form-text>
        </template>
        <template v-slot:cell(config)="row">
          <component :is="component(settings[row.index].format)" @input="onChange(settings[row.index])" v-model="settings[row.index].config" :readonly="settings[row.index].readonly"></component>
        </template>
        <template v-slot:bottom-row><div class="bottom-padding"></div></template>
        <template v-slot:emptyfiltered>
          <div class="h-100 w-100 text-center">
            No Data Available
          </div>
        </template>
      </b-table>
      <div v-if="totalRows" class="settings-table-footer text-secondary d-flex align-items-center p-2 w-100">
        <div class="flex-grow-1">
          <span v-if="totalRows">
            <span v-if="from == to">
              {{from}}
            </span>
            <span v-else>
              {{from}} - {{to}}
            </span>
            of {{totalRows}}
            <span v-if="totalRows == 1">
              {{ $t('Setting') }}
            </span>
            <span v-else>
              {{ $t('Settings') }}
            </span>
          </span>
        </div>
        <b-pagination
          class="m-0"
          v-model="currentPage"
          :total-rows="totalRows"
          :per-page="perPage"
          hide-ellipsis
          limit="3"
        >
          <template v-slot:first-text><i class="fas fa-step-backward fa-sm"></i></template>
          <template v-slot:last-text><i class="fas fa-step-forward fa-sm"></i></template>
          <template v-slot:prev-text><i class="fas fa-caret-left fa-lg" style="padding-top: 9px;"></i></template>
          <template v-slot:next-text><i class="fas fa-caret-right fa-lg" style="padding-top: 9px;"></i></template>
        </b-pagination>
      </div>
    </div>
  </div>
</template>


<script>
import { BasicSearch } from "SharedComponents";
import isPMQL from "../../../modules/isPMQL";
import SettingBoolean from './SettingBoolean';
import SettingText from './SettingText';
import SettingTextArea from './SettingTextArea';

export default {
  components: { BasicSearch, SettingBoolean, SettingText, SettingTextArea },
  props: ['group'],
  data() {
    return {
      currentPage: 1,
      fields: [],
      filter: '',
      from: 0,
      orderBy: 'name',
      orderDesc: false,
      perPage: 25,
      pmql: '',
      searchQuery: '',
      settings: [],
      to: 0,
      totalRows: 0,
      url: '/settings'
    };
  },
  computed: {
    orderDirection() {
      if (this.orderDesc) {
        return 'DESC';
      } else {
        return 'ASC';
      }
    },
  },
  mounted() {
    if (! this.group) {
      this.orderBy = "group";
      this.fields.push({
        key: "group",
        label: "Group",
        sortable: true,
        tdClass: "td-group",
      });
    }
    
    this.fields.push({
      key: "name",
      label: "Setting",
      sortable: true,
      tdClass: "td-name",
    });
    
    this.fields.push({
      key: "config",
      label: "Configuration",
      sortable: true,
      tdClass: "align-middle td-config",
    });
  },
  methods: {
    apiGet() {
      return ProcessMaker.apiClient.get(this.pageUrl(this.currentPage));
    },
    apiPut(setting) {
      return ProcessMaker.apiClient.put(this.settingUrl(setting.id), setting);
    },
    component(format) {
      switch (format) {
        case 'text':
        case 'boolean':
          return `setting-${format}`;
        default:
          return 'setting-text-area';
      }
    },
    dataProvider(context, callback) {
      this.filter = '';
      this.pmql = '';
      if (this.searchQuery.isPMQL()) {
        this.pmql = this.searchQuery;
      } else {
        this.filter = this.searchQuery;
      }
      this.orderDesc = context.sortDesc;
      this.orderBy = context.sortBy;
      this.apiGet().then(response => {
        this.settings = response.data.data;
        this.totalRows = response.data.meta.total;
        this.from = response.data.meta.from;
        this.to = response.data.meta.to;
        callback([]);
        this.$nextTick(() => {
          callback(this.settings);
        });
      });
    },
    onChange(setting) {
      this.$nextTick(() => {
        this.apiPut(setting).then(response => {
          if (response.status == 204) {
            this.$refs.table.refresh();
            this.$emit('refresh');
            ProcessMaker.alert(this.$t("The setting was updated."), "success");
          }
        })
      });
    },
    onSearch(query) {
      this.searchQuery = query;
    },
    pageUrl(page) {
      let url = `${this.url}?` +
        `page=${page}&` +
        `per_page=${this.perPage}&` +
        `order_by=${encodeURIComponent(this.orderBy)}&` +
        `order_direction=${this.orderDirection}&` +
        `filter=${this.filter}&` +
        `pmql=${this.pmql}&` +
        `group=${this.group}`;
      
      if (this.additionalPmql && this.additionalPmql.length) {
        url += `&additional_pmql=${this.additionalPmql}`;
      }

      return url;
    },
    settingUrl(id) {
      return `${this.url}/${id}`;
    }
  }
};
</script>

<style lang="scss">
@import '../../../../sass/colors';

.b-pagination {
  .page-item {
    .page-link {
      background-color: lighten($secondary, 44%);
      border-radius: 2px;
      color: $secondary;
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
        background-color: lighten($secondary, 40%);
      }
    }
    &.disabled {
      cursor: not-allowed;
      opacity: .5;
      .page-link {
        background-color: lighten($secondary, 44%);
      }
    }
    &.active {
      .page-link {
        background-color: lighten($secondary, 15%);
        color: white;
      }
      &:hover {
        .page-link {
          background-color: lighten($secondary, 11%);
        }
      }
    }
  }
}
</style>