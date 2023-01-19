<template>
  <div v-if="node.isRoot">
    <div class="flex-column d-inline-flex">
      <span>{{ node.label }}</span>
    </div>

    <ul v-if="node.children && node.children.length" class="tree ml-4">
      <node v-for="(child, key) in node.children" :key="key" :node="child" />
    </ul>
  </div>

  <li v-else class="">
    <div class="flex-column d-inline-flex">
      <span class="mb-2">{{ node.label }}</span>
    </div>

    <ul v-if="node.children && node.children.length" class="tree ml-4">
      <node v-for="(child, key) in node.children" :key="key" :node="child" />
    </ul>
  </li>
</template>

<script>
export default {
  name: "node",
  props: {
    node: {
      type: Object,
      default() {
        return {
          label: "",
          isRoot: true,
          children: [],
        };
      },
    },
  },
};
</script>
