<template>
  <div class="pm-create-tab-form">
    <b-form>
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
      <b-form-group :label="$t('Select a Saved Search')">
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
      <b-form-group class="text-right">
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
  </div>
</template>

<script>
  import PMDropdownSuggest from "../../components/PMDropdownSuggest.vue";
  export default {
    components: {
      PMDropdownSuggest
    },
    data() {
      return {
        tabName: "",
        idSavedSearch: null,
        options: [],
        stateTabName: null,
        stateIdSavedSearch: null
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
        if (!this.idSavedSearch) {
          this.stateIdSavedSearch = false;
          return;
        }
        let tab = {
          name: this.tabName,
          idSavedSearch: this.idSavedSearch
        };
        this.$emit("onOk", tab);
      },
      onInput(value) {
        this.requestSavedSearch(value);
      },
      requestSavedSearch(filter) {
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
        ProcessMaker.apiClient.get(url)
                .then(response => {
                  this.options = [];
                  response?.data?.data?.forEach(item => {
                    this.options.push({
                      text: item.title,
                      value: item.id,
                      meta: item
                    });
                  });
                });
      }
    }
  }
</script>

<style scoped>
  .pm-create-tab-form{
    width: 285px;
  }
</style>