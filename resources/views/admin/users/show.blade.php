@extends('layouts.layout', ['title' => 'Users Profile'])

@Section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@Section('content')
    <div class="container mt-4">
      <div class="card card-body">
        <div class="container">
          <div class="row">
            <div class="col-1">
              <img class="avatar-small" src="{{ $user->avatar }}" />
            </div>
            <div class="col-6">
              <div class="d-flex">
                <h3>{{$user->getFullName()}}</h3>
                @if ($user->status == "ACTIVE")
                <i class="fas fa-circle text-success mt-2 ml-1" style="fontsize: 10px !important;"></i>
                @elseif ($user->status == "INACTIVE")
                <i class="fas fa-circle text-danger mt-2 ml-1" style="fontsize: 10px !important;"></i>
                @endif
              </div>
              <h4>{{$user->username}}</h4>
              <h5>{{$user->email}}</h5>
              <br>
              <div>{{$user->address}}</div>
              <div>{{$user->city}}, {{$user->state}}</div>
              <div>{{$user->country}}</div>
              <br>
              <div>Default Time Zone : <span class="font-weight-bold"> {{$user->city}}, {{$user->state}} {{$user->country}} </span></div>
              <div>Language: <span class="font-weight-bold"> {{$user->language}} </span></div>
              <br>
              <div>Created: <span class="font-weight-bold"> {{$user->created_at->format( 'd/m/Y h:m')}} </span></div>
              <div>Updated: <span class="font-weight-bold"> {{$user->updated_at->format( 'd/m/Y h:m' )}} </span></div>
              <div>Last Login: <span class="font-weight-bold"> {{$user->loggedin_at->format('d/m/Y h:m')}} </span></div>
              <br>
            </div>
            <div class="col" style="margin-left: 86px;">
              <button class="btn btn-outline-secondary"> <i class="fas fa-lock"></i> permissions</button>
              <button class="btn btn-secondary"> <i class="fas fa-edit"></i> edit</button>
              <button class="btn btn-secondary"> <i class="fas fa-trash-alt"></i> delete</button>
            </div>
          </div>
          <div class="row mt-5">
            <br>
            <br>
             <div class="col">
               <h3>Groups</h3>
             </div>
             <div class="col form-inline justify-content-end">
               <input type="text" class="form-control w-75" placeholder="&#xf0e0; Search">
               <button type="submit" class="btn btn-secondary ml-2"> <i class="fas fa-plus"></i> Group</button>
             </div>
           </div>
          <table class="table mt-4">
          <thead>
            <tr>
              <th scope="col" class="text-uppercase">Name</th>
              <th scope="col" class="text-uppercase">Description</th>
              <th scope="col"></th>
            </tr>
          </thead>
          <tbody>

            <tr>
              <td>Title</td>
              <td>This is a group description</td>
              <td><i class="fas fa-trash-alt text-secondary"></i></td>
            </tr>

          </tbody>
        </table>
        <div class="text-secondary">1 - 4 of 4 Groups</div>
        </div>
      </div>
    </div>
@endsection

@Section('js')
@endsection