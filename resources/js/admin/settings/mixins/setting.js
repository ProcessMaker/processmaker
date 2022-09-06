export default {
  methods: {
    emitSaved(value) {
      const setting = this.copy(this.setting);
      setting.config = value;
      this.$emit("saved", setting);
    },
    copy(object) {
      return JSON.parse(JSON.stringify(object));
    },
    trimmed(string) {
      if (string) {
        if (string.length > 48) {
          return `${string.slice(0, 48)}...`;
        }
        return string;
      }
      return "";
    },
    ui(key) {
      if (this.setting && this.setting.ui && this.setting.ui[key] !== undefined) {
        return this.setting.ui[key];
      }
      return null;
    },
  },
};
