<div id="sidebar-inner" class="closed">
  <ul class="nav flex-column" @click="expanded = !expanded" id="menu-toggle">
    <div>
      <li class="logo">
        <a href="#" >
            <img src={{asset(env('MAIN_LOGO_PATH', '/img/processmaker_logo.png'))}}>
        </a>
      </li>
      <li class="logo-closed" id="menu-toggle">
        <a href="#">
            <img src={{asset(env('ICON_PATH_PATH', '/img/processmaker_icon.png'))}}>
        </a>
      </li>
    </ul>
    <ul class="nav flex-column">
      @foreach($sidebar->topMenu()->items as $section)
        <li class="section">{{ __($section->title) }}</li>
        @foreach($section->children() as $item)
          <li class="nav-item">
            <a href="{{ $item->url() }}" class="nav-link" title="{{ __($item->title) }}">
              @if($item->attr('icon'))
                <i class="fas {{$item->attr('icon')}} nav-icon"></i> <span class="nav-text">{{ __($item->title) }}</span>
              @endif
              @if($item->attr('file'))
                <img src="{{$item->attr('file')}}" class="nav-icon" id="custom_icon"><span class="nav-text">{{ __($item->title) }}</span>
              @endif
            </a>
          </li>
        @endforeach
      @endforeach
    </ul>
</div>
