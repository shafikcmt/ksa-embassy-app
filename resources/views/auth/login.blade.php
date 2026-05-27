<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — KSA Embassy File Agency</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .login-card {
            width: 100%; max-width: 420px;
            background: #fff; border-radius: 12px;
            box-shadow: 0 25px 50px rgba(0,0,0,.35);
            overflow: hidden;
        }
        .login-header {
            background: #0f172a; padding: 2rem 2rem 1.5rem; text-align: center;
        }
        .login-header .logo-icon {
            width: 56px; height: 56px; background: #1e40af;
            border-radius: 12px; display: inline-flex; align-items: center;
            justify-content: center; margin-bottom: .75rem;
        }
        .login-header h1 { color: #fff; font-size: 1.1rem; font-weight: 700; margin: 0; }
        .login-header p  { color: #94a3b8; font-size: .8rem; margin: .25rem 0 0; }
        .login-body { padding: 1.75rem 2rem 2rem; }
        .form-control { border-radius: 8px; }
        .btn-login {
            border-radius: 8px; padding: .65rem; font-weight: 600;
            background: #1e40af; border: none;
        }
        .btn-login:hover { background: #1d4ed8; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <div class="logo-icon">
            <i class="bi bi-building text-white fs-4"></i>
        </div>
        <h1>KSA Embassy File Agency</h1>
        <p>Management System — Secure Login</p>
    </div>
    <div class="login-body">
        @if(session('status'))
            <div class="alert alert-info py-2 mb-3">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger py-2 mb-3">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold" for="email">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input id="email" type="email" name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" required autofocus autocomplete="username"
                        placeholder="you@example.com">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input id="password" type="password" name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        required autocomplete="current-password" placeholder="••••••••">
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                    <label class="form-check-label text-muted small" for="remember_me">Remember me</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-login w-100">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
            </button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
