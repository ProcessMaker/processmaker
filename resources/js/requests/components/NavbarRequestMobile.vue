<template>
  <div
    id="navbarRequestMobile"
    class="d-flex bg-primary p-2 justify-content-start"
  >
    <button
      type="buttom"
      class="dropleft btn btn-primary"
      @click="returnTasks()"
    >
      <i class="fas fa-arrow-left" />
    </button>

    <div class="mx-1">
      <span class="lead text-white">
        {{ title }}
      </span>
    </div>
  </div>
</template>

<script>
export default {
  props: ["title"],
  data() {
    return {};
  },
  mounted() {
    console.log('holi', this.title);
  },
  methods: {
    returnTasks() {
      window.location = `/requests`;
    },
  },
};
</script>
