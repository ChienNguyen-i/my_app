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
            <th>Url</th>
            <th>Parent_id</th>
            <th>Order</th>
            <th>Icon</th>
        </tr>
        @foreach($menus as $menu)
        <tr>
            <td>{{ $menu->id }}</td>
            <td>{{ $menu->name }}</td>
            <td>{{ $menu->url }}</td>
            <td>{{ $menu->parent_id }}</td>
            <td>{{ $menu->order }}</td>
            <td>{{ $menu->icon }}</td>
        </tr>
        @endforeach
    </table>

</body>

</html>