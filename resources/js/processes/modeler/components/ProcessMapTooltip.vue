<template>
    <div id="tooltip" class="card">
        <div class="card-body" style="padding-top: 10px; padding-bottom: 5px">
        <p class="tooltip-title" ><span class="text-info">Submit Request Aproval</span></p>
        <p class="tooltip-data" ><span class="tooltip-data-title">Status:</span><span class="text-secondary">Complete</span></p>
        <p class="tooltip-data" ><span class="tooltip-data-title">Completed By:</span><span class="text-secondary">UserName</span></p>
        <p class="tooltip-data" ><span class="tooltip-data-title">Time Started:</span><span class="text-secondary">11/21/23 16:51</span></p>
        <p class="tooltip-data" ><span class="tooltip-data-title">Time Completed:</span><span class="text-secondary">11/21/23 16:53</span></p>
        </div>
    </div> 
</template>
<script>
export default {
    name: "ProcessMapTooltip",

}
</script>
<style>
#tooltip {
  position: absolute;
  z-index: 3;
}
.tooltip-title {
  font-weight: bold;
  font-size: 90%;
  margin-bottom: 5px;
}
.tooltip-data-title {
  font-weight: bold;
  padding-right: 5px; 
}
.tooltip-data {
  padding-top: 0px; 
  padding-bottom: 0px; 
  margin-bottom: 5px;
  font-size: 80%;  
}
</style>