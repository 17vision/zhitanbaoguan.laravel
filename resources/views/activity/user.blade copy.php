<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>用户信息</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" mce_href="{{ asset('favicon.ico') }}" type="image/x-icon" />

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">用户信息录入</h5>
                    </div>
                    <div class="card-body">
                        {{-- 错误提示 --}}
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('store.activity.user') }}" method="POST">
                            @csrf

                            {{-- 姓名 --}}
                            <div class="mb-3">
                                <label for="name" class="form-label">用户姓名</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- 年龄 --}}
                            <div class="mb-3">
                                <label for="age" class="form-label">年龄</label>
                                <input type="number" min="1" max="150"
                                       class="form-control @error('age') is-invalid @enderror"
                                       id="age" name="age" value="{{ old('age') }}" required>
                                @error('age')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- 性别 --}}
                            <div class="mb-3">
                                <label class="form-label">性别</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="gender" id="male" value="男"
                                               @checked(old('gender')=='男') required>
                                        <label class="form-check-label" for="male">男</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="gender" id="female" value="女"
                                               @checked(old('gender')=='女')>
                                        <label class="form-check-label" for="female">女</label>
                                    </div>
                                </div>
                                @error('gender')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- 手机号 --}}
                            <div class="mb-3">
                                <label for="phone" class="form-label">手机号码</label>
                                <input type="tel" pattern="^1[3-9]\d{9}$"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" value="{{ old('phone') }}" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary w-100">提交</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS .bundle 含 Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>