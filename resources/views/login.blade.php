<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body class="position-relative d-flex justify-content-center align-items-center vh-100 overflow-hidden m-0">
    <img src="{{ asset('img_admin/login/Hero image.png') }}" alt="Background Blur" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" style="filter: blur(10px); transform: scale(1.05); z-index: -1;">

    <div class="container" style="z-index: 1;">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card text-white p-4 rounded-4 shadow-lg border border-secondary" style="background-color: rgb(13, 27, 42);">
                    <div class="card-body">
                        <header class="text-center mb-4">
                            <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                                <div class="d-flex justify-content-center align-items-center rounded-circle p-3" style="background-color: #ff7a3d; width: 60px; height: 60px;">
                                    <img src="{{ asset('img_admin/login/Vector.svg') }}" alt="Logo" width="40" height="40">
                                </div>
                            </div>
                            <h4 class="fs-4 fw-bold m-0" style="color: #ff7a3d">Go<span style="color: white;">rental</span></h4>
                            <h2 class="fw-bold mt-3 mb-0" style="color: white;">Login</h2>
                        </header>

                        @if (isset($errors) && $errors->any())
                            <div class="alert alert-danger small py-2" role="alert">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form action="{{ url('admin/login') }}" method="POST" class="mb-3">
                            @csrf
                            <div class="mb-3">
                                <label for="username" class="form-label" style="color: white">Username atau Email</label>
                                <input type="text" class="form-control bg-light text-dark" id="username" name="username" value="{{ old('username') }}" placeholder="username atau Email" autocomplete="username" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label" style="color: white">Password</label>
                                <input type="password" class="form-control bg-light text-dark" id="password" name="password" placeholder="********" minlength="8" autocomplete="current-password" required>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn text-white fw-bold py-2" style="background-color: #ff7a3d;">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>
