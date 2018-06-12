<template>
  <b-modal ref="modal" size="lg" @hidden="onHidden" centered title="Create Report Table">
      <form>
        <div class="form-group">
          <label for="tableName" v-model="tableName">{{tableName}}</label>
          <input type="text" class="form-control" id="tableName">
        </div>

        <div class="form-group">
          <label for="description">{{description}}</label>
          <textarea class="form-control" id="description" rows="3"></textarea>
        </div>

        <div class="d-flex justify-content-between">
          <div class="form-group">
            <label for="type" v-model="type">{{type}}</label>
            <select class="form-control input-and-select" id="type" v-model="typeSelect">
            <option v-for="select in typeSelectOptions">{{select}}</option>
            </select>
          </div>

          <div class="form-group">
            <label for="dbConnection" v-model="dbConnection">{{dbConnection}}</label>
            <select class="form-control input-and-select" id="dbConnection" v-model="dbConnectionSelect">
            <option v-for="select in dbConnectionSelectOptions">{{select}}</option>
            </select>
          </div>
        </div>

        <div>
          <div class="d-flex justify-content-between">
            <draggable class="drag-to-table-wrapper" v-model="list" :options="{group:'forms'}">
                <div class="drag-to-table" v-for="form in list">
                    <i class="fas fa-ellipsis-v fa-sm"></i><i class="fas fa-ellipsis-v fa-sm"></i>
                  {{form.form_field_full}}
                </div>
            </draggable>

            <i class="seperator fas fa-arrow-right"></i>

            <div class="table-wrapper">
              <div class="d-flex head-wrapper">
                <div class="table-head form-field">form FIELD</div>
                <div class="table-head name-field">NAME</div>
                <div class="table-head label-field">LABEL</div>
                <div class="table-head type-field">TYPE</div>
                <div class="table-head size-field">size</div>
                <div class="table-head increment-field">AUTO INCREMENT</div>
                <div class="table-head index-field">index</div>
              </div>
              <draggable class="table-target" v-model="list2" :options="{group:'forms'}">
                <div class="d-flex row-wrapper" v-for="form in list2">
                  <div class="form-field">
                      <i class="fas fa-ellipsis-v fa-sm"></i><i class="fas fa-ellipsis-v fa-sm"></i>
                    {{form.form_field}}
                  </div>
                  <div class="name-field">
                    {{form.name}}
                  </div>
                  <div class="label-field">
                    {{form.label}}
                  </div>
                  <div class="type-field">
                    {{form.type}}
                  </div>
                  <div class="size-field">
                    {{form.size}}
                  </div>
                  <div class="increment-field">
                    {{form.auto_increment}}
                  </div>
                  <div class="index-field">
                    {{form.index}}
                  </div>
                </div>
              </draggable>
            </div>
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
      'description':"Description",
      'reportTable': "Report Table",
      'formFields': "Form Fields",
      'tableName': 'Table Name',
      'type': 'Type',
      'typeSelect': "View",
      'typeSelectOptions':[
        'View', 'Block'
      ],
      'dbConnection':"DB Connection",
      'dbConnectionSelect': 'View',
      'dbConnectionSelectOptions':[
        'View', 'Block'
      ],
      list: [
        {
          form_field: "F3",
          form_field_full: "Form Field Name 1",
          name: "MilaRene",
          label: "human",
          type: "yes",
          size: 16,
          auto_increment: "nah",
          index: "cha"
        },
        {
          form_field: "F3",
          form_field_full: "Form Field Name 1",
          name: "MilaRene",
          label: "hello",
          type: "yes",
          size: 16,
          auto_increment: "nah",
          index: "cha"
        },
        {
          form_field: "F3",
          form_field_full: "Form Field Name 1",
          name: "MilaRene",
          label: "hello",
          type: "yes",
          size: 16,
          auto_increment: "nah",
          index: "cha"
        },
      ],

      list2: [
        {
          form_field: "F3",
          form_field_full: "Form Field Name 1",
          name: "MilaRene",
          label: "hello",
          type: "yes",
          size: 16,
          auto_increment: "nah",
          index: "cha"
        },
        {
          form_field: "F3",
          form_field_full: "Form Field Name 1",
          name: "MilaRene",
          label: "hello",
          type: "yes",
          size: 16,
          auto_increment: "nah",
          index: "cha"
        },
        {
          form_field: "F3",
          form_field_full: "Form Field Name 1",
          name: "MilaRene",
          label: "hello",
          type: "yes",
          size: 16,
          auto_increment: "nah",
          index: "cha"
        }
      ]
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
  margin-top: 31px;
  text-transform: uppercase;
}
.drag-to-table-wrapper{
  margin-top: 16px;
  border: 6px solid #f7f9fa;

  .drag-to-table{
    border: 1px solid #e9edf1;
    padding: 0 10px;
    font-size: 12px;
    font-weight: 300;
    width: 225px;
    height: 32px;
    line-height: 32px;

      i {
        color: #788793;
      }
  }

  .row-wrapper.sortable-ghost {
      border: 1px solid #e9edf1;
      padding: 0 10px;
      font-size: 12px;
      font-weight: 300;
      width: 225px;
      height: 32px;
      line-height: 32px;

        i {
          color: #788793;
        }
  }
}
.table-target{
  min-height: 100px;
}
.table-wrapper{
  width: 475px;
  border: 6px solid #f7f9fa;
  margin-top: 15px;
  margin-left: 3px;

  .head-wrapper{
    background-color: #e9edf1;
    padding:10px;
    font-size: 12px;
  }
  .row-wrapper{
    border: 1px solid #e9edf1;
    font-size: 12px;
    font-weight: 300;
    padding: 10px;

    &.sortable-chosen {
      border: 1px solid #e9edf1;
      padding: 0 10px;
      font-size: 12px;
      font-weight: 300;
      width: 225px;
      height: 32px;
      line-height: 32px;

        i {
          color: #788793;
        }
    }
  }
  .table-head{
    text-transform: uppercase;
    font-size: 12px;
    color: #788793;
    font-weight: 600;
  }
  i {
    color: #788793;
  }
}
.form-field{
  width: 90px;
}
.name-field{
  width: 75px;
}
.label-field{
  width: 55px;
}
.type-field{
  width: 55px;
}
.size-field{
  width: 45px;
}
.increment-field{
  width: 124px;
}
.index-field{
  width: 35px;
}
.seperator{
  margin-top: 85px;
  margin-left: 5px;
  color: #28d1ab;
}
</style>
