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
        {{ $t('Please enter Tab Name') }}
      </b-form-invalid-feedback>
    </b-form-group>
    <b-form-group :label="$t('Select a Saved Search')"
                  v-if="!hideSelectSavedSearch">
      <PMDropdownSuggest v-model="idSavedSearch"
                         :options="options"
                         @onInput="onInput"
                         @onSelectedOption="$emit('onSelectedOption',$event)"
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
      }
    },
    data() {
      return {
        type: "default",
        tabName: "",
        filter: "",
        pmql: "",
        columns: [],
        idSavedSearch: null,
        seeTabOnMobile: false,
        options: [],
        stateTabName: null,
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
                      value: item.id
                    });
                  });
                });
      },
      async set(tab) {
        this.type = tab.type;
        this.tabName = tab.name;
        this.filter = tab.filter;
        this.pmql = tab.pmql;
        this.columns = tab.columns;
        this.hideSelectSavedSearch = tab.type === "myCases" || tab.type === "myTasks";
        if (!this.hideSelectSavedSearch) {
          await this.requestSavedSearch("");
          this.idSavedSearch = tab.idSavedSearch;
        }
        this.seeTabOnMobile = tab.seeTabOnMobile === true;
      }
    }
  }
</script>

<style scoped>
  .pm-create-tab-form{
    width: 285px;
  }
</style>