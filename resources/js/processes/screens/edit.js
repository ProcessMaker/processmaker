import Vue from 'vue';
import screenEdit from './components/fields-screen';

new Vue({
  el: '#screen-edit',
  data: {},
  components: {
    screenEdit
  },
  methods: {
    onClose() {
      window.location.href = '/designer/screens';
    },
    onSave() {
      this.$refs.screenEdit.onSave();
    },
    afterUpdate() {
      ProcessMaker.alert('The screen was saved.', 'success');
      this.onClose();
    }
  }
});
