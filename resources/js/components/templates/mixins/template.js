import { strLimit } from "../../../lib/strLimit";

export default {
  data() {
    return {

    };
  },
  mounted() {
  },
  methods: {
    strLimit,
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
