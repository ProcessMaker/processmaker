<template>
    <img :src="icon.src" :class="icon.class" :style="icon.style" :title="icon.title" />
</template>
<script>
  export default {
    name: "BpmnTypeImage",
    props: ["icon"]
  };
</script>
