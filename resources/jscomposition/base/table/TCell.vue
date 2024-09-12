<template>
   <td class="tw-p-3">
      <slot v-if="!column.cellRenderer" :columns="columns" :column="column" :row="row">
         {{ getValue() }}
      </slot>

      <component v-else :is="column.cellRenderer()" />       
   </td>
</template>

<script>
import { defineComponent } from 'vue';
import { isFunction, get } from 'lodash';

export default defineComponent({
   props:{
      columns:{
         type:Array,
         default: ()=>[]
      },
      column:{
         type:Object,
         default: ()=>{}
      },
      row:{
         type:Object,
         default: ()=>{}
      }
   },
   setup(props, {emit}){
      const getValue = ()=>{
         if(isFunction(props.column?.formatter)){
            return props.column?.formatter(props.row, props.column, props.columns);
         }

         return get(props.row, props.column?.field) || "";
      }
      return {
         getValue
      }
   }
});
</script>