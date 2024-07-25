<template>
  <div id="navbarRequestMobile" class="d-flex bg-primary p-2 justify-content-center navbar-class position-relative">
    <button type="button" class="btn btn-primary position-absolute start-0" @click="returnTasks()">
      <i class="fas fa-arrow-left"></i>
    </button>
    <div class="mx-1 text-center w-100">
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
#navbarRequestMobile {
  position: relative;
}

#navbarRequestMobile .btn {
  position: absolute;
  left: 0;
}

#navbarRequestMobile .text-center {
  width: 100%;
  text-align: center;
}
</style>
