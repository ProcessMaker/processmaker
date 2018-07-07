export default {
  data() {
    return {
      validationErrors: {}
    }
  },
  methods: {
    updateValidationErrors(errors) {
      // Clear existing Validation Errors object
      // Re-Update Validation Errors with new Errors
      this.validationErrors = Object.assign({}, errors);
    },
    hasValidationError(key) {
      return (key in this.validationErrors);
    },
    getFirstValidationError(key) {
      if(!this.hasValidationError(key)) {
        return null;
      }
      return this.validationErrors[key][0];
    }
  }
}
