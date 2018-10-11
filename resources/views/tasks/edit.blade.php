@extends('layouts.layout')

@section('title')
  {{__('Edit Task')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('content')
    <nav>
        <ul class="nav row" style="height:40px; background-color:#b6bfc6;">
            <li class="nav-item col-3 align-self-center">
                <a class="nav-link active text-light p-0 ml-3" href="{{route('tasks.index')}}">
                    <i class="fas fa-long-arrow-alt-left fa-lg mr-2"></i>
                    BACK TO TASK LIST
                </a>
            </li>
            <li class="nav-item col-3 align-self-center text-right">
                <a class="nav-link text-light p-0" href="#">
                    <h4 class="m-0">Task: {{$task->element_name}}</h4>
                </a>
            </li>
            <li class="nav-item col align-self-center">
                <a class="nav-link p-0" href="#" >
                    <span class="pill badge-light p-1 pl-2 pr-2" style="border-radius: 20px;">
                        <i class="fas fa-circle text-primary mr-2"></i>
                        <span>{{ucfirst(strtolower($task->status))}}</span>
                    </span>
                </a>
            </li>
        </ul>
    </nav>
    <div id="task-detail"></div>    
@endsection

@section('js')
    <script src="{{mix('js/tasks/show.js')}}"></script>
@endsection

@section('css')
    <style lang="scss" scoped>

        .taskNav {
            height:40px;
            background-color:#b6bfc6;
        }

        .pill {
            border-radius: 20px;
        }

    </style>
@endsection