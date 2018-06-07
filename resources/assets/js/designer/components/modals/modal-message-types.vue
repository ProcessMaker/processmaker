<template>
  <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Create Message Type">
      <form>
        <div class="form-group">
          <label for="messageName" v-model="messageName">{{messageName}}</label>
          <input type="text" class="form-control" id="groupOrUser">
        </div>

        <div class="form-group">
          <label for="fieldName" v-model="fieldName">{{fieldName}}</label>
          <input type="text" class="form-control" id="groupOrUser">
        </div>

        <div class="form-group">
        <label for="originTask" v-model="originTask">{{originTask}}</label>
        <select class="form-control" id="originTask" v-model="originSelect">
          <option v-for="select in targetSelectOptions">{{select}}</option>
        </select>
        </div>
        <div class="form-group">
          <label v-model="participationRequired">{{participationRequired}}</label>
          <div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio1" value="option1">
            <label class="form-check-label" for="inlineRadio1">Yes</label>
          </div>

          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio2" value="option2">
            <label class="form-check-label" for="inlineRadio2">No</label>
          </div>
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
        <select class="form-control" id="PermissionsSelect" v-model="permissionSelect">
          <option v-for="select in permissionSelectOptions">{{select}}</option>
        </select>
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
      'messageName': "Name",
    //   'casesSelect': "All",
    //   'casesSelectOptions':[
    //     'All','Assigned','Unassigned','Completed','Paused'
    //   ],
      // 'fieldName': "Message Field Name",
      // 'targetSelect': 'All',
      // 'targetSelectOptions':[
      //   'All','Task 1', 'Task 2'
      // ],
      'fieldName':"Message Field Name",
      // 'originTask':"Origin Task",
      // 'originSelect': "All",
      // 'originSelectOptions':[
      //   'All','Task 1', 'Task 2'
      // ],
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
