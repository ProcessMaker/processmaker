<template>
  <div class="pb-2">
    <b-card no-body class="template-select-card" @click="showDetails()">
      <!-- <div v-html="thumbnail" class="card-img card-img-top img-fluid mb-3"></div> -->
      <b-card-body :title="template.name" class="card-body">
        <b-card-text>
          {{ template.description }}
        </b-card-text>
        <b-badge pill variant="success" class="category-badge mb-3">
          Category 1
        </b-badge>
      </b-card-body>
    </b-card>
  </div>
</template>

<script>

export default {
  components: {},
  props: ['template'],
  data() {
    return {
      thumbnail: null,
    };
  },
  computed: {},
  watch: {},
  beforeMount() {},
  methods: {
    showDetails() {      
      this.$emit('show-details', {"template": this.template});
    }
  },
  mounted() {
    this.thumbnail = this.template.svg;
  }
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
  font-size: 14px;
}

.card-img {
  background: #80808017;
  height: 112px;
  display: flex;
  align-items: center;
}

.card-body {
  padding: 2px!important;
}

.card-text {
  font-size: 12px;
  color: #6C757D;
}

.category-badge {
  background-color: #E6FFEB;
  color: #006644;
  font-size: 10px;
  border: 1px solid #006644;
}

</style>
