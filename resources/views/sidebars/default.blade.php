<div class="sidebarmenu bg-light fixed-top mpt-0" id="root" v-on:mouseover="sidebarShown" v-on:mouseout="sidebarHidden">
    <ul style="height:100%;" class="sidebarmenuicons l-0 list-unstyled position-fixed bg-primary text-light " id="sidebarMenu">
        <img v-if="isSeen" v-bind:src="logo"><img v-else v-bind:src="icon">
          @foreach($main->whereParent(null) as $section)
        <li class="sidebarheader" v-show="isSeen"><small>{{$section->title}}</small></li>
          @foreach($main->whereParent($section->id) as $child)
          <li>
            <a href="{{ url($child->link->path['route']) }}">
            <i class="fas {{$child->attr('icon')}}"></i> <span v-show="isSeen">{{$child->title}}</span>
            </a>
          </li>
        @endforeach
      @endforeach
    </ul>
</div>
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
          $("#sidebarMenu").removeClass('sidebarmenuicons')
      },
      sidebarHidden: function(){
        this.isSeen = false
          $("#sidebarMenu").addClass('sidebarmenuicons')
      }
    }
  })
</script>
@endsection
