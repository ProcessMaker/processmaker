<div class="sidebarmenu fixed-top bg-primary" v-bind:class="{sidebarmenuicons: !expanded}" id="sidebarMenu" v-on:mouseover="toggleVisibility" v-on:mouseout="toggleVisibility">
  <img class="sidebarlogo" v-if="expanded" v-bind:src="logo"><img class="sidebaricon" v-else v-bind:src="icon">
    <ul class="l-0 list-unstyled position-fixed text-light" id="sidebarscroll">
          @foreach($task->whereParent(null) as $section)
        <li class="sidebarheader" v-if="expanded"><small>{{$section->title}}</small></li>
          @foreach($task->whereParent($section->id) as $child)
          <a href="{{ url($child->link->path['route']) }}">
            <li>
              <i class="fas {{$child->attr('icon')}} fa-fw"></i> <span v-if="expanded">{{$child->title}}</span>
            </li>
          </a>
        @endforeach
      @endforeach
    </ul>
</div>
@section('js')
@parent
  <script>
  new Vue({
    el: '#sidebarMenu',
    data:{
      expanded:false,
      icon:'img/processmaker-icon-white-sm.png',
      logo:'img/processmaker-logo-white-sm.png'
    },
    methods:{
      toggleVisibility() {
        this.expanded = !this.expanded;
      }
    }
  })
</script>
@endsection
