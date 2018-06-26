<template>
<div id="designer-toolbar">
<nav class="navbar navbar-expand-md override">
  <span>Process Name</span>

<div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
    <ul class="navbar-nav">
      <li class="nav-item">
    <a href="#" title="Full Screen"><i class="fas fa-arrows-alt"></i></a>
  </li>
  <li class="nav-item ">
    <a href="#" title="Undo"><i class="fas fa-undo"></i></a>
  </li>
  <li class="nav-item ">
    <a href="#" title="Redo"><i class="fas fa-redo"></i></a>
  </li>
  <li class="nav-item ">
    <a href="#" title="Zoom In"><i class="fas fa-search-plus"></i></a>
  </li>
  <li class="nav-item ">
    <a href="#" title="Zoom In"><i class="fas fa-search-minus"></i></a>
  </li>
  <li class="nav-item ">
    <a href="#" title="Save Process"><i class="fas fa-save"></i></a>
  </li>
   <li class="nav-item ">
    <a href="#" title="Upload BPMN"><i class="fas fa-upload"></i></a>
  </li>
  <li class="nav-item ">
    <a href="#" title="Help"><i class="fas fa-info"></i></a>
  </li>
  <li class="nav-item ">
    <a href="#" title="Close Designer"><i class="fas fa-times"></i></a>
  </li>
    </ul>

  </div>

</nav>

</div>

 
</template>

<style lang='scss' scoped>

#designer-toolbar .override {
  background-color: #b6bfc6;
  padding: 10px;
  height:40px;
  font-family: "Open Sans";
  font-size: 18px;
  font-weight: 600;
  font-style: normal;
  font-stretch: normal;
  line-height: normal;
  letter-spacing: normal;
  text-align: left;
  color: #ffffff;
}

#designer-toolbar a {
  color: white;
  padding-right: 15px;
  
}
</style>
