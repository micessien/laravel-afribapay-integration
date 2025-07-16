<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Payment Status</title>
    <style>
        .invalid-feedback {
            color: #e74c3c;
            font-size: 0.7rem
        }
    </style>
</head>
<body>
    <h1>Callback</h1>
    {{-- Show error --}}
    @if (!empty('error'))
        <p class="invalid-feedback">{{ $error }}</p>
    @endif

    @if (!empty($data))
    <table>
        <tbody>
            <tr><td>Status:</td> <td><em>{{ $data->status }}</em></td></tr>
            <tr><td>Amount:</td> <td><em>{{ $data->amount }} {{ $data->currency }}</em></td></tr>
            <tr><td>Phone Number:</td> <td><em>{{ $data->phone_number }}</em></td></tr>
            <tr><td>Operator:</td> <td><em>{{ $data->operator }}</em></td></tr>
            <tr><td>TTC (fees&taxes):</td> <td><em>{{ $data->fees_taxes_ttc }} {{ $data->currency }}</em></td></tr>
        </tbody>
    </table>
    @endif
</body>
</html>