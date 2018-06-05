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
          <div class="editor-wrapper">
            <Editor :init="{menubar: false, branding: false, height : 250, width: 350, toolbar_items_size : 'small', statusbar: false, skin: 'custom'}"></Editor>
          </div>
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
  .editor-wrapper{
    border: solid 1px #b6bfc6;
    padding: 0 20px 0 40px;
    border-radius: 2px;
  }
</style>
