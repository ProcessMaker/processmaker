const IsViewMixin = {
  data() {
    return {
      currentView: "main"
    };
  },
  watch: {
    currentView: {
      handler() {
        this.$emit("onChangeViews", this.currentView);
      }
    }
  },
  methods: {
    viewIs(...views) {
      return views.includes(this.currentView);
    },
    viewsTo(view) {
      this.currentView = view;
    }
  }
};
export default IsViewMixin;