<template>
  <div class="project mt-3">
    <b-navbar type="faded">
      <b-navbar-brand class="text-uppercase">
        {{ $t("My Projects") }}
      </b-navbar-brand>
      <b-navbar-nav align="end">
        <b-nav-item>
          <i class="fas fa-search" />
        </b-nav-item>
        <b-nav-item href="/designer/projects">
          <i class="fas fa-external-link-alt" />
        </b-nav-item>
      </b-navbar-nav>
    </b-navbar>
    <div class="container">
      <div class="content">
        <img
          class="image"
          src="/img/recent_projects.svg"
          alt="recent projects"
        >
        <div class="content-text">
          <span class="title">
            {{ $t("Recent Projects") }}
          </span>
          <p>{{ $t("You are not part of a project yet") }}</p>
          <b-link href="#">
            {{ $t("Create a Project") }}
          </b-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return { };
  },
};
</script>

<style scoped>
.project {
  background-color: #F9F9F9;
}
.card {
  border-radius: 8px;
}
.navbar-expand {
  flex-flow: row nowrap;
  justify-content: space-between;
}
.container {
  display: flex;
  justify-content: center;
  align-items: center;
  flex: 1 0 0;
  align-self: stretch;
  width: 100%;
  height: 450px;
}
.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.image {
  width: 214px;
  height: 194px;
}
.content-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}
.title {
  color: var(--secondary-800, #44494E);
  font-size: 32px;
  font-style: normal;
  font-weight: 600;
  line-height: 38px;
  letter-spacing: -1.28px;
}
</style>
