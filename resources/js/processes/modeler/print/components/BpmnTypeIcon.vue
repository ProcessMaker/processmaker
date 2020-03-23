<template>
    <i :class="icon.class" :style="icon.style" :title="icon.title"></i>
</template>
<script>
  export default {
    name: "BpmnTypeIcon",
    props: ["icon"]
  };
</script>
