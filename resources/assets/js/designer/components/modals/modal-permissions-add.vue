<template>
  <b-modal ref="modal" size="lg" centered title="Create Permissions">
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
          <option v-for="select in typeSelectOptions">{{select}}</option>
        </select>
        </div>
    </form>

  </b-modal>
</template>

<script>
export default {
  data() {
    return {
      // form models here
      'casesStatus': "Cases Status",
      'casesSelect': 1,
      'casesSelectOptions':[
        1,2,3,4,5
      ],
      'targetTask': "Target Task",
      'targetSelect': 1,
      'targetSelectOptions':[
        1,2,3,4,5
      ],
      'groupOrUser':"Group Or User",
      'originTask':"Origin Task",
      'originSelect': 1,
      'originSelectOptions':[
        1,2,3,4,5
      ],
      'participationRequired':"Participation Required?",
      'type':"Type",
      'typeSelect': 1,
      'typeSelectOptions':[
        1,2,3,4,5
      ],
      'permission':"Permission",
      'permissionSelect':1,
      'permissionSelectOptions':[
        1,2,3,4,5
      ]
    }
  },
  mounted() {
    // Show our modal as soon as we're created
    this.$refs.modal.show();
  }
};
</script>
