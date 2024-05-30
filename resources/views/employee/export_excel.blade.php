<table>
    <thead>
        <tr>
            <th>No.</th>
            <th>First Name</th>
            <th>last name</th>
            <th>Email</th>
            <th>Age</th>
            <th>Position</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($employees as $index => $employee)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $employee->first_name }}</td>
            <td>{{ $employee->last_name }}</td>
            <td>{{ $employee->email }}</td>
            <td>{{ $employee->age }}</td>
            <td>{{ $employee->position->name }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
