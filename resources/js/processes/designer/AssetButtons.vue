<template>
  <b-card-text>
    <div class="button-container">
      <b-button
        size="sm"
        class="custom-button custom-text mb-2"
        block
        @click="openAsset(urlAsset)"
      >
        {{ $t(asset_name_new) }}
        <i class="fas fa-arrow-right ml-2 align-middle" />
      </b-button>
    </div>
    <div class="button-container">
      <b-button
        size="sm"
        class="custom-button custom-text"
        block
        @click="callURL()"
      >
        {{ $t(asset_name_all) }}
        <i class="fas fa-arrow-right ml-2 align-middle" />
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
  align-items: center;
  flex: 1 0 0;
  align-self: stretch;
  border-radius: 4px;
  border: 1px solid #b7d8ff;
  background: #d1e3fe;
}
.custom-text {
  color: #5e6469;
  font-family: "Open Sans", sans-serif;
  font-size: 12px;
  font-style: normal;
  font-weight: 400;
  line-height: normal;
  letter-spacing: -0.28px;
}
</style>
