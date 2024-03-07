<template>
  <div> <!--Edit id {{ $route.params.id }}-->
    <b-form-group>
      <template v-slot:label>
        <b>{{ $t('What do we do with tasks that fit this filter?') }}</b>
      </template>
      <b-form-radio v-model="markAsPriority"
                    name="actionsTask"
                    value="false">
        <img src="/img/flag-fill-red.svg" :alt="$t('Mark as Priority')">
          {{ $t('Mark as Priority') }}
      </b-form-radio>
      <b-form-radio v-model="reassign"
                    name="actionsTask"
                    value="false">
        <img src="/img/people-fill.svg" :alt="$t('Reassign')">
          {{ $t('Reassign') }}
      </b-form-radio>
    </b-form-group>

    <b-form-group>
      <template v-slot:label>
        <b>{{ $t('Rule Behavior') }}</b>
      </template>

      <b-form-checkbox-group v-model="checkboxValues"
                             name="ruleBehavior"
                             stacked >
        <b-form-checkbox value="true">
          {{ $t('Apply to current inbox matching tasks') }} ({{ count }})
        </b-form-checkbox>
        <b-form-checkbox value="true">
          {{ $t('Apply to Future tasks') }}
        </b-form-checkbox>
      </b-form-checkbox-group>
    </b-form-group>

    <b-form-group>
      <template v-slot:label>
        <b>{{ $t('Deactivation date') }}</b>
      </template>
      <PMDatetimePicker v-model="deactivationDate"
                        :format="'YYYY-MM-DD'"
                        :withTime="false">
        <template v-slot:button-content-datepicker>
          <img src="/img/calendar2-fill.svg">
        </template>
      </PMDatetimePicker>
    </b-form-group>

    <b-form-group>
      <template v-slot:label>
        <b>{{ $t('Give this rule a name *') }}</b>
      </template>
      <b-form-input v-model="ruleName" placeholder="Enter your name"></b-form-input>
    </b-form-group>

    <b-form-group>
      <span>*=Required</span>
      <b-button variant="secondary"
                @click="onCancel">
        {{ $t('Cancel') }}
      </b-button>
      <b-button variant="primary"
                @click="onCreateRule">
        {{ $t('Create Rule') }}
      </b-button>
    </b-form-group>
  </div>
</template>

<script>
  import PMDatetimePicker from "../../components/PMDatetimePicker.vue";
  export default {
    components: {
      PMDatetimePicker
    },
    props: {
      count: {
        type: Number,
        default: 0
      },
      inboxRule: {
        type: Object,
        default: null
      }
    },
    data() {
      return {
        markAsPriority: false,
        reassign: false,
        checkboxValues: [],

        deactivationDate: "",
        ruleName: null
      };
    },
    watch: {
      deactivationDate(a, b) {
        console.log("deactivationDate", a, b);
      },
      inboxRule: {
        handler() {
          this.setInboxRuleData();
        },
        deep: true
      }
    },
    mounted() {
      this.setInboxRuleData();
    },
    methods: {
      onCancel() {
        this.$router.push({name: 'index'});
      },
      onCreateRule() {
        this.$router.push({name: 'index'});
      },
      setInboxRuleData() {
        if (this.inboxRule) {
          this.markAsPriority = this.inboxRule.mark_as_priority;
          this.reassign = this.inboxRule.reassign_to_user_id > 0,
                  this.deactivationDate = this.inboxRule.end_date;
          this.ruleName = this.inboxRule.name;
        }
      }
    }
  };
</script>
