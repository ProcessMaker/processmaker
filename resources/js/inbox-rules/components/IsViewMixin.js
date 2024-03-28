const IsViewMixin = {
  data() {
    return {
      view_name: 1
    };
  },
  watch: {
    view_name: {
      handler() {
        this.$emit("view-name", this.view_name);
      }
    }
  },
  methods: {
    isViewName(name) {
      return this.view_name === name;
    },
    viewName(name) {
      this.view_name = name;
    }
  }
};
export default IsViewMixin;