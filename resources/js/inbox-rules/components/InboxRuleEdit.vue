<template>
  <div class="pm-inbox-rule-edit">
    <template v-if="isViewName(1)">
      <b-form-group>
        <template v-slot:label>
          <b>{{ $t('What do we do with tasks that fit this filter?') }}</b>
        </template>
        <b-form-checkbox v-model="reassign">
          <img src="/img/people-fill.svg" :alt="$t('Reassign')"/>
          {{ $t('Reassign') }}
        </b-form-checkbox>
        <b-form-group :label="$t('Select a Person*')"
                      v-if="reassign">
          <PMFormSelectSuggest v-model="reassignToUserId" 
                               :options="persons"
                               @onSearchChange="onSearchChange"
                               :placeholder="$t('Type here to search')"
                               :selectLabel="$t('Press enter to select')"
                               :deselectLabel="$t('Press enter to remove')"
                               :selectedLabel="$t('Selected')">
          </PMFormSelectSuggest>
        </b-form-group>
        <b-form-checkbox v-model="markAsPriority">
          <img src="/img/flag-fill-red.svg" :alt="$t('Mark as Priority')"/>
          {{ $t('Mark as Priority') }}
        </b-form-checkbox>
        <b-form-checkbox v-model="makeDraft">
          <img src="/img/pencil-fill-text.svg" :alt="$t('Save and reuse filled data')"/>
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
                         :value=true
                         :unchecked-value=false>
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
            <img src="/img/calendar2-fill.svg" :alt="$t('Deactivation date')"/>
          </template>
        </PMDatetimePicker>
        <div class="pm-inbox-rule-edit-custom-placeholder">
          {{ $t('For a rule with no end date, leave the field empty') }}
        </div>
      </b-form-group>

      <b-form-group>
        <div class="pm-inbox-rule-edit-custom-separator"></div>
      </b-form-group>

      <b-form-group v-if="!makeDraft">
        <template v-slot:label>
          <b>{{ $t('Give this rule a name *') }}</b>
        </template>
        <b-form-input v-model="ruleName" 
                      :placeholder="$t('Enter your name')"
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
            <b-button v-if="!makeDraft"
                      variant="primary"
                      @click="onSave">
              {{ $t('Save') }}
            </b-button>
            <b-button v-if="makeDraft"
                      variant="primary"
                      @click="viewName(2)">
              {{ $t('Next') }}
            </b-button>
          </div>
        </div>
      </b-form-group>
    </template>
    <template v-if="isViewName(2)">
      <b-form-group>
        <span>
          {{ $t('If you want to establish an automatic submit for this rule,') }}
          {{ $t('complete all the necessary fields and select you preferred submit action.') }}
        </span>
      </b-form-group>

      <b-form-group>
        <div class="d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
            <img src="/img/arrow-right.svg" :alt="$t('Submit after filling')" />
            {{ $t('Submit after filling') }}
          </div>
          <b-form-checkbox switch
                           v-model="submitAfterFilling">
          </b-form-checkbox>
        </div>
      </b-form-group>

      <b-form-group v-if="submitAfterFilling">
        <b>{{ $t('Choose the submit action you want to use by clicking on it in the form*') }}</b>
      </b-form-group>

      <b-form-group v-if="submitAfterFilling">
        <template v-slot:label>
          {{ $t('Submit action') }}
        </template>
        <b-form-input :placeholder="$t('Waiting selection')"
                      v-model="submitButtonLabel"
                      :readonly="true">
        </b-form-input>
      </b-form-group>

      <!--Important! It may be necessary to change the values of the directives: 
      v-model, :state, @input of this b-form-input.-->
      <b-form-group v-if="makeDraft">
        <template v-slot:label>
          <b>{{ $t('Give this rule a name *') }}</b>
        </template>
        <b-form-input v-model="ruleName" 
                      :placeholder="$t('Enter your name')"
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
                      @click="viewName(1)">
              {{ $t('Back') }}
            </b-button>
            <b-button variant="primary"
                      @click="onSave">
              {{ inboxRule.id ? $t('Update Rule') : $t('Create Rule') }}
            </b-button>
          </div>
        </div>
      </b-form-group>

    </template>
  </div>
