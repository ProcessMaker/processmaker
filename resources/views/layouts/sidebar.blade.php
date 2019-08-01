<div id="sidebar-inner" :class="{ closed: !expanded, open: expanded }">
  <ul class="nav flex-column" @click="expanded = !expanded">
    <div>
      <li v-if="expanded === true" v-cloak class="logo">
        <a href="#" >
          @php
            $logo = \ProcessMaker\Models\Setting::getLogo();
          @endphp
          <img src={{$logo}}>
        </a>
      </li>
      <li v-else v-cloak class="logo-closed">
        <a href="#">
          @php
            $icon = \ProcessMaker\Models\Setting::getIcon();
          @endphp
          <img src={{$icon}}>
        </a>
      </li>
    </ul>
    <ul class="nav flex-column">
      @foreach($sidebar->topMenu()->items as $section)
        <li class="section" v-if="expanded === true" v-cloak>{{$section->title}}</li>
        @foreach($section->children() as $item)
          <sidebaricon :item='@lavaryMenuJson($item)'></sidebaricon>
        @endforeach
      @endforeach
    </ul>
</div>
