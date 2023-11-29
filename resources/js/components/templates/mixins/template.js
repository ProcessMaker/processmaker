import Vue from "vue";

Vue.filter("str_limit", (value, size) => {
  if (!value) return "";
  value = value.toString();

  if (value.length <= size) {
    return value;
  }
  return `${value.substr(0, size)}...`;
});

export default {
    data() {
      return {
        
      };
    },
    mounted() {
    },
    methods: {
        showDetails() {
            this.$emit("show-details", { template: this.template });
        },
        addHoverClass(event) {
            event.target.classList.add("hover");
        },
        removeHoverClass(event) {
            event.target.classList.remove("hover");
        },
    },
  };
  