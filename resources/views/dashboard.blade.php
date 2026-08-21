<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>DANUM Dashboard</title>
</head>

<body>
    <h1>DANUM Dashboard</h1>

    <p>
        You are authenticated.
    </p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button type="submit">
            Logout
        </button>
    </form>
</body>

</html>