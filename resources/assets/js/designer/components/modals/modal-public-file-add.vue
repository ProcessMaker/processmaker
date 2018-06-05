<template>
  <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Create Public File">
      <form>
        <div class="form-group">
          <label for="publicFile" v-model="publicFile">{{publicFile}}</label>
          <select class="form-control" id="publicFile" v-model="publicFileSelect">
          <option  v-for="select in publicFileSelectOptions">{{select}}</option>
          </select>
        </div>

        <div class="form-group">
        <label>Content</label>
          <Editor :init="{menubar: false, branding: false, height : 250, theme: 'modern'}"></Editor>
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
import Editor from '@tinymce/tinymce-vue';

export default {
  components:{
    Editor
  },
  data() {
    return {
      // form models here
      'publicFile': "File Name",
      'publicFileSelect': "All",
      'publicFileSelectOptions':[
        'All','Assigned','Unassigned','Completed','Paused'
      ],
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
