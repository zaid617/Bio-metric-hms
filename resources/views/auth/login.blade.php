@extends('layouts.guest')
@section('title', 'Login')

@section('content')
<div class="auth-wrapper d-flex min-vh-100">

    <!-- Left Branding Section -->
    <div class="col-lg-7 d-none d-lg-flex align-items-center justify-content-center auth-left">
        <div class="text-center px-5">
            <img src="{{ URL::asset('build/images/auth/login1.png') }}" class="img-fluid mb-4" width="520" alt="">
            <h2 class="fw-bold text-white">Welcome Back 👋</h2>
            <p class="text-white-50 mt-2">Securely access your dashboard and manage everything in one place.</p>
        </div>
    </div>

    <!-- Right Login Card -->
    <div class="col-lg-5 d-flex align-items-center justify-content-center bg-light">
        <div class="auth-card shadow-lg rounded-4 p-4 p-md-5 w-100 mx-3">

            <div class="text-center mb-4">
                <img src="{{ URL::asset('build/images/logo1.png') }}" width="140" class="mb-3" alt="">
                <h3 class="fw-bold">Sign In</h3>
                <p class="text-muted">Enter your credentials to continue</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="row g-3">
                @csrf

                <div class="col-12">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control form-control-lg" placeholder="john@example.com" value="{{ old('email') }}" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Password</label>
                    <div class="input-group input-group-lg" id="show_hide_password">
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        <span class="input-group-text bg-white"><i class="bi bi-eye-slash-fill"></i></span>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Login As</label>
                    <select class="form-select form-select-lg" name="role" required>
                        <option value="" disabled selected>Select Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="remember">
                    <label class="form-check-label">Remember Me</label>
                </div>

                <div class="col-md-6 text-end mt-2">
                    <a href="{{ route('password.request') }}" class="text-decoration-none">Forgot Password?</a>
                </div>

                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">Login</button>
                </div>

                <div class="col-12 text-center mt-3">
                    <p class="mb-0">Don’t have an account? <a href="{{ route('register') }}" class="fw-semibold">Create Account</a></p>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

@push('style')
<style>
.auth-left{
    background: linear-gradient(135deg, #0d6efd, #6610f2);
}
.auth-card{
    max-width: 420px;
    background: #fff;
}
.form-control, .form-select{
    border-radius: 12px;
}
</style>
@endpush

@push('script')
<script>
$(document).ready(function(){
    $('#show_hide_password span').on('click', function(){
        let input = $('#show_hide_password input');
        let icon = $('#show_hide_password i');
        if(input.attr('type') === 'password'){
            input.attr('type','text');
            icon.removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
        }else{
            input.attr('type','password');
            icon.removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
        }
    });
});
</script>
@endpush
