<div class="sidebarHidden bg-light fixed-top mpt-0" id="root" v-on:mouseover="sidebarShown" v-on:mouseout="sidebarHidden">
    <ul style="height:100%;" class="l-0 p-3 list-unstyled position-fixed bg-primary text-light " id="sidebarMenu">
        <img v-if="isSeen" v-bind:src="logo"><img v-else v-bind:src="icon">
          @foreach($main->whereParent(null) as $section)
        <li class="mt-3 text-uppercase text-muted font-weight-light" v-show="isSeen"><small>{{$section->title}}</small></li>
          @foreach($main->whereParent($section->id) as $child)
          <li><i class="fas {{$child->attr('icon')}}"></i> <span class="p-1 text-capitalize" v-show="isSeen">{{$child->title}}</span></li>
        @endforeach
      @endforeach
    </ul>
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
      isSeen:false,
      icon:'img/processmaker-icon-white-sm.png',
      logo:'img/processmaker-logo-white-sm.png'
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
