<template>
  <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Create Message Type">
      <form>
        <div class="form-group">
          <label for="messageName" v-model="messageName">{{messageName}}</label>
          <input type="text" class="form-control" id="groupOrUser">
        </div>

        <div class="form-group">
          <label for="fieldName">{{fieldName}}</label>
          <div class="d-flex">
            <input type="text" class="form-control inline-input" id="destinationPath">
            <button type="submit" class="btn inline-button text-light">Create</button>
          </div>
          <div class="sub-header rounded">
            <label for="messageFieldName" class="field-name text-uppercase">{{messageFieldName}}</label>
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
export default {
  data() {
    return {
      // form models here
      'messageName': "Name",
      'messageFieldName': "Message Field Name",
      'fieldName':"Message Field Name",
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
  background-color: rgb(190, 190, 190);
}
.field-name {
  color: black;
  font-weight: bold;
}

</style>
