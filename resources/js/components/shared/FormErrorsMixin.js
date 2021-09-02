export default {
  methods: {
    formDescription(text, field, array) {
      if (this.errorState(field, array) === null) {
        return this.$t(text);
      } else {
        return null;
      }
    },
    errorState(field, array) {
      if (_.get(array, field, null)) {
        return false;
      } else {
        return null;
      }
    },
    errorMessage(field, array) {
      return _.get(array, `${field}.0`, '');
    }
  }
}
