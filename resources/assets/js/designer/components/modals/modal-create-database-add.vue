<template>
  <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Create Permission">
      <form>
        <div class="form-group">
          <label for="engine" v-model="engine">{{engine}}</label>
          <select class="form-control" id="engine" v-model="engineSelect">
          <option  v-for="select in engineSelectOptions">{{select}}</option>
          </select>
        </div>

        <div class="form-group">
          <label for="encode" v-model="encode">{{encode}}</label>
          <select class="form-control" id="encode" v-model="encodeSelect">
          <option v-for="select in encodeSelectOptions">{{select}}</option>
          </select>
        </div>

        <div class="form-group">
          <label for="server" v-model="server">{{server}}</label>
          <input type="text" class="form-control" id="server">
        </div>

        <div class="form-group">
          <label for="databaseName" v-model="databaseName">{{databaseName}}</label>
          <input type="text" class="form-control" id="databaseName">
        </div>

        <div class="form-group">
          <label for="userName" v-model="userName">{{userName}}</label>
          <input type="text" class="form-control" id="userName">
        </div>

        <div class="form-group">
          <label for="password" v-model="password">{{password}}</label>
          <input type="text" class="form-control" id="password">
        </div>

        <div class="form-group">
          <label for="port" v-model="port">{{port}}</label>
          <input type="text" class="form-control" id="port">
        </div>

        <div class="form-group">
          <label for="description">{{description}}</label>
          <textarea class="form-control" id="description" rows="3"></textarea>
        </div>
    </form>

    <template slot="modal-footer">
      <b-button @click="onCancel" class="btn-outline-secondary btn-md">
        CANCEL
      </b-button>
      <b-button class="btn-secondary text-light btn-md">
        TEST CONNECTION
      </b-button>
    </template>

  </b-modal>
</template>

<script>
export default {
  data() {
    return {
      // form models here
      'engine': "Engine",
      'engineSelect': "All",
      'engineSelectOptions':[
        'All','Assigned','Unassigned','Completed','Paused'
      ],
      'encode': "Encode",
      'encodeSelect': 'All',
      'encodeSelectOptions':[
        'All','Task 1', 'Task 2'
      ],
      'server':"Server",
      'databaseName': "Database Name",
      'userName': "User Name",
      'password': "Password",
      'port': "Port",
      'description': "Description",
      'originTask':"Origin Task",
      'originSelect': "All",
      'originSelectOptions':[
        'All','Task 1', 'Task 2'
      ],
      'participationRequired':"Participation Required?",
      'type':"Type",
      'typeSelect': 'Form',
      'typeSelectOptions':[
        'Form', 'Attachment', 'Status', 'Summary', 'Comments','Input Document', 'Output Document'
      ],
      'permission':"Permission",
      'permissionSelect': 'View',
      'permissionSelectOptions':[
        'View', 'Block'
      ]
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

</style>
