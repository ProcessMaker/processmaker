<template>
  <div class="pb-2">
    <b-card no-body class="template-select-card" @click="showDetails()">
      <b-card-body :title="template.name | str_limit(30)" class="card-body">
        <b-card-text>
          {{ template.description | str_limit(150) }}
        </b-card-text>
        <b-badge v-for="category in categories" :key="category.id" pill variant="success" class="category-badge mb-3 mr-1"> 
          {{ category.name }}
        </b-badge>
        <small v-if="template.categories.length > 3" class="text-muted">+{{ catCount }}</small>
      </b-card-body>
    </b-card>
  </div>
</template>

<script>

Vue.filter('str_limit', function (value, size) {
  if (!value) return '';
  value = value.toString();

  if (value.length <= size) {
    return value;
  }
  return value.substr(0, size) + '...';
});

export default {
  components: {},
  props: ['template'],
  data() {
    return {
      thumbnail: null,
      catLimit: 3,
    };
  },
  computed: {
    categories() {
      return this.catLimit ? this.template.categories.slice(0,this.catLimit) : this.template.categories;
    },
    catCount() {
      const length = this.template.categories.length;
      return  length  - this.catLimit;
    }
  },
  watch: {},
  beforeMount() {},
  methods: {
    showDetails() {      
      this.$emit('show-details', {"template": this.template});
    }
  },
  mounted() {
  }
};
</script>

<style lang="scss" scoped>
.template-select-card {
  width: 277px;
  height: 180px;
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
