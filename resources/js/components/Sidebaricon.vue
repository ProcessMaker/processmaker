<template>
    <li class="nav-item filter-bar justify-content-between" id="Sidebaricon" v-b-tooltip.hover.right="{ animation: false, disabled: expanded(), boundary: 'viewport', delay: { show: 0, hide: 0 }, title: item.title }" :data-cy="item.title">
      <a :href="item.url" class="nav-link"  @click="toggle" :target="item.attributes.target" :aria-label="ariaLabel">
        <i v-if="item.attributes.icon" class="fas nav-icon" :class="item.attributes.icon" ></i>
        <i v-if="item.attributes.customicon" :class="item.attributes.customicon" ></i>
        <img v-if="item.attributes.file" :src="item.attributes.file" class="nav-icon" id="custom_icon">
        <span class="nav-text" v-show="expanded()" v-cloak >
          {{item.title}}
          <i v-if="item.children && item.children.length" class="float-right fas" :class="{'fa-caret-right': !isOpen, 'fa-caret-down': isOpen}"></i>
          <span v-if="count !== null" class="nav-badge float-right">{{ count }}</span>
        </span>
      </a>
      <ul v-if="item.children && item.children.length" class="nav nav-list flex-column" v-show="isOpen">
          <li v-for="item in item.children" :key="item.id" class="nav-item nav-pl">
            <a :href="item.url" class="nav-link" v-show="item.attributes.icon">
                <i class="fas nav-icon" :class="item.attributes.icon" ></i>
                <span class="nav-text" v-if="expanded()" v-cloak>{{item.title}}
                  <span v-if="count !== null" class="nav-badge float-right" :aria-label="ariaLabel">{{ count }}</span>
                </span>
            </a>
            <a :href="item.url" class="nav-link" v-show="item.attributes.file">
              <img :src="item.attributes.file" class="nav-icon" id="custom_icon"><span class="nav-text" v-if="expanded()" v-cloak>{{item.title}}<span v-if="count !== null" class="nav-badge float-right">{{ count }}</span></span>
                <span class="nav-text" v-if="expanded()" v-cloak>{{item.title}}
                  <span v-if="count !== null" class="nav-badge float-right" :aria-label="ariaLabel">{{ count }}</span>
                </span>
            </a>
          </li>
      </ul>
    </li>
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
    computed: {
      ariaLabel() {
        if (this.item.attributes.count !== undefined) {
          return this.item.title + ', ' + this.pluralize(this.count);
        } else {
          return this.item.title;
        }
      },
    },
    methods: {
      pluralize(count) {
        if (count == 1) {
          return this.$t('{{count}} Item', { count });
        } else {
          return this.$t('{{count}} Items', { count });
        }
      },
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
