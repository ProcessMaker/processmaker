<div class="sidebarmenu fixed-top sidebarmenuicons bg-primary" id="sidebarMenu" v-on:mouseover="sidebarShown" v-on:mouseout="sidebarHidden">
        <img class="sidebarlogo" v-if="isSeen" v-bind:src="logo"><img class="sidebaricon" v-else v-bind:src="icon">
    <ul class="l-0 list-unstyled position-fixed text-light" id="sidebarscroll">
          @foreach($main->whereParent(null) as $section)
        <li class="sidebarheader" v-show="isSeen"><small>{{$section->title}}</small></li>
          @foreach($main->whereParent($section->id) as $child)
          <a href="{{ url($child->link->path['route']) }}">
            <li>
              <i class="fas {{$child->attr('icon')}} fa-fw"></i> <span v-show="isSeen">{{$child->title}}</span>
            </li>
          </a>
        @endforeach
      @endforeach
    </ul>
</div>
@section('js')
<script>
  new Vue({
    el: '#sidebarMenu',
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
