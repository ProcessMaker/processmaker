@extends('layouts.layout')

@section('title')
  {{__('User Profile')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <div class="container mt-4">
      <div class="card card-body">
        <div class="container">
          <div class="row">
            <div class="col-2 text-center">
              @if ($user->getAvatar() != '' )
                <img class="avatar-lg-circle" src="{{ $user->getAvatar() }}" />
              @else
                <div class="avatar-lg-circle bg-warning">
                <span class="avatar-lg-initials">{{ $user->firstname[0] }}{{ $user->lastname[0] }}</span>
                </div>
              @endif
            </div>
            <div class="col-5">
              <div class="d-flex">
                <h3>{{$user->getFullName()}} </h3>
                @if ($user->status == "ACTIVE")
                <i class="fas fa-circle text-success fa-sm mb-2 ml-1 align-self-center"></i>
                @elseif ($user->status == "INACTIVE")
                <i class="fas fa-circle text-danger fa-sm mb-2 ml-1 align-self-center"></i>
                @endif
              </div>
              <div class="font-weight-bold mb-0">{{$user->username}}</div>
              <div >{{$user->email}}</div>
              <br>
              <div>{{$user->address}}</div>
              <div>{{$user->city}}, {{$user->state}} {{$user->postal}}</div>
              <div>{{$user->country}}</div>
              <div>{{$user->phone}}</div>
              <br>
              <div>Default Time Zone: <span class="font-weight-bold"> {{$user->timezone}} </span></div>
              @if ($user->language == "us_en")
              <div>Language: <span class="font-weight-bold"> English </span></div>
              @else
              <div>Language: <span class="font-weight-bold"> {{$user->language}} </span></div>
              @endif
              <br>
              <div>Created: <span class="font-weight-bold"> {{$user->created_at->format( 'm/j/Y g:i A')}} </span></div>
              <div>Updated: <span class="font-weight-bold"> {{$user->updated_at->format( 'm/j/Y g:i A' )}} </span></div>
              @if ($user->loggedin_at != null)
              <div>Last Login: <span class="font-weight-bold"> {{$user->loggedin_at->format('m/j/Y g:i A')}} </span></div>
              @else
              
              @endif
              <br>
            </div>
            <div class="col" align="right">
              <button class="btn btn-outline-secondary"> <i class="fas fa-lock"></i> permissions</button>
              <button class="btn btn-secondary"> <i class="fas fa-edit"></i> edit</button>
              <button class="btn btn-secondary"> <i class="fas fa-trash-alt"></i> delete</button>
            </div>
          </div>
          <br>
          <br>
          <div class="row">
            <div class="col align-self-center">
              <h3 class="m-0">Groups</h3>
            </div>
            <div class="col form-inline input-group justify-content-end">
              <div class="input-group-prepend">
                <span class="input-group-text" id="prepend-search"><i class="fa fa-search" aria-hidden="true"></i></span>
              </div>
              <input type="text" class="form-control w-50 border-left-0" placeholder="Search">
              <button type="submit" class="btn btn-secondary ml-2"> <i class="fas fa-plus"></i> Group</button>
            </div>
           </div>
          @if ($user->groupMembersFromMemberable()->count() > 0)
            <table class="table table-hover vuetable">
              <thead>
                <tr>
                  <th scope="col" class="text-uppercase">Full Name</th>
                  <th scope="col" class="text-uppercase">Description</th>
                  <th scope="col"></th>
                </tr>
              </thead>
              <tbody>
                @foreach ($user->groupMembersFromMemberable() as $group_member)
                <tr>
                  <td>{{$group_member->group->name}}</td>
                  <td>{{$group_member->group->status}}</td>
                  <td class="text-right"><i class="fas fa-trash-alt text-secondary"></i></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          @else
            <br>
            <div class="card card-body text-center font-weight-bold">User is not a member of any groups</div>
          @endif
        </div>
      </div>
    </div>
@endsection

@section('js')
@endsection
