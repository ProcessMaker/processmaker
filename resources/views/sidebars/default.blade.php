<div id="sidebar-wrapper">
    <ul class="sidebar-nav">
        <li class="sidebar-brand">
      <a class="logo" href="/home">
        <img src="/img/processmaker-logo-white-sm.png" alt="">
    </a>
  </li>

        @foreach($sidebar->topMenu()->items as $section)
          <li class="section">{{$section->title}}</li>
          @foreach($section->children() as $item)
            <li href="#">
              <a  href="{{ $item->url() }}">
                <i class="fas {{$item->attr('icon')}}"></i> <span class="nav-text">{{$item->title}}</span>
              </a>
            </li>
          @endforeach
        @endforeach
    </ul>
  </div>
