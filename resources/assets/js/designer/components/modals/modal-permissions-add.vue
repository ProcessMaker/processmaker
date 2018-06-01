<template>
  <b-modal id="add-modal-permissions" ref="modal" size="md" centered title="Create Permissions">
      <form>
        <div class="form-group">
          <label for="casesStatus" v-model="casesStatus">{{casesStatus}}</label>
          <select class="form-control" id="casesStatus" v-model="casesSelect">
          <option  v-for="select in casesSelectOptions">{{select}}</option>
          </select>
        </div>

        <div class="form-group">
          <label for="targetTask" v-model="targetTask">{{targetTask}}</label>
          <select class="form-control" id="targetTask" v-model="targetSelect">
          <option v-for="select in targetSelectOptions">{{select}}</option>
          </select>
        </div>

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
      <b-button class="btn-outline-secondary">
        CANCEL
      </b-button>
      <b-button class="btn-secondary text-light">
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
  mounted() {
    // Show our modal as soon as we're created
    this.$refs.modal.show();
  }
};
</script>
<style lang="scss" scoped>
  #add-modal-permissions >>> .modal-dialog {
    width: 464px;
    height: 573px;
  }
</style>
