<template>
  <div class="settings-listing data-table">
    <pmql-input
      class="mb-2"
      :search-type="'settings'"
      :value="pmql"
      :ai-enabled="false"
      :aria-label="$t('Advanced Search (PMQL)')"
      @submit="onNLQConversion">
      <template v-slot:right-buttons>
        <div v-if="topButtons" class="d-flex">
          <b-button
            v-for="(btn, index) in topButtons"
            v-bind="btn.ui.props"
            :key="`btn-${index}`"
            :ref="formatGroupName(btn.group)"
            :data-cy="btn.key"
            :disabled="false"
            class="ml-2 nowrap"
            @click="handler(btn)"
          >
            <b-spinner ref="b-spinner" small :hidden="true" />
            <i v-if="btn.ui.props.icon" :class="btn.ui.props.icon" />
            {{ btn.name }}
          </b-button>
        </div>
      </template>
    </pmql-input>

    <div class="p-5 text-center" v-if="shouldDisplayNoDataMessage">
      <h3>{{ noDataMessageConfig.name }}</h3>
      <small>{{noDataMessageConfig.helper }}</small>
    </div>

    <div v-else class="card card-body table-card">
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
          <div v-if="row.item.name" v-uni-id="row.item.id.toString()" class="capitalize">{{ $t(row.item.name) }}</div>
          <div v-else v-uni-id="row.item.id.toString()">{{ row.item.key }}</div>
          <b-form-text v-if="row.item.helper">{{ $t(row.item.helper) }}</b-form-text>
        </template>
        <template v-slot:cell(config)="row">
          <keep-alive>
            <component
              :is="component(row.item)"
              v-if="row.item && !isSwitch(row.item)"
              v-show="!savingSetting || savingSetting.id !== settings[row.index].id"
              :ref="`settingComponent_${row.index}`"
              :key="row.item.key"
              v-model="row.item.config"
              :setting="settings[row.index]"
              @saved="onChange"
            />
          </keep-alive>
          <i
            v-if="savingSetting && savingSetting.id === settings[row.index].id"
            class="fas fa-cog fa-spin text-secondary"
          />
        </template>
        <template v-slot:cell(actions)="row">
          <keep-alive>
            <component
              :is="component(row.item)"
              v-if="isSwitch(row.item)"
              :ref="`settingComponent_${row.index}`"
              :key="row.item.key"
              v-model="row.item.config"
              :setting="settings[row.index]"
              @saved="onChange"
            />
          </keep-alive>
          <template v-if="row.item && row.item.format !== 'boolean'">
            <span v-b-tooltip.hover :title="getTooltip(row)">
              <b-button
                v-uni-aria-describedby="row.item.id.toString()"
                variant="link"
                class="settings-listing-button"
                :data-cy="`edit-${row.item.key}`"
                :aria-label="$t('Edit')"
                :disabled="row.item.readonly"
                @click="onEdit(row)"
              >
                <i class="fa-lg fas fa-edit settings-listing-button mr-1" />
              </b-button>
            </span>
            <template v-if="row.item.key !== 'sso.default.login'">
              <b-button
                v-if="!disabledCopySetting(row)"
                v-uni-aria-describedby="row.item.id.toString()"
                v-b-tooltip.hover
                variant="link"
                class="settings-listing-button"
                :data-cy="`copy-${row.item.key}`"
                :aria-label="$t('Copy to Clipboard')"
                :disabled="row.item.key.includes('cdata.')"
                :title="$t('Copy to Clipboard')"
                @click="onCopy(row)"
              >
                <i class="fa-lg fas fa-copy settings-listing-button mr-1" />
              </b-button>

              <span v-b-tooltip.hover v-if="!['boolean', 'object', 'button'].includes(row.item.format) && enableDeleteSetting(row)" :title="$t('Delete')">
                <b-button
                  v-uni-aria-describedby="row.item.id.toString()"
                  variant="link"
                  class="settings-listing-button"
                  :data-cy="`delete-${row.item.key}`"
                  :aria-label="$t('Delete')"
                  @click="onDelete(row)"
                >
                  <i class="fa-lg fas fa-trash-alt settings-listing-button mr-1"></i>
                </b-button>
              </span>

              <span v-b-tooltip.hover v-else-if="!['boolean', 'object', 'button'].includes(row.item.format) && !disabledDeleteSetting(row)" :title="$t('Clear')">
                <b-button
                  :aria-label="$t('Clear')"
                  v-uni-aria-describedby="row.item.id.toString()"
                  :disabled="disableClear(row.item)"
                  @click="onClear(row)"
                  variant="link"
                  class="settings-listing-button"
                  >
                  <i class="fa-lg fas fa-trash-alt settings-listing-button"></i>
                </b-button>
              </span>
              <span v-else class="invisible">
                <b-button
                  variant="link"
                  class="settings-listing-button"
                  v-uni-aria-describedby="row.item.id.toString()">
                  <i class="fas fa-trash-alt settings-listing-button"></i>
                </b-button>
              </span>
            </template>
          </template>
        </template>
        <template v-slot:bottom-row><div class="bottom-padding"></div></template>
        <template v-slot:emptyfiltered>
          <div class="h-100 w-100 text-center">
            No Data Available
          </div>
        </template>
      </b-table>
      <div class="text-right p-2">
        <b-button
          v-for="(btn,index) in bottomButtons"
          :ref="formatGroupName(btn.group)"
          :key="`btn-${index}`"
          class="ml-2"
          v-bind="btn.ui.props"
          @click="handler(btn)"
          :disabled="false"
          >
          <b-spinner small ref="b-spinner" :hidden="true"></b-spinner>
          <span v-html="btn.icon"></span>
          <i v-if="btn.ui.props.icon" :class="btn.ui.props.icon"></i>
          {{btn.name}}
        </b-button>
      </div>
    </div>
  </div>
