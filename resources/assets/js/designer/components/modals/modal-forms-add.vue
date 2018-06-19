<template>
  <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Create Blank Form">
      <form>
        <div class="form-group">
          <label for="formTitle" v-model="formTitle">{{formTitle}}</label>
          <input type="text" class="form-control" id="formTitle">
        </div>

        <div class="form-group">
          <label for="formDescription" v-model="formDescription">{{formDescription}}</label>
          <textarea rows="5" class="form-control" id="formDescription"></textarea>
        </div>

    </form>

    <template slot="modal-footer">
      <b-button class="btn-outline-secondary btn-md">
        CANCEL
      </b-button>
      <b-button class="btn-secondary text-light btn-md">
        SAVE & OPEN
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
      'formTitle':"Title",
      'formDescription':"Description",

      messageFields: []
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
