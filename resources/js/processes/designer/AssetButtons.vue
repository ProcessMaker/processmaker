<template>
  <b-card-text>
    <div class="button-container">
      <b-button
        size="sm"
        class="custom-button mb-2"
        block
        @click="openAsset(urlAsset)"
      >
        <p class="custom-text"> {{ $t(asset_name_new) }} </p>
        <i class="fas fa-arrow-right ml-2 align-middle text-primary" />
      </b-button>
    </div>
    <div class="button-container">
      <b-button
        size="sm"
        class="custom-button"
        block
        @click="callURL()"
      >
        <p class="custom-text"> {{ $t(asset_name_all) }} </p>
        <i class="fas fa-arrow-right ml-2 align-middle text-primary" />
      </b-button>
    </div>
  </b-card-text>
</template>

<script>
export default {
  props: {
    asset_name_all: String,
    urlPath: String,
    urlAsset: String,
    asset_name: String,
    asset_name_new: String,
  },
  data() {
    return {};
  },
  methods: {
    callURL() {
      window.location.href = window.location.origin + this.urlPath;
    },
    openAsset(url) {
      window.open(url, "_blank");
    },
  },
};
</script>

<style scoped>
.card-text {
  width: 100%;
}
.button-container {
  display: block;
  width: 100%;
}
.b-card {
  border: none;
  padding: 0;
}
.b-card .btn {
  width: 100%;
}
.custom-button {
  display: flex;
  padding: 8px 12px;
  justify-content: space-between;
  text-align: start;
  align-items: center;
  flex: 1 0 0;
  align-self: stretch;
  border-radius: 4px;
  border: 1px solid #b7d8ff;
  background: #edf4ff;
}
.custom-button:hover {
  background: #d1e3fe;
}
.custom-text {
  color: #5e6469;
  font-family: "Open Sans", sans-serif;
  font-size: 14px;
  font-style: normal;
  font-weight: 400;
  line-height: normal;
  letter-spacing: -0.28px;
  text-transform: capitalize;
  margin: 0px;
}
</style>
