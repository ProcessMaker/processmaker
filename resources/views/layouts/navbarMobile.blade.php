@php
  $showPrincipalNavbar = 1;

  if (
    Request::path() !== 'tasks' &&
    Request::path() !== 'requests' &&
    Request::path() !== 'cases' &&
    !str_starts_with(Request::path(), 'process-browser')
  ) {
    $showPrincipalNavbar = 0;
  }
@endphp
<div>
  <div id="navbarMobile" v-if="display" v-cloak>
    @if($showPrincipalNavbar)
      <nav class="navbar navbar-light bg-primary d-print-none">
        @php
          $loginLogo = \ProcessMaker\Models\Setting::getLogo();
        @endphp
        <a href="#" class="navbar-brand pl-2"><img alt= "Login logo" class="navbar-logo" src={{$loginLogo}}></a>
        <div class="content-nav">
          <ul class="nav justify-content-end">
            <li class="nav-item">
              <a class="nav-link px-0">
                @if (shouldShow('requestButton'))
                  <component 
                    v-bind:is="'request-modal-mobile'" 
                    url="{{ route('processes.index') }}" 
                    v-bind:permission="{{ \Auth::user()->hasPermissionsFor('processes') }}"
                  >
                  </component>
                @endif
              </a>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle py-2 px-3" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                <button
                  type="buttom"
                  class="dropleft btn btn-outline-light"
                >
                  <i class="fa fa-user"></i>
                </button>
              </a>
              <div class="dropdown-menu dropdown-menu-right mr-3 mt-2 p-2">
                <a 
                  class="dropdown-item"
                  @click="switchToDesktop()"
                >
                  {{ __('Switch to Desktop View') }}
                </a>
                <div class="dropdown-divider"></div>
                <a 
                  class="dropdown-item" 
                  href="/logout"
                >
                  {{ __('Log Out') }}
                </a>
              </div>
            </li>
          </ul>
        </div>
      </nav>
      @php
        $menuItems = [
          [
            'name' => __('Tasks'),
            'url' => route('tasks.index'),
            'isActive' => Request::path() === 'tasks',
          ],
          [
            'name' => __('Cases'),
            'url' => route('cases.index'),
            'isActive' => Request::path() === 'cases',
          ],
          [
            'name' => __('Processes'),
            'url' => route('process.browser.index'),
            'isActive' => Request::path() === 'process-browser',
          ],
        ];
      @endphp
      <!-- Nav tabs -->
      <div>
        <b-navbar-nav class="mobile-nav-tabs nav-tabs" id="nav-tab" role="tablist">
          <template v-for="(item, index) in {{ json_encode($menuItems) }}">
            <b-nav-item 
              class="mobile-nav-item nav-item nav-link p-0"
              role="presentation"
              :href="item.url"
              :active="item.isActive"
              :key="index"
            >
              <span v-html="item.name"></span>
            </b-nav-item>
          </template>
        </b-navbar-nav >
      </div>
    @endif
    <welcome-modal username="{{ \Auth::user()->fullname }}"/>
  </div>
</div>

<style>
  .navbar {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    padding: 10 0 10 0;
  }
  .dropdown-toggle::after {
    display:none;
  }
  .nav-tabs {
    list-style-type: none;
    background-color: #EFF5FF;
    flex-direction: row;
  }
  .mobile-nav-tabs {
    background-color: #FFFFFF;
  }
  .nav-tabs .nav-link{
    border: none !important;
    margin-bottom: 0;
    justify-content: center;
  }
  .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active {
    color: black;
    background-color: transparent;
    border-color: transparent transparent #f3f3f3;
    border-bottom: 3px solid #1572C2;
    font-size: 16px;
    font-weight: 600;
  }
  .nav-tabs a{
    color: #333;
  }
</style>
