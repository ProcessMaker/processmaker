<template>
  <div class="project">
    <b-navbar type="faded">
      <b-navbar-brand class="text-uppercase">
        {{ $t("Recent Assets") }}
      </b-navbar-brand>
      <b-navbar-nav align="end">
        <b-nav-item-dropdown right>
          <template #button-content>
            {{ $t("Filter by Type") }}
          </template>
          <b-dropdown-item href="#">
            Option 1
          </b-dropdown-item>
          <b-dropdown-item href="#">
            Option 2
          </b-dropdown-item>
        </b-nav-item-dropdown>
        <b-nav-item>
          <i class="fas fa-search" />
        </b-nav-item>
        <b-nav-item>
          <i class="fas fa-external-link-alt" />
        </b-nav-item>
      </b-navbar-nav>
    </b-navbar>
    <div class="container">
      <div class="content">
        <img
          class="image"
          src="/img/recent_assets.svg"
        >
        <div class="content-text">
          <span class="title">
            {{ $t("Recent Assets") }}
          </span>
          <p>{{ $t("No assets to display here yet") }}</p>
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
  height: 815px;
}
.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.image {
  width: 244px;
  height: 219px;
}
.content-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}
.title {
  color: var(--secondary-800, #44494E);
  /* Heading 2/Semi-Bold */
  font-family: Open Sans;
  font-size: 32px;
  font-style: normal;
  font-weight: 600;
  line-height: 38px; /* 118.75% */
  letter-spacing: -1.28px;
}
</style>
