<template>
  <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Create Variable">
      <form>
        <div class="form-group">
          <label for="name" v-model="name">{{name}}</label>
          <input type="text" class="form-control" id="name">
        </div>

        <div class="form-group">
        <label for="type" v-model="type">{{type}}</label>
        <select class="form-control bottom-label-form" id="type" v-model="typeSelect">
          <option v-for="select in typeSelectOptions">{{select}}</option>
        </select>
        <label class="bottom-label" for="supportedcontrolls" v-model="supportedcontrolls">{{supportedcontrolls}}</label>
        </div>

        <div class="form-group">
          <label for="databaseConnection" v-model="databaseConnection">{{databaseConnection}}</label>
          <select class="form-control" id="databaseConnection" v-model="database">
          <option v-for="select in databaseOptions">{{select}}</option>
          </select>
        </div>

        <div class="form-group">
        <label for="sql" v-model="sql">{{sql}}</label>
          <textarea class="form-control"></textarea>
        </div>

        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="" id="defaultCheck1">
          <label class="form-check-label" for="defaultCheck1">
            Define accepted variable values.
          </label>
        </div>
    </form>

    <template slot="modal-footer">
      <b-button @click="onCancel" class="btn-outline-secondary btn-md">
        CANCEL
      </b-button>
      <b-button class="btn-secondary text-light btn-md">
        SAVE
      </b-button>
    </template>

  </b-modal>
</template>

<script>
export default {
  data() {
    return {
      // form models here
      'name':"Name",
      'type':"Type",
      'supportedcontrolls': 'Supported Controls: text, textarea, dropdown',
      'typeSelect': "",
      'typeSelectOptions':[
        'All','Task 1', 'Task 2'
      ],
      'databaseConnection':"Database Connection",
      'database': 'Form',
      'databaseOptions':[
        'Form', 'Attachment', 'Status', 'Summary', 'Comments','Input Document', 'Output Document'
      ],
      'sql': "SQL"
    }
  },
  methods:{
    onHidden() {
      this.$emit('hidden')
    },
    onCancel() {
      this.$refs.modal.hide()
    }
  },
  mounted() {
    // Show our modal as soon as we're created
    this.$refs.modal.show();
  }
};
</script>
<style lang="scss" scoped>
  .form-check-input{
    margin-top: 8px;
  }
  .bottom-label{
    font-size: 10px;
  }
  .bottom-label-form{
    margin-bottom: -6px;
  }
</style>
