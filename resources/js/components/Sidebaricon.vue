<template>
    <div class="filter-bar justify-content-between" id="Sidebaricon">
      <li class="nav-item">
        <a href="item.url()" class="nav-link" title="item.title" v-show="item.attributes.icon">
          <i class="fas nav-icon" :class="item.attributes.icon" ></i> <span class="nav-text" v-if="expanded()" v-cloak>{{item.title}}</span>
        </a>
        <a href="item.url()" class="nav-link" title="item.title" v-show="item.attributes.file">
          <img :src="item.attributes.file" class="nav-icon" id="custom_icon"><span class="nav-text" v-if="expanded()" v-cloak>{{item.title}}</span>
        </a>
      </li>
    </div>
</template>



<script>
  export default {
    props: [
      'item'
    ],
    data () {
      return {
       
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
