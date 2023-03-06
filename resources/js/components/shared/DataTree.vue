<template>
    <ul class="tree">
      <data-node :node="data" :collapsable="collapsable" :show-icon="showIcon" :show-children-icon="showChildrenIcon"></data-node>
    </ul>
</template>

<script>

import DataNode from "./DataNode.vue";

export default {
  components: {
    DataNode,
  },
  mixins: [],
  props: ["data", "collapsable", "showIcon", "showChildrenIcon"],
  data() {
    return {
    };
  },
  mounted() {
  },
  methods: {
  },
};
</script>

<style>
ul.tree, ul.tree ul {
  list-style: none;
   margin: 0;
   padding: 0;
 }

 ul.tree ul {
   margin-left: 10px;
 }

 ul.tree li {
   margin: 0;
   padding: 0 7px;
   line-height: 20px;
   border-left:1px solid #768089;
 }

 ul.tree li:last-child {
   border-left:none;
 }

 ul.tree li:before {
    position:relative;
    top:-0.3em;
    height:1.4em;
    width:12px;
    color:white;
    border-bottom:1px solid #768089;
    content:"";
    display:inline-block;
    left:-7px;
 }

 ul.tree li:last-child:before {
    border-left:1px solid #768089;
 }
</style>
