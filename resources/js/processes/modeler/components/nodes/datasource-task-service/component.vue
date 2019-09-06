<template>
  <div></div>
</template>

<script>
import { task } from "@processmaker/modeler";
import icon from "./marker.svg";

/**
 * A new control based on a Task node.
 *
 */
export default {
  extends: task.component,
  methods: {
    /**
     * Define the inspector loaded when nthe control is selected
     */
    handleClick() {
      this.$parent.loadInspector(
        "package-data-sources/datasource-task-service",
        this.node.definition,
        this
      );
    }
  },
  mounted() {
    // Define the task marker
    this.shape.attr("image/xlink:href", icon);
  }
};
</script>
