<div class="bg-light fixed-top mpt-0" id="root" v-on:mouseover="sidebarShown" v-on:mouseout="sidebarHidden">
  <!-- <img v-if="isSeen" v-bind:src="logo"><img v-else v-bind:src="icon"> -->
  <div class="row p-2">
    <ul style="height:100%;" class="l-0 p-4 list-unstyled position-fixed bg-primary text-light " id="sidebarMenu">
      @foreach($main->whereParent(null) as $section)
        <li class="h5 font-weight-light" v-show="isSeen">{{$section->title}}</li>
        @foreach($main->whereParent($section->id) as $child)
          <li><i class="fa icon {{$child->attr('icon')}}"></i> <span v-show="isSeen">{{$child->title}}</span></li>
        @endforeach
      @endforeach
    </ul>
  </div>
</div>


@section('css')
<style>
 .sidebarmenu li{
   height: 36px;
   margin:10px 0;
 }
 .sidebarmenuicons li{
   height: 36px;
   margin:25px 0;
 }
</style>
@endsection

@section('js')
<script>
  new Vue({
    el: '#root',
    data:{
      isSeen:true,
      icon:'img/logo.png',
      logo:'img/processmaker_logo_white.jpg'
    },
    methods:{
      sidebarShown: function(){
        this.isSeen = true
          $("#sidebarMenu").removeClass('sidebarmenuicons').addClass('sidebarmenu')
      },
      sidebarHidden: function(){
        this.isSeen = false
          $("#sidebarMenu").removeClass('sidebarmenu').addClass('sidebarmenuicons')
      }
    }
  })
</script>
@endsection
