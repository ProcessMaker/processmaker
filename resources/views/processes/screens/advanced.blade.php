<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ $screen->title }}</title>
    <!--Do Not Delete Below -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js"></script>
    <script>
        (function () {
            /** LOAD_PM_VARIABLES **/
            window.ProcessMaker = {
                csrfToken: PM_CSRF_TOKEN,
                submitUrl: PM_SUBMIT_URL,
                requestData: PM_REQUEST_DATA,
                completeTask: PM_FN_COMPLETE_TASK,
            }
        })();
    </script>
    <!--Do Not Delete Above -->
    <script>
         function submitForm(event) {
            event.preventDefault();

            let data =
            {
                status: 'COMPLETED',
                data: Object.fromEntries(FormData(event.target))
            };
            
            let config = 
            {
                headers: {
                    'content-type': 'application/json;charset=UTF-8',
                    'X-Csrf-Token': window.ProcessMaker.csrfToken
                }
            };
            
            axios.put(window.ProcessMaker.submitUrl, data, config);
        }
    </script>
</head>

<body>
    <div class="card" style="width: 18rem;">
        <div class="card-body">
            <h5 class="card-title">Example</h5>
            <p class="card-text">This is an example of an advanced form.</p>
            <form onsubmit="submitForm(event)">
                <div class="form-group">
                    <input class="form-control" type="text" name="first_name" placeholder="First Name">
                </div>
                <div class="form-group">
                    <input class="form-control" type="text" name="last_name" placeholder="Last Name">
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>
</body>

</html>
