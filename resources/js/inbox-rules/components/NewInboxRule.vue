<template>
  <div>
    <h4>{{$t('New Inbox Rule')}}</h4> <!--Edit id {{ $route.params.id }}-->
    
    

    <b-form-group label="What do we do with tasks that fit this filter?">
      <b-form-radio v-model="markAsPriority" name="actionsTask" value="false">
        {{ $t('Mark as Priority') }}
      </b-form-radio>
      <b-form-radio v-model="reassing" name="actionsTask" value="false">
        {{ $t('Reassing') }}
      </b-form-radio>
    </b-form-group>

    <b-form-group label="Rule Behavior">
      <b-form-checkbox-group v-model="checkboxValues"
                             name="ruleBehavior"
                             stacked >
        <b-form-checkbox value="true">
          {{ $t('Apply to current inbox matching tasks (8)') }}
        </b-form-checkbox>
        <b-form-checkbox value="true">
          {{ $t('Apply to Future tasks') }}
        </b-form-checkbox>
      </b-form-checkbox-group>
    </b-form-group>

    <b-form-group label="Deactivation date">
      <b-form-datepicker v-model="deactivationDate">
      </b-form-datepicker>
    </b-form-group>

    <b-form-group label="Give this rule a name*">
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
  export default {
    components: {
    },
    props: {
    },
    data() {
      return {
        markAsPriority: false,
        reassing: false,
        checkboxValues: [],

        deactivationDate: null,
        ruleName: null,
        savedSearchSelected: null,
        savedSearch: [
          {value: 1, text: "saved search 1"},
          {value: 2, text: "saved search 2"},
          {value: 3, text: "saved search 3"},
          {value: 4, text: "saved search 4"}
        ]
      };
    },
    mounted() {
    },
    methods: {
      onCancel() {
        this.$router.push({name: 'index'});
      },
      onCreateRule() {
        this.$router.push({name: 'index'});
      }
    }
  };
</script>
