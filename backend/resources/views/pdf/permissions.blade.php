<!DOCTYPE html>
<html>

<head>
    <title>Laravel 11 Generate PDF Example</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>

<body>
    <h1>{{ $title }}</h1>
    <p>{{ $date }}</p>
    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
        tempor incididunt ut labore et dolore magna aliqua.</p>

    <table class="table table-bordered">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Key_code</th>
            <th>Parent_id</th>
            <th>Order</th>
        </tr>
        @foreach($oermissions as $oermission)
        <tr>
            <td>{{ $oermission->id }}</td>
            <td>{{ $oermission->name }}</td>
            <td>{{ $oermission->key_code }}</td>
            <td>{{ $oermission->parent_id }}</td>
            <td>{{ $oermission->order }}</td>
        </tr>
        @endforeach
    </table>

</body>

</html>