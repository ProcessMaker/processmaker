<template>
  <div class="pm-inbox-rule-edit">
    <template v-if="!showFillConfig">
      <b-form-group>
        <template v-slot:label>
          <b>{{ $t('What do we do with tasks that fit this filter?') }}</b>
        </template>
        <b-form-radio-group v-model="actionsTask"
                            class="pm-inbox-rule-edit-radio">
          <b-form-radio value="priority">
            <img src="/img/flag-fill-red.svg" :alt="$t('Mark as Priority')">
              {{ $t('Mark as Priority') }}
          </b-form-radio>
          <b-form-radio value="reassign">
            <img src="/img/people-fill.svg" :alt="$t('Reassign')">
              {{ $t('Reassign') }}
          </b-form-radio>
        </b-form-radio-group>
        <b-form-group :label="$t('Select a Person*')"
                      v-if="actionsTask==='reassign'">
          <b-form-select v-model="selectedPerson" 
                          :options="persons">
          </b-form-select>
        </b-form-group>
      </b-form-group>

      <b-form-group>
        <b-form-checkbox
          v-model="fillDataChecked"
        >
          {{ $t('Save and reuse filled data') }}
        </b-form-checkbox>
      </b-form-group>

      <b-form-group>
        <template v-slot:label>
          <b>{{ $t('Rule Behavior') }}</b>
        </template>

        <b-form-checkbox v-model="applyToCurrentInboxMatchingTasks"
                          value="true"
                          unchecked-value="false">
          {{ $t('Apply to current inbox matching tasks') }} ({{ count }})
        </b-form-checkbox>
        <b-form-checkbox v-model="applyToFutureTasks"
                          value="true"
                          unchecked-value="false">
          {{ $t('Apply to Future tasks') }}
        </b-form-checkbox>

      </b-form-group>

      <b-form-group>
        <template v-slot:label>
          <b>{{ $t('Deactivation date') }}</b>
        </template>
        <PMDatetimePicker v-model="deactivationDate"
                          :format="'YYYY-MM-DD'"
                          :withTime="false">
          <template v-slot:button-content-datepicker>
            <img src="/img/calendar2-fill.svg" :alt="$t('Deactivation date')">
          </template>
        </PMDatetimePicker>
        <div class="pm-inbox-rule-edit-custom-placeholder">
          {{ $t('For a rule with no end date, leave the field empty') }}
        </div>
      </b-form-group>

      <b-form-group>
        <div class="pm-inbox-rule-edit-custom-separator"></div>
      </b-form-group>

      <b-form-group v-if="!fillDataChecked">
        <template v-slot:label>
          <b>{{ $t('Give this rule a name *') }}</b>
        </template>
        <b-form-input v-model="ruleName" 
                      placeholder="Enter your name"
                      :state="ruleNameState"
                      @input="onChangeRuleName">
        </b-form-input>
        <b-form-invalid-feedback :state="ruleNameState">
          {{ $t('This field is required!') }}
        </b-form-invalid-feedback>
      </b-form-group>

      <b-form-group>
        <div class="d-flex flex-nowrap">
          <div  class="flex-grow-1 d-flex align-items-center">
            <span class="">{{ $t('*=Required') }}</span>
          </div>
          <div class="flex-grow-0">
            <b-button variant="secondary"
                      @click="onCancel">
              {{ $t('Cancel') }}
            </b-button>
            <b-button v-if="!fillDataChecked"
                      variant="primary"
                      @click="onSave">
              {{ $t('Save') }}
            </b-button>
            <b-button v-if="fillDataChecked"
                      variant="primary"
                      @click="showFillConfig = true">
              {{ $t('Next') }}
            </b-button>
          </div>
        </div>
      </b-form-group>
    </template>
    <template v-if="showFillConfig">
      <b-form-group>
        Submit config here
      </b-form-group>
      <b-button variant="primary"
                @click="showFillConfig = false">
        {{ $t('Back') }}
      </b-button>
    </template>
  </div>
</template>

