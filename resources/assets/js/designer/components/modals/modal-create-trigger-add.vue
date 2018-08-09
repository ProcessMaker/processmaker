<template>
  <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Create Custom Trigger">
      <form>
        <div class="form-group">
          <label for="title" v-model="title">{{title}}</label>
          <input type="text" class="form-control" id="title">
        </div>
        <div class="form-group">
          <label for="description">{{description}}</label>
          <textarea class="form-control" id="description" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label for="code">{{code}}</label>
          <textarea class="form-control bottom-label-form" id="code" rows="3"></textarea>
          <label class="form-group bottom-label">Press ctrl+space to get the function list</label>
        </div>
        <button type="submit" class="btn variable-buttons text-light">@@</button>
        <button type="submit" class="btn variable-buttons text-light">OPEN EDITOR</button>

    </form>

    <template slot="modal-footer">
      <b-button @click="onCancel" class="btn btn-outline-success btn-md">
        CANCEL
      </b-button>
      <b-button class="btn btn-success btn-sm">
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
      'title':"Title",
      'description':"Description",
      'code': "Code"
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
.variable-buttons{
  background-color: rgb(109,124,136);
  font-size: 14px;
  font-weight: 300;
}
.bottom-label-form{
  margin-bottom: -6px;
}
.bottom-label{
  font-size: 10px;
}
</style>
