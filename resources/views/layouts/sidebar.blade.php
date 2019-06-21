<div id="sidebar-inner" :class="{ closed: !expanded, open: expanded }">
  <ul class="nav flex-column" @click="expanded = !expanded">
    <div>
      <li v-if="expanded === true" v-cloak class="logo">
        <a href="#" >
            <img src={{asset(env('MAIN_LOGO_PATH', '/img/processmaker_logo.png'))}}>
        </a>
      </li>
      <li v-else v-cloak class="logo-closed">
        <a href="#">
            <img src={{asset(env('ICON_PATH_PATH', '/img/processmaker_icon.png'))}}>
        </a>
      </li>
    </ul>
    <ul class="nav flex-column">
      @foreach($sidebar->topMenu()->items as $section)
        <li class="section" v-if="expanded === true" v-cloak>{{$section->title}}</li>
        @foreach($section->children() as $item)
          <sidebaricon :item='@json($item)'></sidebaricon>
        @endforeach
      @endforeach
    </ul>
</div>