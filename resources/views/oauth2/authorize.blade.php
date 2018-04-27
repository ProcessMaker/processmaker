<!DOCTYPE html>
<html>
<head>
    <title>ProcessMaker Oauth2 Server</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Style sheets -->
    <link rel="stylesheet" type="text/css" href="/assets/css/pure-min.css">
    <link rel="stylesheet" href="/assets/css/base-min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/grids-responsive-min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/oauth2.css">
</head>
<body>

<form action="{{$redirect_uri}}" method="post" class="pure-form pure-form-stacked" enctype="application/x-www-form-urlencoded">
    <fieldset>
        <!-- Upper box with Title, username, and password -->
        <div class="upper-box">

            <!-- Title -->
            <div style="padding-top: 15px">
                <img style="display: block; margin-left: auto; margin-right: auto;" src="/images/processmaker.logo.jpg">
                <div class="subtext">Authorization Server</div>
            </div>
            <div class="pure-control-group labels">
                <p>
                <p>{{$user->getFullName()}}</p>
                The application <b>{{$client->name}}</b> is requesting access to your account.

                <h4>{{$client->NAME}}</h4>
                <small><i>{{$client->description}}</i></small>
                </p>

                <p>Do you approve?</p>
            </div>
        </div>
        <!-- Bottom box with Sign in -->
        <div class="bottom-box">
            <div class="pure-controls accept-cancel-buttons">
                <div>
                    <input class="pure-button pure-button-primary" type="submit" name="approve" value="Accept" id="allow">
                    <!-- Cheat to make a bit of spacing -->
                    <span>&nbsp&nbsp</span>
                    <input class="pure-button" type="submit" value="Deny" name="cancel" id="deny">
                </div>
            </div>
        </div>
    </fieldset>

</form>
</body>
</html>