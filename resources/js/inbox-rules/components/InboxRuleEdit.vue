<template>
  <div class="pm-inbox-rule-edit">
    <template v-if="viewIs('main')">
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
        <b-form-checkbox v-model="makeDraft" v-if="taskId">
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
        
        <b-input-group class="pm-datetime-picker">
          <b-form-input v-model="visibleFormattedDeactivationDate"
            type="text"
            autocomplete="off"
            readonly
            @click="showCalendar"
            class="pm-datetime-picker-input">
          </b-form-input>
          <b-input-group-append>
            <b-form-datepicker
              v-model="formattedDeactivationDate"
              ref="datepicker"
              right
              button-only
              button-variant="light"
              class="pm-datetime-picker-button-datetime"
              >
              <template v-slot:button-content>
                <img src="/img/calendar2-fill.svg" :alt="$t('Deactivation date')"/>
              </template>
            </b-form-datepicker>
          </b-input-group-append>
        </b-input-group>

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
                      autocomplete="off"
                      @input="onChangeRuleName">
        </b-form-input>
        <b-form-invalid-feedback :state="ruleNameState">
          {{ ruleNameMessageError }}
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
                      @click="viewsTo('nextConfiguration')">
              {{ $t('Next') }}
            </b-button>
          </div>
        </div>
      </b-form-group>
    </template>
    <template v-if="viewIs('nextConfiguration')">
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
        <b-form-input :placeholder="$t('Waiting for selection')"
                      v-model="submitButtonLabel"
                      autocomplete="off"
                      :state="submitButtonState"
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
                      autocomplete="off"
                      @input="onChangeRuleName">
        </b-form-input>
        <b-form-invalid-feedback :state="ruleNameState">
          {{ ruleNameMessageError }}
        </b-form-invalid-feedback>
      </b-form-group>

      <b-form-group>
        <div class="d-flex flex-nowrap">
          <div  class="flex-grow-1 d-flex align-items-center">
            <span class="">{{ $t('*=Required') }}</span>
          </div>
          <div class="flex-grow-0">
            <b-button variant="secondary"
                      @click="viewsTo('main')">
              {{ $t('Back') }}
            </b-button>
            <b-button variant="primary"
                      @click="onSave">
              {{ inboxRule && inboxRule.id ? $t('Update Rule') : $t('Create Rule') }}
            </b-button>
          </div>
        </div>
      </b-form-group>

    </template>

    <b-modal ref="openModal"
             @hidden="onModalHidden"
             centered
             hide-footer
             modal-class="pm-inbox-rule-edit-modal"
             header-class="pm-inbox-rule-edit-modal-header"
             footer-class="pm-inbox-rule-edit-modal-footer">
      <div class="pm-inbox-rule-edit-modal-content mb-4 mr-4 ml-4">
        <img src="/img/check-circle-lg.svg" :alt="$t('Rule successfully created')"/>
        <b>{{ inboxRule ? $t('Rule successfully updated'): $t('Rule successfully created') }}</b>
        <span>{{ $t("Please take a look at it in the 'Rules' section located within your Inbox.") }}</span>
      </div>
    </b-modal>
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
      selectSubmitButton: {
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
        deactivationDate: null,
        ruleName: "",
        ruleNameState: null,
        ruleNameMessageError: "",
        makeDraft: false,
        submitAfterFilling: false,
        usersTimeZone: window.ProcessMaker.user.timezone,
      };
    },
    computed: {
      formattedDeactivationDate: {
        get() {
          if (!moment(this.deactivationDate).isValid()) {
            return null;
          }
          // Convert the UTC date to users time zone
          // Do not include time
          return moment.utc(this.deactivationDate)
            .tz(this.usersTimeZone)
            .format("YYYY-MM-DD");
          },
        set(value) {
          // Convert to UTC from the users time zone
          // Assume local time is midnight and then convert to UTC
          this.deactivationDate = moment.tz(value + " 00:00:00", this.usersTimeZone)
            .utc()
            .format("YYYY-MM-DD HH:mm:ss")
          }
      },
      visibleFormattedDeactivationDate() {
        if (!this.formattedDeactivationDate) {
          return '';
        }
        const format = ProcessMaker.user.datetime_format.split(" ")[0];
        return moment(this.formattedDeactivationDate).format(format);
      },
      submitButton() {
        if (!this.selectSubmitButton || !this.submitAfterFilling) {
          return null;
        }
        return {
          label: this.selectSubmitButton.label || null,
          value: this.selectSubmitButton.value || null,
          name: this.selectSubmitButton.name || null,
          loopContext: this.selectSubmitButton.loopContext || null
        };
      },
      submitButtonState() {
        if (!this.submitAfterFilling) {
          return null;
        }

        return !!this.submitButton;
      },
      submitButtonLabel() {
        if (!this.submitAfterFilling || !this.selectSubmitButton?.label) {
          return "";
        }
        return this.selectSubmitButton.label;
      }
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
      inboxRule: {
        handler() {
          this.setInboxRuleData();
        },
        deep: true
      }
    },
    mounted() {
      this.requestUser("");
      this.setInboxRuleData();
    },
    methods: {
      showCalendar() {
        this.$refs.datepicker.$refs.control.show();
      },
      onCancel() {
        window.history.back();
      },
      onSave() {
        if (!this.savedSearchData.columns) {
          this.$emit("onSavedSearchNotSelected", true);
          return;
        }
        if (this.ruleName.trim() === "") {
          this.ruleNameMessageError = this.$t("This field is required!");
          this.ruleNameState = false;
          return;
        }
        if (this.submitButtonState === false) {
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
          ...this.savedSearchData
        };
        if (this.inboxRule) {
          ProcessMaker.apiClient.put('/tasks/rules/' + this.inboxRule.id, params)
                  .then(response => {
                    this.$refs.openModal.show();
                  })
                  .catch((error) => {
                    let message = "The operation cannot be performed. Please try again later.";
                    if (error.response.status && error.response.status === 422) {
                      message = error.response.data.message;
                      this.ruleNameMessageError = this.$t(message);
                      this.ruleNameState = false;
                    }
                    ProcessMaker.alert(this.$t(message), "danger");
                  });
        } else {
          ProcessMaker.apiClient.post('/tasks/rules', params)
                  .then(response => {
                    this.$refs.openModal.show();
                  })
                  .catch((error) => {
                    let message = "The operation cannot be performed. Please try again later.";
                    if (error.response.status && error.response.status === 422) {
                      message = error.response.data.message;
                      this.ruleNameMessageError = this.$t(message);
                      this.ruleNameState = false;
                    }
                    ProcessMaker.alert(this.$t(message), "danger");
                  });
        }
      },
      onModalHidden() {
        this.$router.push({name: 'index'});
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
  .pm-inbox-rule-edit-modal .modal-content{
    border-radius: 12px;
  }
  .pm-inbox-rule-edit-modal-content>:not(img):not(svg){
    display: flex;
    flex-direction: column;
  }
  .pm-inbox-rule-edit-modal-content>b{
    font-size: 24px;
    color: #4EA075;
    margin-top: 10px;
    margin-bottom: 10px;
  }
  .pm-inbox-rule-edit-modal-header{
    border-color: white;
  }
  .pm-inbox-rule-edit-modal-footer{
    border-color: white;
  }
  
  .pm-datetime-picker-button-datetime > button {
    display: flex;
    justify-content: center;
    align-items: center;
    border-top: 1px solid #b6bfc6;
    border-bottom: 1px solid #b6bfc6;
    border-left: 0px;
    border-right: 1px solid #b6bfc6;
    border-top-right-radius: 2px !important;
    border-bottom-right-radius: 2px !important;
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

  .pm-datetime-picker-input {
    background-color: white;
  }
</style>