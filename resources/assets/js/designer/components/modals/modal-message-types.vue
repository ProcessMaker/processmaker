<template>
  <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Create Message Type">
      <form>
        <div class="form-group">
          <label for="messageName" v-model="messageName">{{messageName}}</label>
          <input type="text" class="form-control" id="groupOrUser">
        </div>

        <div class="form-group">
          <label for="messageFieldName">{{messageFieldName}}</label>
          <div class="d-flex">
            <input type="text" class="form-control inline-input" id="destinationPath">
            <button type="submit" class="btn inline-button text-light">Create</button>
          </div>
          <div class="sub-header">
            <label for="messageFieldName" class="field-name text-uppercase">{{messageFieldName}}</label>
          </div>

            <ul class="p-0">
              <li v-for="item in items" class="field-name-li">
                {{ item.message }} 
                <div>
                  <i class="fas fa-pencil-alt mr-3"></i>
                  <i class="fas fa-trash-alt"></i>
                </div>

              </li>

            </ul>

        </div>

    </form>

    <div slot="modal-footer">
        <b-button @click="onCancel" class="btn btn-outline-success btn-md">
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
      'messageName': "Name",
      'messageFieldName': "Message Field Name",
          items: [
      { message: 'Foo' },
      { message: 'Bar' }
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
.sub-header {
  background-color: rgb(234, 236, 241);
  margin-top: 10px;
  border-radius: 2px;
}
.field-name {
  color: black;
  font-weight: bold;
  padding: 10px;
  margin-bottom: 0px;
}
.field-name-li { 
  display: flex;
  justify-content: space-between;
  padding: 10px 16px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.2);
  color: rgb(109,124,136);
  font-size: 12px;
}

</style>
