<template>
  <div>
    <div
      class="card body-card mb-3 mr-2 ml-2 mt-2"
    >
      <div
        class="card-header d-flex justify-content-between"
      >
        <div class="d-flex">
          <i
            type="icon"
            title="Task"
            class="fas fa-table mr-2 mt-1"
            style="color: rgb(120, 135, 147)"
          />
          <h5>Records</h5>
        </div>
      </div>
      <div
        class="card-body d-flex flex-column"
      >
        <div class="card-info-group">
          <span class="mr-1">Total Records:</span>
          <span class="mb-2 font-bold-500">83</span>
        </div>

        <div class="">
          <ul class="tree ml-4">
            <li class="">
              <div
                class="flex-column d-inline-flex"
              >
                <span>New:
                  <span
                    class="mb-2 font-bold-500"
                  >20</span></span>
              </div>
            </li>
            <li class="">
              <div
                class="flex-column d-inline-flex"
              >
                <div class="">
                  <span>Updated:
                    <span
                      class="mb-2 font-bold-500"
                    >20</span></span>
                </div>
              </div>
            </li>
            <li class="">
              <div
                class="flex-column d-inline-flex"
              >
                <div class="">
                  <span>Unchanged:
                    <span
                      class="mb-2 font-bold-500"
                    >43</span></span>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  components: {},
  mixins: [],
  props: [],
  data() {
    return {

    };
  },
  mounted() {
  },
  methods: {
  },
};
</script>

<style>
ul.tree, ul.tree ul {
  list-style: none;
   margin: 0;
   padding: 0;
 }

 ul.tree ul {
   margin-left: 10px;
 }

 ul.tree li {
   margin: 0;
   padding: 0 7px;
   line-height: 20px;
   border-left:1px solid #768089;
 }

 ul.tree li:last-child {
   border-left:none;
 }

 ul.tree li:before {
    position:relative;
    top:-0.3em;
    height:1.4em;
    width:12px;
    color:white;
    border-bottom:1px solid #768089;
    content:"";
    display:inline-block;
    left:-7px;
 }

 ul.tree li:last-child:before {
    border-left:1px solid #768089;
 }
</style>
