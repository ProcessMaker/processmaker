<template>
  <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Create Permission">
      <form>
        <div class="form-group">
          <label for="name" v-model="name">{{name}}</label>
          <input type="text" class="form-control" id="name">
        </div>

        <div class="form-group">
        <label for="type" v-model="type">{{type}}</label>
        <select class="form-control" id="type" v-model="typeSelect">
          <option v-for="select in typeSelectOptions">{{select}}</option>
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

        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="" id="defaultCheck1">
          <label class="form-check-label" for="defaultCheck1">
            Default checkbox
          </label>
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
      'name':"Name",
      'type':"Type",
      'typeSelect': "All",
      'typeSelectOptions':[
        'All','Task 1', 'Task 2'
      ],
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
