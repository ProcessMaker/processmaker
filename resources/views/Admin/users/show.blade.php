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
              <img class="avatar" src="./avatar-placeholder.png" />
            </div>
            <div class="col-6">
              <div class="d-flex"><h3>Name Name</h3><i class="fas fa-circle text-success mt-2 ml-1" style="fontsize: 10px !important;"></i></div>
              <h4>Username</h4>
              <h5>Email@email.com</h5>
              <div class="mt-4">1234 Street</div>
              <div>City, Town 12345</div>
              <div>Country</div>
              <div class="mt-4">Default Time Zone : <span class="font-weight-bold"> City/State/Country </span></div>
              <div>Language: <span class="font-weight-bold"> Language </span></div>
              <div class="mt-4">Created: <span class="font-weight-bold"> 01/01/0101 01:00 </span></div>
              <div>Updated: <span class="font-weight-bold"> 02/01/0101 02:00 </span></div>
              <div class="mb-4">Last Login: <span class="font-weight-bold"> Right Meow </span></div>
            </div>
            <div class="col" style="margin-left: 86px;">
              <button class="btn btn-outline-secondary"> <i class="fas fa-lock"></i> permissions</button>
              <button class="btn btn-secondary"> <i class="fas fa-edit"></i> edit</button>
              <button class="btn btn-secondary"> <i class="fas fa-trash-alt"></i> delete</button>
            </div>
          </div>
          <div class="row mt-5">
             <div class="col">
               <h3>Groups</h3>
             </div>
             <div class="col-3">
               <input type="text" class="form-control" placeholder="&#xf0e0; Search">
             </div>
               <button type="submit" class="btn btn-secondary mr-3"> <i class="fas fa-plus"></i> Group</button>
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
            <tr>
              <td>Title</td>
              <td>This is a group description</td>
              <td><i class="fas fa-trash-alt text-secondary"></i></td>
            </tr>
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