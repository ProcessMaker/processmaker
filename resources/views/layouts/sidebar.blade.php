<div id="sidebar-inner" :class="{ closed: !expanded, open: expanded }">
  <ul class="nav flex-column" @click="expanded = !expanded">
    <div>
      <li v-if="expanded === true" v-cloak class="logo">
        <a href="#" >
            <img src={{asset(env('MAIN_LOGO_PATH', '/img/processmaker_logo.png'))}}>
        </a>
      </li>
      <li v-else class="logo-closed">
        <a href="#">
            <img src={{asset(env('ICON_PATH_PATH', '/img/processmaker_icon.png'))}}>
        </a>
      </li>
    </ul>
    <ul class="nav flex-column">
      @foreach($sidebar->topMenu()->items as $section)
        <li class="section" v-if="expanded === true" v-cloak>{{$section->title}}</li>
        @foreach($section->children() as $item)
          <li class="nav-item">
            <a href="{{ $item->url() }}" class="nav-link" title="{{$item->title}}">
              @if($item->attr('icon'))
                <i class="fas {{$item->attr('icon')}} nav-icon"></i> <span class="nav-text" v-if="expanded === true" v-cloak>{{$item->title}}</span>
              @endif
              @if($item->attr('file'))
                <img src="{{$item->attr('file')}}" class="nav-icon" id="custom_icon"><span class="nav-text" v-if="expanded === true" v-cloak>{{$item->title}}</span>
              @endif
            </a>
          </li>
        @endforeach
      @endforeach
    </ul>
</div>
