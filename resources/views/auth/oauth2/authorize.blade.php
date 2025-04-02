<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }} - Authorization</title>

    <!-- Styles -->
    <link href="{{ asset('/css/app.css') }}" rel="stylesheet">

    <style>
        .passport-authorize .container {
            margin-top: 30px;
        }

        .passport-authorize .scopes {
            margin-top: 20px;
        }

        .passport-authorize .buttons {
            margin-top: 25px;
            text-align: center;
        }

        .passport-authorize .btn {
            width: 125px;
        }

        .passport-authorize .btn-approve {
            margin-right: 15px;
        }

        .passport-authorize form {
            display: inline;
        }

        .devlink-container {
            text-align: center;
            padding: 40px 20px;
            width: 467px;
        }

        .devlink-icon {
            width: 150px;
            margin-bottom: 20px;
        }

        .devlink-title {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .devlink-description {
            color: #525252;
            margin-bottom: 30px;
            font-size: 16px;
            font-weight: 400;
        }

        .devlink-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            
            margin: 0 auto;
        }

        .btn-authorize {
            background-color: #4284F3;
            color: white;
            padding: 10px;
            border-radius: 5px;
            border: none;
            width: 100%;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-logout {
            background-color: white;
            color: #333;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            width: 100%;
            font-size: 14px;
            font-weight: 500;
        }
    </style>
</head>
<body class="passport-authorize">
    @if($client->name === 'devlink')
        <div class="container d-flex justify-content-center">
            <div class="devlink-container">
                <img src="/img/devlink-request.svg" alt="DevLink Icon" class="devlink-icon">
                <h1 class="devlink-title">DevLink Request</h1>
                <p class="devlink-description">We have found a request to connect your environment to another instance of the platform.</p>
                
                <div class="devlink-buttons">
                    <!-- Authorize Button -->
                    <form method="post" action="{{ route('passport.authorizations.approve') }}">
                        @csrf
                        <input type="hidden" name="state" value="{{ $request->state }}">
                        <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        <button type="submit" class="btn-authorize">Authorize</button>
                    </form>

                    <!-- Logout Button -->
                    <form method="post" action="{{ route('passport.authorizations.deny') }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="state" value="{{ $request->state }}">
                        <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        <button class="btn-logout">Logout From The Active Session</button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card card-default">
                        <div class="card-header">
                            Authorization Request
                        </div>
                        <div class="card-body">
                            <!-- Introduction -->
                            <p><strong>{{ $client->name }}</strong> is requesting permission to access your account.</p>

                            <!-- Scope List -->
                            @if (count($scopes) > 0)
                                <div class="scopes">
                                        <p><strong>This application will be able to:</strong></p>

                                        <ul>
                                            @foreach ($scopes as $scope)
                                                <li>{{ $scope->description }}</li>
                                            @endforeach
                                        </ul>
                                </div>
                            @endif

                            <div class="buttons">
                                <!-- Authorize Button -->
                                <form method="post" action="{{ route('passport.authorizations.approve') }}">
                                    @csrf

                                    <input type="hidden" name="state" value="{{ $request->state }}">
                                    <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                                    <input type="hidden" name="auth_token" value="{{ $authToken }}">
                                    <button type="submit" class="btn btn-success btn-approve">Authorize</button>
                                </form>

                                <!-- Cancel Button -->
                                <form method="post" action="{{ route('passport.authorizations.deny') }}">
                                    @csrf
                                    @method('DELETE')

                                    <input type="hidden" name="state" value="{{ $request->state }}">
                                    <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                                    <input type="hidden" name="auth_token" value="{{ $authToken }}">
                                    <button class="btn btn-danger">Cancel</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</body>
</html>
