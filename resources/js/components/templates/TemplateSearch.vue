<template>
  <div>
    <div class="pb-2">
      <b-input-group>
        <b-input-group-prepend>
          <b-btn class="btn-search-run px-2" :title="$t('Search Templates')">
            <i class="fas fa-search search-icon"></i>
          </b-btn>
        </b-input-group-prepend>
        <b-form-input class="pl-0" :placeholder="$t('Search Templates')"></b-form-input>
      </b-input-group>
    </div>
    <div class="pb-2 template-container" >
      <b-card-group deck class="d-flex justify-content-between">
        <template-select-card v-for="(template, index) in templates" :key="index" :template="template" @show-details="showDetails($event)"/>        
      </b-card-group>
    </div>    
  </div>
</template>

<script>
import TemplateSelectCard from "./TemplateSelectCard.vue";

export default {
  components: { TemplateSelectCard},
  props: ['templates'],
  data() {
    return {
    };
  },
  computed: {},
  watch: {},
  beforeMount() {},
  methods: {
    showDetails($event) {
      this.$emit('show-details', $event);      
    }
  },
};
</script>

<style lang="scss" scoped>
.btn-search-run {
  background-color: #ffffff;
  border-color: #b6bfc6;
  border-right: 0;
  border-radius: 4px;
}

.btn-search-run:active,
  .btn-search-run:focus {
    border-right-width: 0;
    box-shadow: none !important;
    outline: 0 !important;
  }

.search-icon {
  color: #6C757D;
}

.template-container {
  height: 567px;
  overflow-x: hidden;
  overflow-y: auto;
}
</style>
