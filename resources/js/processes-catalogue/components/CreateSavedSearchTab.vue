<template>
  <b-form class="pm-create-tab-form">
    <b-form-group :label="$t('Tab Name')">
      <b-form-input v-model="tabName"
                    :placeholder="$t('Tab Name')"
                    :size="'sm'"
                    :autocomplete="'off'"
                    :state="stateTabName">
      </b-form-input>
      <b-form-invalid-feedback :state="stateTabName">
        {{ stateMessageTabName }}
      </b-form-invalid-feedback>
    </b-form-group>
    <b-form-group :label="$t('Select a Saved Search')"
                  v-if="!hideSelectSavedSearch">
      <PMDropdownSuggest v-model="idSavedSearch"
                         :options="options"
                         @onInput="onInput"
                         @onSelectedOption="onSelectedOption"
                         :state="stateIdSavedSearch"
                         :placeholder="$t('Type here to search')">
      </PMDropdownSuggest>
      <b-form-invalid-feedback :state="stateIdSavedSearch">
        {{ $t('Please select a Saved Search') }}
      </b-form-invalid-feedback>
    </b-form-group>
    <b-form-checkbox v-if="showOptionSeeTabOnMobile"
                     v-model="seeTabOnMobile"
                     :value="true"
                     :unchecked-value="false">
      {{$t('See this tab on mobile devices')}}
    </b-form-checkbox>
    <b-form-group class="text-right"
                  v-show="!hideFormsButton">
      <b-button variant="secondary"
                size="sm"
                @click="onCancel">
        {{$t('Cancel')}}
      </b-button>
      <b-button variant="primary"
                size="sm"
                @click="onOk">
        {{$t('Ok')}}
      </b-button>  
    </b-form-group>
  </b-form>
</template>

<script>
  import PMDropdownSuggest from "../../components/PMDropdownSuggest.vue";
  export default {
    components: {
      PMDropdownSuggest
    },
    props: {
      hideFormsButton: {
        type: Boolean,
        default: false
      },
      showOptionSeeTabOnMobile: {
        type: Boolean,
        default: false
      },
      tabsList: {
        type: Array,
        defalut: []
      }
    },
    data() {
      return {
        activeTab: null,
        type: "default",
        tabName: "",
        filter: "",
        pmql: "",
        advanced_filter: null,
        columns: [],
        idSavedSearch: null,
        seeTabOnMobile: false,
        options: [],
        stateTabName: null,
        stateMessageTabName: "",
        stateIdSavedSearch: null,
        hideSelectSavedSearch: false
      };
    },
    mounted() {
      this.requestSavedSearch("");
    },
    methods: {
      onCancel() {
        this.$emit("onCancel");
      },
      onOk() {
        this.stateTabName = null;
        this.stateIdSavedSearch = null;
        if (!this.tabName) {
          this.stateMessageTabName = this.$t('Type a valid name');
          this.stateTabName = false;
          return;
        }
        //We remove the current element if it's being edited to allow saving the current tab name
        let clonedTabsList = JSON.parse(JSON.stringify(this.tabsList));
        if (Number.isInteger(this.activeTab)) {
          clonedTabsList.splice(this.activeTab, 1);
        }
        if (clonedTabsList.some(item => item.name === this.tabName)) {
          this.stateMessageTabName = this.$t("Duplicate");
          this.stateTabName = false;
          return;
        }
        if (!this.idSavedSearch && !this.hideSelectSavedSearch) {
          this.stateIdSavedSearch = false;
          return;
        }
        let tab = {
          type: this.type,
          name: this.tabName,
          filter: this.filter,
          pmql: this.pmql,
          advanced_filter: this.advanced_filter,
          columns: this.columns,
          idSavedSearch: this.idSavedSearch,
          seeTabOnMobile: this.seeTabOnMobile
        };
        this.$emit("onOk", tab);
      },
      onInput(value) {
        this.requestSavedSearch(value);
      },
      async requestSavedSearch(filter) {
        let url = "saved-searches" +
                "?include=reports,user_options" +
                "&page=1" +
                "&per_page=30" +
                "&filter=" + filter +
                "&type=task" +
                "&key=" +
                "&is_system=false" +
                "&subset=mine" +
                "&order_by=title" +
                "&order_direction=asc";
        await ProcessMaker.apiClient.get(url)
                .then(response => {
                  this.options = [];
                  response?.data?.data?.forEach(item => {
                    this.options.push({
                      text: item.title,
                      value: item.id,
                      advanced_filter: item.advanced_filter
                    });
                  });
                });
      },
      async set(tab, activeTab) {
        this.activeTab = activeTab;
        this.type = tab.type;
        this.tabName = tab.name;
        this.filter = tab.filter;
        this.pmql = tab.pmql;
        this.advanced_filter = tab.advanced_filter;
        this.columns = tab.columns;
        this.hideSelectSavedSearch = tab.type === "myCases" || tab.type === "myTasks";
        if (!this.hideSelectSavedSearch) {
          await this.requestSavedSearch("");
          this.idSavedSearch = tab.idSavedSearch;
        }
        this.seeTabOnMobile = tab.seeTabOnMobile === true;
      },
      onSelectedOption(option) {
        this.advanced_filter = option.advanced_filter;
        this.$emit('onSelectedOption', option);
      }
    }
  }
</script>

<style scoped>
  .pm-create-tab-form{
    width: 285px;
  }
</style>