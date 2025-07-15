<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Make payment</title>
    <style>
        .invalid-feedback {
            color: #e74c3c;
            font-size: 0.7rem
        }
    </style>
</head>
<body>
    <h1>Pay Now</h1>
    {{-- Show error --}}
    @if (session()->has('error'))
        <p class="invalid-feedback">{{ session()->get('error') }}</p>
    @endif
    <form action="{{ route('pay.make') }}" method="post">
        @csrf
        <select name="country" required>
            <option value="CI" selected>Cote d'Ivoire</option>
            <option value="BF">Burkina</option>
            <option value="SN">Senegal</option>
        </select>
        @error('country')
            <br>
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror <br><br>

        <select name="operator" required>
            <option value="moov">Moov Money</option>
            <option value="mtn">MTN Money</option>
            <option value="orange">Orange Money</option>
            <option value="wave">Wave</option>
        </select>
        @error('operator')
            <br>
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror <br><br>

        <input type="tel" name="phone" placeholder="Phone Number *" required /> <br><br>
        @error('phone')
            <br>
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror

        <input type="number" name="amount" placeholder="Enter Amount *" required />
        @error('amount')
            <br>
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror <br><br>

        <button type="submit">Pay</button>
    </form>
</body>
</html>