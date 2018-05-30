<template>

<a href="#" class="object-menu-items list-group-item list-group-item-action font-weight-bold" v-on:mouseover="showbutton" v-on:mouseout="showbutton">
    {{title}}
    <i v-if="buttonvisible == false" id="object-menu-icon" class="fas fa-plus-circle"></i>
    <button id="object-add-button" v-if="buttonvisible == true" type="button" class="btn btn-secondary btn-sm float-right"><i class="fas fa-plus fa-xs"></i>ADD</button>
</a>

</template>


<script>
export default{
  props:[
    'title'
  ],
  data(){
    return{
      buttonvisible:false
    }
  },
  methods:{
    showbutton(){
      this.buttonvisible = !this.buttonvisible
    }
  }
}
</script>
<style lang="scss" scoped>

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
