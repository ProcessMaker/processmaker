<template>
  <div class="pb-2">
    <b-card
      class="template-select-card"
      title="Template Name"
      img-src="https://via.placeholder.com/261x112"
      img-alt="Image"
      img-top
    >
      <b-card-text class="pb-0">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
      </b-card-text>
      <b-badge pill variant="success" class="category-badge">
        Category 1
      </b-badge>
    </b-card>
  </div>
</template>

<script>

export default {
  components: { },
  props: [],
  data() {
    return {
    };
  },
  computed: {},
  watch: {},
  beforeMount() {},
  methods: {},
};
</script>

<style lang="scss" scoped>
.template-select-card {
  width: 277px;
  height: 277px;
  border-radius: 2px;
  padding: 10px 8px 10px 8px;
  overflow: hidden;
}

.card-title {
  font-weight: 600;
  margin-left: -15px;
  margin-top: -5px;
  font-size: 14px;
}

.card-text {
  margin-left: -15px;
  margin-bottom: 0px;
  font-size: 12px;
  color: #6C757D;
}

.category-badge {
  margin-left: -15px;
  background-color: #E6FFEB;
  color: #006644;
  font-size: 10px;
  border: 1px solid #006644;
}

</style>
