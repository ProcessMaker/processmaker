<template>
  <div class="list-group">
    <a href="#" id="object-menu-title" class="object-menu-items list-group-item text-light">Process Objects<i class="fas fa-minus-circle float-right"></i><i class="fas fa-sync-alt float-right"></i></a>
    <designer-options-menu-item v-for="(item, index) in menuItems" :key="item.id" :title="item.title"></designer-options-menu-item>
  </div>
</template>

<script>
import designerOptionsMenuItem from './designer-options-menu-item.vue'

export default {
  components:{
    'designer-options-menu-item': designerOptionsMenuItem
  },
  data(){
    return {
        menuItems:[
          {
            'title': 'Variables'
          },
          {
            'title': 'Message Types'
          },
          {
            'title': 'Forms'
          },
          {
            'title': 'Input Documents'
          },
          {
            'title': 'Output Documents'
          },
          {
            'title': 'Triggers'
          },
          {
            'title': 'Report Tables'
          },
          {
            'title': 'Database Connections'
          },
          {
            'title': 'Templates'
          },
          {
            'title': 'Public Files'
          },
          {
            'title': 'Permissions',
            'btnaction': 'openpermissionsmodal',
          },
          {
            'title': 'Case Tracker'
          },
          {
            'title': 'Supervisiors'
          },
        ]
    }
  },
}
</script>

<style>
  .list-group {
    width: 220px;
    font-size: 12px;
  }
  #object-menu-title{
    background-color: rgb(174,183,190);
  }
  #object-menu-icon{
    float: right;
    color: rgb(109,124,136)
  }
  .list-group-item{
    border:none;
  }
  #object-add-button{
    width: 63px;
    height: 22px;
    font-size: 12px;
    color: white;
  }
</style>
