<template>
  <div class="process-listing h-100">
    <CardProcess
      :key="index"
      :categoryId="categoryId"
    />
  </div>
</template>

<script>
import CardProcess from "./CardProcess.vue";
export default {
  props: ["categoryId"],
  components: {
    CardProcess,
  },
  data(){
    return {index:0};
  },
  watch: {
    categoryId(nVal,oVal){
      if(nVal !== oVal){
        this.index++;
      }
    }
  },
};
</script>

<style lang="scss" scoped>
@import '~styles/variables';

.process-listing {
  @media (max-width: $lp-breakpoint) {
    padding-left: 1em;
    padding-right: 1em;
  }
}
</style>