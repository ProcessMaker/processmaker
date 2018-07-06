<div class="sidebarmenu fixed-top bg-primary" v-bind:class="{sidebarmenuicons: !expanded}" id="sidebarMenu" v-on:mouseover="toggleVisibility" v-on:mouseout="toggleVisibility">
  <img class="sidebarlogo" v-if="expanded" v-bind:src="logo"><img class="sidebaricon" v-else v-bind:src="icon">
  <ul class="l-0 list-unstyled position-fixed text-light" id="sidebarscroll">
    @foreach($sidebar->items as $row)
      @if(array_key_exists('route',$row->link->path))
        <li class="d-flex">
          <a class="d-flex" href="{{ route($row->link->path['route']) }}">
            <i class="fas {{$row->attr('icon')}} fa-fw"></i> <span v-if="expanded">{{$row->title}}</span>
          </a>
        </li>
        @else
        <li class="sidebarheader" v-if="expanded"><small>{{$row->title}}</small></li>
        @endif
        @endforeach
  </ul>
</div>
