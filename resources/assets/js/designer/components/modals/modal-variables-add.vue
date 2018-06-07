<template>
  <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Create Permission">
      <form>
        <div class="form-group">
          <label for="groupOrUser" v-model="groupOrUser">{{groupOrUser}}</label>
          <input type="text" class="form-control" id="groupOrUser">
        </div>

        <div class="form-group">
        <label for="originTask" v-model="originTask">{{originTask}}</label>
        <select class="form-control" id="originTask" v-model="originSelect">
          <option v-for="select in targetSelectOptions">{{select}}</option>
        </select>
        </div>

        </div>
        <div class="form-group">
        <label for="typeSelect" v-model="type">{{type}}</label>
        <select class="form-control" id="typeSelect" v-model="typeSelect">
          <option v-for="select in typeSelectOptions">{{select}}</option>
        </select>
        </div>

        <div class="form-group">
        <label for="PermissionsSelect" v-model="permission">{{permission}}</label>
          <textarea class="form-control"></textarea>
        </div>
    </form>

    <template slot="modal-footer">
      <b-button class="btn-outline-secondary btn-md">
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
      'casesStatus': "Cases Status",
      'casesSelect': "All",
      'casesSelectOptions':[
        'All','Assigned','Unassigned','Completed','Paused'
      ],
      'targetTask': "Target Task",
      'targetSelect': 'All',
      'targetSelectOptions':[
        'All','Task 1', 'Task 2'
      ],
      'groupOrUser':"Group Or User",
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
