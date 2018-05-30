<template>

<span class="object-menu-items list-group-item list-group-item-action font-weight-bold" v-on:mouseover="showbutton" v-on:mouseout="showbutton">
    {{title}}
    <i v-if="buttonvisible == false" id="object-menu-icon" class="fas fa-plus-circle"></i>
    <button id="object-add-button" v-if="buttonvisible == true" type="button" class="btn btn-secondary btn-sm float-right" @click='btn-action'><i class="fas fa-plus fa-xs"></i>ADD</button>
</span>

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
    },
    openpermissionsmodal(){
      console.log('permissions')
    }
  }
}
</script>