</template>

<script>
import { BasicSearch } from "SharedComponents";
import isPMQL from "../../../modules/isPMQL";
import PmqlInput from "../../../components/shared/PmqlInput";
import SettingBoolean from './SettingBoolean';
import SettingCheckboxes from './SettingCheckboxes';
import SettingChoice from './SettingChoice';
import SettingSelect from './SettingSelect';
import SettingFile from './SettingFile';
import SettingObject from './SettingObject';
import SettingScreen from './SettingScreen';
import SettingText from './SettingText';
import SettingTextArea from './SettingTextArea';
import SettingsImport from './SettingsImport';
import SettingsExport from './SettingsExport';
import SettingsRange from './SettingsRange';
import SettingDriverAuthorization from './SettingDriverAuthorization';
import { createUniqIdsMixin } from "vue-uniq-ids";
const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: {
    BasicSearch,
    PmqlInput,
    SettingBoolean,
    SettingChoice,
    SettingCheckboxes,
    SettingDriverAuthorization,
    SettingFile,
    SettingObject,
    SettingScreen,
    SettingText,
    SettingTextArea,
    SettingsImport,
    SettingsExport,
    SettingsRange,
    SettingSelect,
  },
  mixins:[uniqIdsMixin],
  props: ['group'],
  data() {
    return {
      savingSetting: null,
      tableKey: 0,
      bottomButtons: [],
      topButtons: [],
      currentPage: 1,
      fields: [],
      filter: '',
      from: 0,
      orderBy: 'name',
      orderDesc: false,
      orderByPrevious: 'name',
      orderDescPrevious: false,
      perPage: 25,
      pmql: '',
      searchQuery: '',
      settings: [],
      to: 0,
      totalRows: 0,
      url: '/settings',
      shouldDisplayNoDataMessage: false,
      noDataMessageConfig: null,
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
      tdClass: "align-middle td-name settings-listing-td1",
    });

    this.fields.push({
      key: "config",
      label: "Configuration",
      sortable: false,
      tdClass: "align-middle td-config settings-listing-td2",
    });

    this.fields.push({
      key: "actions",
      label: "",
      sortable: false,
      tdClass: "align-middle settings-listing-td3",
    });

    ProcessMaker.EventBus.$on('setting-added-from-modal', () => {
      this.shouldDisplayNoDataMessage = false;
      this.$nextTick(() => {
        this.$emit('refresh-all');
      });
    });
  },
  methods: {
    loadButtons() {
      ProcessMaker.apiClient.get(`/settings/group/${this.group}/buttons`)
        .then((response) => {
          if (!response.data) {
            return;
          }
          this.filterTopButtons(response.data);
          this.filterBottomButtons(response.data);
        });
    },
    apiGet() {
      return ProcessMaker.apiClient.get(this.pageUrl(this.currentPage));
    },
    apiPut(setting) {
      return ProcessMaker.apiClient.put(this.settingUrl(setting.id), setting, { timeout: 0 });
    },
    isSwitch(setting) {
      return setting.format === "boolean";
    },
    component(setting) {
      switch (setting.format) {
        case 'text':
        case 'boolean':
        case 'checkboxes':
        case 'choice':
        case 'select':
          return `setting-${setting.format}`;
        case 'object':
          if (setting.ui && setting.ui.format && setting.ui.format == 'map') {
            return `setting-object`;
          }
          if (setting.ui && setting.ui.format && setting.ui.format == 'screen') {
            return `setting-screen`;
          }
        case 'component':
          return window['__setting_component_' + setting.ui.component];
        case 'file':
          return 'setting-file';
        case 'range':
          return 'settings-range';
        case 'driver-auth':
          return 'setting-driver-authorization';
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
        const { noDataSettings, otherSettings } = this.separateSettings(this.settings);

        this.shouldDisplayNoDataMessage = this.shouldDisplayNoData(noDataSettings, this.settings);
        this.noDataMessageConfig = noDataSettings[0];

        if (!this.shouldDisplayNoDataMessage) {
          this.settings = otherSettings; // Use the other settings
        }

        this.totalRows = response.data.meta.total;
        this.from = response.data.meta.from;
        this.to = response.data.meta.to;

        this.loadButtons();

        if (this.orderBy !== this.orderByPrevious || this.orderDesc !== this.orderDescPrevious) {
          callback([]);
        }
        this.orderByPrevious = this.orderBy;
        this.orderDescPrevious = this.orderDesc;
        this.$nextTick(() => callback(this.settings));
      });
    },
    separateSettings(settings) {
      const noDataSettings = [];
      const otherSettings = [];

      settings.forEach(setting => {
        if (setting.format === 'no-data') {
          noDataSettings.push(setting);
        } else {
          otherSettings.push(setting);
        }
      });

      return { noDataSettings, otherSettings };
    },
    shouldDisplayNoData(noDataSettings, allSettings) {
      // All settings are 'no-data' settings
      if (noDataSettings.length === allSettings.length) {
        // and noDataSettings has a value
        return noDataSettings.length > 0;
      }

      // No 'no-data' settings found, display all configured settings
      return false;
    },
    getTooltip(row) {
      if (row.item.readonly) {
        return this.$t('Read Only');
      } else {
        return this.$t('Edit');
      }
    },
    onChange(setting) {
      this.$nextTick(() => {
        this.savingSetting = setting;
        this.apiPut(setting).then(response => {
          if (response.status == 204) {
            ProcessMaker.alert(this.$t("The setting was updated."), "success");
            if (_.get(setting, 'ui.refreshOnSave')) {
              this.$emit('refresh-all');
            } else {
              this.refresh();
            }
          }
        }).finally(() => {
          this.savingSetting = null;
        });
      });
    },
    onCopy(row) {
      let value = '';
      if (typeof row.item.config == 'string') {
        value = row.item.config;
      } else {
        value = JSON.stringify(row.item.config);
      }

      navigator.clipboard.writeText(value).then(() => {
        ProcessMaker.alert(this.$t("The setting was copied to your clipboard."), "success");
      }, () => {
        ProcessMaker.alert(this.$t("The setting was not copied to your clipboard."), "danger");
      });
    },
    onClear(row) {
      if (['array', 'checkboxes'].includes(row.item.format)) {
        row.item.config = [];
      }
      else {
        row.item.config = null;
      }
      this.onChange(row.item);
    },
    onDelete(row) {
      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        this.$t("Are you sure you want to delete the setting") +
          " " + '<strong>' +
          row.item.name + '</strong>' +
          this.$t("?"),
        "",
        () => {
          this.handleDeleteSetting(row.index, row.item.id);
        }
      );
    },
    handleDeleteSetting(index, id) {
      if (index !== -1) {
        this.settings.splice(index, 1);
        ProcessMaker.apiClient.delete(`${this.url}/${id}`).then(response => {
          if (response.status == 204) {
            ProcessMaker.alert(this.$t("The setting was deleted."), "success");
            this.refresh();
          }
        });
      }
    },
    onEdit(row) {
      this.$refs[`settingComponent_${row.index}`].onEdit(row);
    },
    onNLQConversion(pmql) {
      this.searchQuery = pmql;
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
    settingUrl(id = null) {
      return `${this.url}/${id}`;
    },
    /**
     * Javascript handler for configuration button
     *
     *  props: Properties of the button
     *  handler: JavaScript global function
     *
     * Example of Settings properties:
     *   name=Test button
     *   format=button
     *   hidden=true
     *   ui={"props":{"variant":"primary"},"handler":"mailTest"}
     *
     */
    handler(btn) {
      if (btn.ui && btn.ui.handler && window[btn.ui.handler]) {
        window[btn.ui.handler](this);
        if (btn.ui.handler === "addMailServer") {
          this.$parent.$refs["menu-collapse"].firstTime = false;
          this.$parent.$refs["menu-collapse"].getMenuGrups();
        }
      }
    },
    refresh() {
      this.$refs.table.refresh();
      this.$emit('refresh');
    },
    formatGroupName(name) {
      return name.toLowerCase().replaceAll(" ", "-");
    },
    filterTopButtons(buttons) {
      if (!this.settings) {
        return;
      }

      const sortedButtons = this.sortButtons(buttons);
      const groupData = this.getGroupData(this.settings);

      this.topButtons = sortedButtons.filter(btn => {
        if (this.group === 'Email Default Settings' || this.group.includes('Email Server')) {
          return this.filterEmailServerButtons(this.group, groupData, btn);
        } else if (this.group === 'Actions By Email') {
          return this.filterAbeButtons(groupData, btn);
        }

        if (btn.ui.props.hasOwnProperty('position')) {
          return btn.ui.props.position === 'top';
        } else {
          return btn;
        }
      });
    },
    filterBottomButtons(buttons) {
      const sortedButtons = this.sortButtons(buttons);
      this.bottomButtons = sortedButtons.filter(btn => {
        return btn.ui.props.position === 'bottom';
      })
    },
    getGroupData(settings) {
      return settings.filter(setting => {
        return setting.group === this.group;
      });
    },
    filterEmailServerButtons(groupName, groupData, btn) {
      const authMethod = groupData.find(data => data.key.includes("EMAIL_CONNECTOR_MAIL_AUTH_METHOD"));
      const selectedAuthMethod = authMethod ? authMethod.ui.options[authMethod.config] : null;
      const showAuthAccBtn = selectedAuthMethod && selectedAuthMethod !== 'standard' ? true : false;

      if (groupName.includes('Email Server') && !showAuthAccBtn)  {
        // Returns all 'top' position buttons except the '+ Mail Server' and 'Authorize Account' button for email server tabs
        return btn.ui.props.position === 'top' && !btn.key.includes('EMAIL_CONNECTOR_ADD_MAIL_SERVER_') && !btn.key.includes('EMAIL_CONNECTOR_AUTHORIZE_ACCOUNT');
      }
      if (showAuthAccBtn) {
        // Returns all 'top' position buttons except the '+ Mail Server' button for email server tabs
        return btn.ui.props.position === 'top' && !btn.key.includes('EMAIL_CONNECTOR_ADD_MAIL_SERVER_');
      }
      // Returns all 'top' position buttons except the 'Authorize Account' button for email default settings tab
      return btn.ui.props.position === 'top' && !btn.key.includes('EMAIL_CONNECTOR_AUTHORIZE_ACCOUNT');
    },
    filterAbeButtons(groupData, btn) {
      const authMethod = groupData.find(data => data.key.includes("abe_imap_auth_method"));
      const selectedAuthMethod = authMethod ? authMethod.ui.options[authMethod.config] : null;
      const showAuthAccBtn = selectedAuthMethod && selectedAuthMethod !== 'standard' ? true : false;

      if (showAuthAccBtn) {
        // Returns all 'top' position buttons
        return btn.ui.props.position === 'top';
      }
      // Returns all 'top' position buttons except the 'Authorize Account' button
      return btn.ui.props.position === 'top' && !btn.key.includes('abe_authorize_account');
    },
    sortButtons(buttons) {
      return buttons.sort((a,b) => (a.ui.props.order > b.ui.props.order) ? 1 : -1);
    },
    disableClear(item) {
      return item.readonly || item.format === 'choice' ? true : false;
    },
    disabledCopySetting(row) {
      return row.item.ui?.copySettingEnabled === false;
    },
    disabledDeleteSetting(row) {
      return row.item.ui?.deleteSettingEnabled === false;
    },
    enableDeleteSetting(row) {
      return row.item.ui?.deleteSettingEnabled || false;
    }
  }
};
</script>

<style>
.settings-listing-td1 {
  width: 50%;
}
.settings-listing-td2 {
  width: 40%;
}
.settings-listing-td3 {
  width: 10%;
  min-width: 100px;
}
</style>
<style lang="scss" scoped>
@import '../../../../sass/colors';

.preview-renderer {
  .form-group {
    margin: 0 !important;
    padding: 0 !important;
  }
}
.capitalize {
  text-transform: capitalize;
}
.nowrap {
  white-space: nowrap;
}
.settings-listing-button {
  padding: 0px;
}
</style>
