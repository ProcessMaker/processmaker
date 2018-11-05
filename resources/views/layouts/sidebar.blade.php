<div id="sidebar-inner" class="closed">
  <ul class="nav flex-column" @click="expanded = !expanded" id="menu-toggle">
    <div>
      <li class="logo">
        <a href="#" >
            <img src={{asset(env('MAIN_LOGO_PATH', 'img/processmaker-logo-white-sm.png'))}}>
        </a>
      </li>
      <li class="logo-closed" id="menu-toggle">
        <a href="#">
            <img src="/img/processmaker_icon_logo-md.png">
        </a>
      </li>
    </ul>
      <ul class="nav flex-column">
    @foreach($sidebar->topMenu()->items as $section)
      <li class="section">{{$section->title}}</li>
      @foreach($section->children() as $item)
        <li class="nav-item">
          <a href="{{ $item->url() }}" class="nav-link" title="{{$item->title}}">
                <i class="fas {{$item->attr('icon')}} nav-icon"></i> <span class="nav-text">{{$item->title}}</span>
              </a>
        </li>
        @endforeach
        @endforeach
  </ul>
</div>
