<div class="container h-100" id="connectedAccounts">
  <div class="card card-body h-100 border-0">
    <h4 class="mt-2 pb-3 page-title">{{__('Connected Accounts')}}</h4>
    <div v-if="accounts.length === 0">{{__('You currently don\'t have any connected accounts enabled.')}} </div>
    <ul v-else class="accounts-list w-100 pl-0">
      <li class="accounts-list-item d-flex align-items-start py-3 mt-3" v-for="(account, index) in accounts" :key="index" >
        <div class="d-flex align-items-start mr-3">
          <img :src="account.icon" :alt="account.name + 'icon'" width="45px"/>
        </div>
        <div class="d-flex flex-column flex-grow-1">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
              <h5 class="account-name mb-0">@{{account.name}}</h5>
              <p class="account-description mb-0">@{{account.description}}</p>
            </div>
              <div class="d-flex align-items-center">
              <button class="edit-btn" @click="showAccountsModal()">{{__('Edit')}}</button>
              <b-badge pill variant="success" class="ml-3 connection-status">
                <i class="fa fa-check"></i>
                {{__('Connected')}}
              </b-badge>
            </div>
          </div>
        </div>
      </li>
    </ul>
  </div>
</div>

@section('css')
  <style scoped>
    .page-title {
      color: #556271;
      font-size: 21px;
    }

    .accounts-list-item {
      border-bottom: 1px solid #C4C8CC;
    }

    .account-name {
      font-size: 18px;
      font-weight: 600;
    }

    .account-description {
      font-size: 14px;
      font-weight: 400;
      color: #6C757D;
    }

    .edit-btn {
      border: none;
      background-color: #FFFFFF;
      color: #6C757D;
      font-size: 14px;
    }

    .connection-status {
      border-radius: 8px;
      font-size: 16px;
      font-weight: 400;
      padding: 0.75rem;
    }
  </style>
@endsection
