<template>
  <div>
    <slot />
  </div>
</template>

<script>
export default {
  components: {},
  mixins: [],
  props: {},
  data() {
    return {};
  },
  methods: {
  },
  mounted() {
    // this.$slots.default
    //   .filter((slot) => slot.componentOptions?.tag === "pm-content")
    //   .forEach((slot) => {
    //     console.log('Emitting page', slot);
    //     this.$emit('page', slot);
    //   });
  },
};
</script>
