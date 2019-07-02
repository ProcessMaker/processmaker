<template>
    <div class="filter-bar justify-content-between" id="Sidebaricon">
      <li class="nav-item">
        <a :href="url" class="nav-link" :title="item.title" v-show="item.attributes.icon">
          <i class="fas nav-icon" :class="item.attributes.icon" ></i> <span class="nav-text" v-if="expanded()" v-cloak>{{item.title}}<span v-if="count !== null" class="nav-badge float-right">{{ count }}</span></span>
        </a>
        <a :href="url" class="nav-link" :title="item.title" v-show="item.attributes.file">
          <img :src="item.attributes.file" class="nav-icon" id="custom_icon"><span class="nav-text" v-if="expanded()" v-cloak>{{item.title}}<span v-if="count !== null" class="nav-badge float-right">{{ count }}</span></span>
        </a>
      </li>
    </div>
</template>

<script>
  export default {
    props: [
      'item',
      'url',
    ],
    mounted() {
      if (this.item.attributes.count !== undefined) {
        this.count = this.item.attributes.count;
      }
      
      if (this.item.attributes.countId !== undefined) {
        ProcessMaker.EventBus.$on('sidebar-count-updated-' + this.item.attributes.countId, count => {
          this.count = count;
        });
      }
    },
    data () {
      return {
        count: null,
      }
    },
    methods: {
     expanded() {
       return this.$parent.expanded
     }
    }
  }
</script>

<style>

</style>