<script>
  import PMDatetimePicker from "../../components/PMDatetimePicker.vue";
  export default {
    components: {
      PMDatetimePicker
    },
    props: {
      inboxRule: {
        type: Object,
        default: null
      },
      count: {
        type: Number,
        default: 0
      },
      savedSearchData: {
        type: Object,
        default: {},
      },
      taskId: {
        type: Number,
        default: null
      }
    },
    data() {
      return {
        actionsTask: "priority",
        selectedPerson: "",
        persons: [],
        applyToCurrentInboxMatchingTasks: false,
        applyToFutureTasks: false,
        deactivationDate: "",
        ruleName: "",
        ruleNameState: null,
        fillDataChecked: false,
        showFillConfig: false
      };
    },
    watch: {
      savedSearchData: {
        handler() {
          console.log('savedSearchData', _.cloneDeep(this.savedSearchData));
        },
        deep: true,
        immediate: true
      },
      inboxRule: {
        handler() {
          this.setInboxRuleData();
        },
        deep: true
      },
      showFillConfig: {
        handler() {
          this.$emit("show-fill-config", this.showFillConfig);
        }
      }
    },
    mounted() {
      this.requestUser("");
      this.setInboxRuleData();
    },
    methods: {
      onCancel() {
        this.$router.push({name: 'index'});
      },
      onSave() {
        if (this.ruleName.trim() === "") {
          this.ruleNameState = false;
          return;
        }
        let params = {
          actionsTask: this.actionsTask,
          selectedPerson: this.selectedPerson,
          applyToCurrentInboxMatchingTasks: this.applyToCurrentInboxMatchingTasks,
          applyToFutureTasks: this.applyToFutureTasks,
          deactivationDate: this.deactivationDate,
          ruleName: this.ruleName,
          taskId: this.taskId,
          ...this.savedSearchData,
        };
        if (this.inboxRule) {
          window.ProcessMaker.apiClient
                  .put('/tasks/rules/' + this.inboxRule.id, params)
                  .then(response => {
                    this.$router.push({name: 'index'});

                    let message = "The inbox rule '{{name}}' was updated.";
                    message = this.$t(message, {name: this.ruleName});
                    ProcessMaker.alert(message, "success");
                  });
        } else {
          window.ProcessMaker.apiClient
                  .post('/tasks/rules', params)
                  .then(response => {
                    this.$router.push({name: 'index'});

                    let message = "The inbox rule {{name}} was created.";
                    message = this.$t(message, {name: this.ruleName});
                    ProcessMaker.alert(message, "success");
                  });
        }
      },
      onChangeRuleName() {
        this.ruleNameState = this.ruleName.trim() !== "";
      },
      setInboxRuleData() {
        if (this.inboxRule) {
          if (this.inboxRule.mark_as_priority) {
            this.actionsTask = "priority";
          }
          if (this.inboxRule.reassign_to_user_id > 0) {
            this.actionsTask = "reassign";
            this.selectedPerson = this.inboxRule.reassign_to_user_id;
          }
          this.deactivationDate = this.inboxRule.end_date;
          this.ruleName = this.inboxRule.name;
        }
      },
      requestUser(filter, callback) {
        ProcessMaker.apiClient.get(this.getUrlUser(filter))
                .then(response => {
                  for (let i in response.data.data) {
                    this.persons.push({
                      text: response.data.data[i].fullname,
                      value: response.data.data[i].id
                    });
                  }
                });
      },
      getUrlUser(filter) {
        let page = 1;
        let perPage = 100;
        let orderBy = "username";
        let orderDirection = "asc";
        let url = "users" +
                "?page=" + page +
                "&per_page=" + perPage +
                "&filter=" + filter +
                "&order_by=" + orderBy +
                "&order_direction=" + orderDirection;
        return url;
      }
    }
  };
</script>

<style>
  .pm-inbox-rule-edit-radio>.custom-control-inline{
    display: flex;
  }
</style>
<style scoped>
  .pm-inbox-rule-edit>.form-group{
    padding-top: 8px;
    padding-bottom: 8px;
  }
  .pm-inbox-rule-edit-custom-placeholder{
    color: #556271;
    font-size: 14px;
  }
  .pm-inbox-rule-edit-custom-separator{
    border-top: 1px solid #CDDDEE;
  }
</style>