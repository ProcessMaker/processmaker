<div class="sidebarmenu fixed-top bg-primary" v-bind:class="{sidebarmenuicons: !expanded}" id="sidebarMenu" v-on:mouseover="toggleVisibility" v-on:mouseout="toggleVisibility">
  <img class="sidebarlogo" v-if="expanded" v-bind:src="logo"><img class="sidebaricon" v-else v-bind:src="icon">
  <ul class="l-0 list-unstyled position-fixed text-light" id="sidebarscroll">
    @foreach($sidebar->topMenu()->items as $section)
      {{-- The first level are the sections --}}
      <li class="sidebarheader" v-if="expanded"><small>{{$section->title}}</small></li>
      @foreach($section->children() as $item)
        <li>
          <a href="{{ $item->url() }}">
            <i class="fas {{$item->attr('icon')}} fa-fw"></i> <span v-if="expanded">{{$item->title}}</span>
          </a>
        </li>
      @endforeach
    @endforeach
  </ul>
</div>
