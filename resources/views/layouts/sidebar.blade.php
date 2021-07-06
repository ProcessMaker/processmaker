<nav aria-label="Sidebar" id="sidebar-inner" class="d-flex h-100 align-items-start flex-column" :class="{ closed: !expanded, open: expanded }">
  <ul class="mb-auto w-100">
      <li v-if="expanded === true" v-cloak class="logo">
      <a href="/" aria-label="{{ config('logo-alt-text', 'ProcessMaker') }}">
          @php
            $logo = \ProcessMaker\Models\Setting::getLogo();
          @endphp
          <img src={{$logo}} alt="{{ config('logo-alt-text', 'ProcessMaker') }}">
        </a>
      </li>
      <li v-else v-cloak class="logo-closed">
      <a href="/" aria-label="{{ config('logo-alt-text', 'ProcessMaker') }}">
          @php
            $icon = \ProcessMaker\Models\Setting::getIcon();
          @endphp
          <img src={{$icon}} alt="{{ config('logo-alt-text', 'ProcessMaker') }}">
        </a>
      </li>
      @if ($sidebar)
        @foreach($sidebar->topMenu()->items as $section)
          <li class="section" v-if="expanded === true" aria-label="{{$section->title}}" v-cloak>{{$section->title}}</li>
          @foreach($section->children() as $item)
            <sidebaricon :item='@lavaryMenuJson($item)'></sidebaricon>
          @endforeach
        @endforeach
      @endif
    </ul>
  <div class="w-100" v-cloak>
    <div
      v-if="expanded"
      @click="expanded = !expanded"
      role="button"
      :aria-label="$t('Collapse sidebar')"
      class="nav-item filter-bar justify-content-between py-2 sidebar-expansion"
    >
      <div class="nav-link">
        <i class="fas fa-angle-double-left nav-icon" v-if="expanded"></i>
        <i class="fas fa-angle-double-right nav-icon" v-else></i>
        <span class="nav-text" v-if="expanded" v-cloak >
          {{ __('Collapse sidebar') }}
        </span>
</div>
    </div>
    <div
      v-else
      @click="expanded = !expanded"
      role="button"
      :aria-label="$t('Expand sidebar')"
      class="nav-item filter-bar justify-content-between py-2 sidebar-expansion"
      v-b-tooltip.hover.right="{ title: $t('Expand sidebar'), animation: false, boundary: 'viewport', delay: { show: 0, hide: 0 } }"
    >
      <div class="nav-link">
        <i class="fas fa-angle-double-right nav-icon"></i>
      </div>
    </div>
  </div>
</nav>