</template>

<script>
  import PMDatetimePicker from "../../components/PMDatetimePicker.vue";
  import PMFormSelectSuggest from "../../components/PMFormSelectSuggest.vue";
  import IsViewMixin from "./IsViewMixin.js";
  export default {
    components: {
      PMDatetimePicker,
      PMFormSelectSuggest
    },
    mixins: [IsViewMixin],
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
        default: {}
      },
      taskId: {
        type: Number,
        default: null
      },
      data: {
        type: Object,
        default: null
      },
      submitButton: {
        type: Object,
        default: null
      }
    },
    data() {
      return {
        markAsPriority: false,
        reassign: false,
        reassignToUserId: null,
        persons: [],
        applyToCurrentInboxMatchingTasks: false,
        applyToFutureTasks: false,
        deactivationDate: "",
        ruleName: "",
        ruleNameState: null,
        makeDraft: false,
        submitAfterFilling: false,
        submitButtonLabel: ""
      };
    },
    watch: {
      makeDraft() {
        if (!this.makeDraft) {
          this.submitAfterFilling = false;
        }
      },
      reassign() {
        if (!this.reassign) {
          this.reassignToUserId = null;
        }
      },
      savedSearchData: {
        handler() {
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
      submitButton(value) {
        this.submitButtonLabel = this.submitAfterFilling ? value.label : "";
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
          mark_as_priority: this.markAsPriority,
          reassign_to_user_id: this.reassignToUserId,
          apply_to_current_tasks: this.applyToCurrentInboxMatchingTasks,
          active: this.applyToFutureTasks,
          end_date: this.deactivationDate,
          name: this.ruleName,
          process_request_token_id: this.taskId,
          data: this.data,
          submit_button: this.submitButton,
          make_draft: this.makeDraft,
          submit_data: this.submitAfterFilling,
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
                  })
                  .catch((err) => {
                    let message = "The operation cannot be performed. Please try again later.";
                    ProcessMaker.alert(this.$t(message), "danger");
                  });
        } else {
          window.ProcessMaker.apiClient
                  .post('/tasks/rules', params)
                  .then(response => {
                    this.$router.push({name: 'index'});

                    let message = "The inbox rule {{name}} was created.";
                    message = this.$t(message, {name: this.ruleName});
                    ProcessMaker.alert(message, "success");
                  })
                  .catch((err) => {
                    let message = "The operation cannot be performed. Please try again later.";
                    ProcessMaker.alert(this.$t(message), "danger");
                  });
        }
      },
      onChangeRuleName() {
        this.ruleNameState = this.ruleName.trim() !== "";
      },
      setInboxRuleData() {
        if (this.inboxRule) {
          if (this.inboxRule.reassign_to_user_id > 0) {
            this.reassign = true;
            this.reassignToUserId = this.inboxRule.reassign_to_user_id;
          }
          this.markAsPriority = this.inboxRule.mark_as_priority;
          this.deactivationDate = this.inboxRule.end_date;
          this.ruleName = this.inboxRule.name;
          this.makeDraft = this.inboxRule.make_draft;
          this.submitAfterFilling = this.inboxRule.submit_data;
          this.applyToFutureTasks = this.inboxRule.active;
        }
      },
      requestUser(filter) {
        let url = "users" +
                "?page=1" +
                "&per_page=100" +
                "&filter=" + filter +
                "&order_by=firstname" +
                "&order_direction=asc";
        ProcessMaker.apiClient.get(url)
                .then(response => {
                  this.persons = [];
                  for (let i in response.data.data) {
                    this.persons.push({
                      text: response.data.data[i].fullname,
                      value: response.data.data[i].id
                    });
                  }
                });
      },
      onSearchChange(searchTerm) {
        this.requestUser(searchTerm);
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