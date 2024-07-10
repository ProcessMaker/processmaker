<template>
  <div
    id="navbarRequestMobile"
    class="d-flex bg-primary p-2 justify-content-start navbar-class"
  >
    <button
      type="buttom"
      class="dropleft btn btn-primary"
      @click="returnTasks()"
    >
      <i class="fas fa-arrow-left" />
    </button>

    <div class="mx-1">
      <span class="lead text-white font-weight-normal">
        {{ truncatedTitle }}
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
  computed: {
    truncatedTitle() {
      if (this.title.length > 30) {
        return this.title.slice(0, 30) + '...';
      }
      return this.title;
    }
  },
  methods: {
    returnTasks() {
      window.location = `/requests`;
    },
  },
};
</script>
<style scoped>
.navbar-class {
  background-color: #1572C2;
  height: 48px;
}
</style>
