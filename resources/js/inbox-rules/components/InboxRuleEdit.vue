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
            <img src="/img/flag-fill-red.svg" :alt="$t('Mark as Priority')"/>
            {{ $t('Mark as Priority') }}
          </b-form-radio>
          <b-form-radio value="reassign">
            <img src="/img/people-fill.svg" :alt="$t('Reassign')"/>
            {{ $t('Reassign') }}
          </b-form-radio>
        </b-form-radio-group>
        <b-form-group :label="$t('Select a Person*')"
                      v-if="actionsTask==='reassign'">
          <PMFormSelectSuggest v-model="selectedPerson" 
                               :options="persons"
                               @onSearchChange="onSearchChange"
                               :placeholder="$t('Type here to search')"
                               :selectLabel="$t('Press enter to select')"
                               :deselectLabel="$t('Press enter to remove')"
                               :selectedLabel="$t('Selected')">
          </PMFormSelectSuggest>
        </b-form-group>
      </b-form-group>

      <b-form-group>
        <b-form-checkbox
          v-model="makeDraft"
          >
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
                      @click="showFillConfig = true">
              {{ $t('Next') }}
            </b-button>
          </div>
        </div>
      </b-form-group>
    </template>
    <template v-if="showFillConfig">
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
        <b-form-input :placeholder="$t('Waiting selection')">
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
            <b-button variant="primary"
                      @click="showFillConfig = false">
              {{ $t('Back') }}
            </b-button>
            <b-button variant="primary"
                      @click="onSave">
              {{ $t('Create Rule') }}
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
  export default {
    components: {
      PMDatetimePicker,
      PMFormSelectSuggest
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
        actionsTask: "priority",
        selectedPerson: "",
        persons: [],
        applyToCurrentInboxMatchingTasks: false,
        applyToFutureTasks: false,
        deactivationDate: "",
        ruleName: "",
        ruleNameState: null,
        makeDraft: false,
        showFillConfig: false,
        submitAfterFilling: false
      };
    },
    watch: {
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
          data: this.data,
          submit_button: this.submitButton,
          make_draft: this.makeDraft,
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