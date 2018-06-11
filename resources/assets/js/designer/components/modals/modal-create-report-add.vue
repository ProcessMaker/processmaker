<template>
  <b-modal ref="modal" size="lg" @hidden="onHidden" centered title="Create Input Document">
      <form>
        <div class="form-group">
          <label for="allowedFileExtensions" v-model="allowedFileExtensions">{{allowedFileExtensions}}</label>
          <input type="text" class="form-control" id="allowedFileExtensions">
        </div>

        <div class="form-group">
          <label for="description">{{description}}</label>
          <textarea class="form-control" id="description" rows="3"></textarea>
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

        <div class="d-flex justify-content-between">
          <div class="form-group">
            <label for="maximumFileSize" v-model="maximumFileSize">{{maximumFileSize}}</label>
            <input type="text" class="form-control search-and-add" id="maximumFileSize">
          </div>

          <div class="form-group">
            <label for="unit" v-model="unit">{{unit}}</label>
            <div>
              <button class="btn btn-gray text-light"><i class="fas fa-plus"></i> FIELD </button>
            </div>
          </div>
        </div>

        <div>
          <div class="d-flex justify-content-between">
            <draggable v-model="list" :options="{group:'people'}">
              <div v-for="person in list">{{person.name}}</div>
            </draggable>

            <draggable v-model="list2" :options="{group:'people'}">
              <div v-for="person in list2">{{person.name}}</div>
            </draggable>
          </div>
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
import draggable from 'vuedraggable'

export default {
  components:{
    draggable
  },
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
      ],
      list: [{
        name: "Mila"
      }, {
        name: "Taylor"
      }, {
        name: "Ben"
      }],
      list2: [{
        name: "Alan"
      }, {
        name: "Matt"
      }, {
        name: "Leah"
      }]
    }
  },
  methods:{
    onHidden() {
      this.$emit('hidden')
    },
    onCancel() {
      this.$refs.modal.hide()
    },
    add: function() {
     this.list.push({
       name: 'Mila'
     });
   },
   replace: function() {
     this.list = [{
       name: 'Taylor'
     }]
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
  width:362px;
}
.search-and-add{
  width: 212px;
}
.btn-gray{
  background-color: rgb(109, 124, 136);
}
</style>
