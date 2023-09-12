<div class="flex-grow-1">
  <div id="navbarMobile">
    <nav class="navbar navbar-light bg-primary d-print-none">
      @php
        $loginLogo = \ProcessMaker\Models\Setting::getLogin();
      @endphp
      <a href="#" class="navbar-brand pl-2"><img alt= "Login logo" class="navbar-logo" src={{$loginLogo}}></a>
      <div class="content-nav">
        <ul class="nav justify-content-end">
          <li class="nav-item">
            <a class="nav-link">
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
          <li class="nav-item">
            <a class="nav-link">
              <button
                type="buttom"
                class="btn btn-outline-light"
              >
                <i class="fa fa-bell"></i>
              </button>
            </a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
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
    <!-- Nav tabs -->
    <div>
      <ul class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
        <li class="nav-item" role="presentation" role="presentation">
          <a class="nav-link" data-toggle="tab" href="/tasks" role="tab" aria-selected="false">{{ __('Tasks') }}</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" data-toggle="tab" href="/requests" role="tab" aria-selected="true">{{ __('Requests') }}</a>
        </li>
      </ul>
    </div>
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
    list-style-type:none;
    background-color: #EFF5FF;
    flex-direction: row !important;
  }
  .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active {
    color: black;
    background-color: transparent;
    border-color: transparent transparent #f3f3f3;
    border-bottom: 3px solid #1572C2 !important;
    font-size: 16px;
    font-weight: 600;
  }
  .nav-tabs a{
    color: #333;
  }
</style>
