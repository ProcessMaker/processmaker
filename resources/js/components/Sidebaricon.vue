<template>
    <div class="filter-bar justify-content-between" id="Sidebaricon">
      <li class="nav-item">
        <a :href="item.url" class="nav-link" v-b-tooltip.hover.bottomleft="item.title"  @click="toggle" :target="item.attributes.target">
          <i v-if="item.attributes.icon" class="fas nav-icon" :class="item.attributes.icon" ></i>
          <img v-if="item.attributes.file" :src="item.attributes.file" class="nav-icon" id="custom_icon">
          <span class="nav-text" v-if="expanded()" v-cloak >
            {{item.title}}
            <i v-if="item.children && item.children.length" class="float-right fas" :class="{'fa-caret-right': !isOpen, 'fa-caret-down': isOpen}"></i>
            <span v-if="count !== null" class="nav-badge float-right">{{ count }}</span>
          </span>
        </a>
        <ul v-if="item.children && item.children.length" class="nav nav-list flex-column" v-show="isOpen">
            <li v-for="item in item.children" :key="item.id" class="nav-item nav-pl">
              <a :href="item.url" class="nav-link"  v-b-tooltip.hover.bottomleft="item.title" v-show="item.attributes.icon">
                <i class="fas nav-icon" :class="item.attributes.icon" ></i> <span class="nav-text" v-if="expanded()" v-cloak>{{item.title}}<span v-if="count !== null" class="nav-badge float-right">{{ count }}</span></span>
              </a>
              <a :href="item.url" class="nav-link"  v-b-tooltip.hover.bottomleft="item.title" v-show="item.attributes.file">
                <img :src="item.attributes.file" class="nav-icon" id="custom_icon"><span class="nav-text" v-if="expanded()" v-cloak>{{item.title}}<span v-if="count !== null" class="nav-badge float-right">{{ count }}</span></span>
              </a>
            </li>
        </ul>
      </li>
    </div>
</template>

<script>
  export default {
    props: [
      'item',
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
        isOpen: false,
      }
    },
    methods: {
      toggle() {
        this.isOpen = !this.isOpen;
      },
      expanded() {
        return this.$parent.expanded
     }
    }
  }
</script>

<style>

</style>
