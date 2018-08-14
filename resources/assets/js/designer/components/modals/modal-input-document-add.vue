<template>
  <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Create Input Document">
      <form>
        <div class="form-group">
          <label for="documentType" v-model="documentType">{{documentType}}</label>
          <select class="form-control" id="documentType" v-model="documentSelect">
          <option  v-for="select in documentSelectOptions">{{select}}</option>
          </select>
        </div>

        <div class="form-group">
          <label for="description">{{description}}</label>
          <textarea class="form-control" id="description" rows="3"></textarea>
        </div>

        <div class="form-group">
          <label for="enableVersioning" v-model="enableVersioning">{{enableVersioning}}</label>
          <select class="form-control" id="enableVersioning" v-model="enableVersioningSelect">
          <option v-for="select in enableVersioningSelectOptions">{{select}}</option>
          </select>
        </div>

        <div class="form-group">
          <label for="destinationPath">{{destinationPath}}</label>
          <div class="d-flex">
            <input type="text" class="form-control inline-input" id="destinationPath">
            <button type="submit" class="btn inline-button text-light">@@</button>
          </div>
        </div>

        <div class="form-group">
          <label for="tags">{{tags}}</label>
          <div class="d-flex">
            <input type="text" class="form-control inline-input" id="tags">
            <button type="submit" class="btn inline-button text-light">@@</button>
          </div>
        </div>

        <div class="form-group">
          <label for="allowedFileExtensions" v-model="allowedFileExtensions">{{allowedFileExtensions}}</label>
          <input type="text" class="form-control" id="allowedFileExtensions">
        </div>

        <div class="form-group">
          <label for="type" v-model="type">{{type}}</label>
          <input type="text" class="form-control" id="type">
        </div>

        <div class="d-flex justify-content-between">
          <div class="form-group">
            <label for="maximumFileSize" v-model="maximumFileSize">{{maximumFileSize}}</label>
            <input type="text" class="form-control input-and-select" id="maximumFileSize">
          </div>

          <div class="form-group">
            <label for="unit" v-model="unit">{{unit}}</label>
            <select class="form-control input-and-select" id="unit" v-model="unitSelect">
            <option v-for="select in unitSelectOptions">{{select}}</option>
            </select>
          </div>
        </div>
    </form>

    <div slot="modal-footer">
      <b-button @click="onCancel" class="btn btn-outline-success btn-sm text-uppercase">
        CANCEL
      </b-button>
      <b-button class="btn btn-success btn-sm text-uppercase">
        SAVE
      </b-button>
    </div>

  </b-modal>
</template>

<script>
export default {
  data() {
    return {
      // form models here
      'documentType': "Document Type",
      'documentSelect': "All",
      'documentSelectOptions':[
        'All','Assigned','Unassigned','Completed','Paused'
      ],
      'enableVersioning': "Enable Versioning",
      'enableVersioningSelect': "All",
      'enableVersioningSelectOptions':[
        'All','Assigned','Unassigned','Completed','Paused'
      ],
      'description':"Description",
      'originTask':"Origin Task",
      'originSelect': "All",
      'originSelectOptions':[
        'All','Task 1', 'Task 2'
      ],
      'destinationPath': "Destination Path",
      "tags":'Tags',
      'participationRequired':"Participation Required?",
      "allowedFileExtensions": 'Allowed File Extensions',
      "type": 'Type',
      "maximumFileSize": 'Maximum File Size',
      'unit':"Unit",
      'unitSelect': 'View',
      'unitSelectOptions':[
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
.inline-input{
  margin-right: 6px;
}
.inline-button{
  background-color: rgb(109,124,136);
  font-weight: 100;
}
.input-and-select{
  width:212px;
}
</style>
