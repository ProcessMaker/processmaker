<template>
  <div class="form-group">
    <label>{{ $t(label)}}</label>
    <div v-if="loading">{{ $t('Loading...') }}</div>
    <div v-else>
      <select class="form-control" @change="updateValue">
        <option value></option>
        <option
          :value="screen.id"
          :selected="screen.id == value"
          v-for="screen in screens"
          :key="screen.id"
        >{{ screen.title }}</option>
      </select>
      <a href="#" @click="load">{{ $t('Refresh') }}</a>
    </div>
  </div>
</template>


<script>
export default {
  props: ["value", "label", "helper", "params"],
  data() {
    return {
      content: "",
      loading: true,
      screens: null
    };
  },
  computed: {
    node() {
      return this.$parent.$parent.highlightedNode.definition;
    }
  },
  mounted() {
    // Check to see if we already have a value set, if not, set it to first option
    // Also check if we have at least one option available
    if (!this.value && this.screens) {
      this.content = this.screens[0].id;
      this.$emit("input", this.content);
    }
    this.load();
  },
  watch: {
    currentValue: {
      handler() {
        this.$emit("change", this.currentValue);
      }
    }
  },
  methods: {
    updateValue(e) {
      this.content = e.target.value;
      this.$emit("input", this.content);
    },
    load() {
      this.loading = true;
      let params = Object.assign(
        { type: "FORM", per_page: 10000 },
        this.params
      );
      ProcessMaker.apiClient
        .get("/screens", {
          params: params
        })
        .then(response => {
          this.screens = response.data.data;
          this.loading = false;
        })
        .catch(err => {
          this.loading = false;
        });
    }
  }
};
</script>

<style lang="scss" scoped>
</style>