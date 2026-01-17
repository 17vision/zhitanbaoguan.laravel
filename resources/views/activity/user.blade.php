<!doctype html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <title>观息空间</title>

    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" mce_href="{{ asset('favicon.ico') }}" type="image/x-icon" />

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body class="container-fluid">
    <div class="row">
        <div class="col-12 col-lg-6 position-lg-absolute" style="min-height: 100vh;"></div>
        <div class="col-12 col-lg-6 position-lg-absolute" style="min-height: 100vh;">
            <div class="d-flex flex-column content">
                <span class="title">欢迎使用观息空间</span>

                @if(session('success'))
                    <div class="alert alert-success mt-4">
                        {{ session('success') }}
                    </div>

                    <a href="{{ url('/') }}" class="btn btn-secondary mt-3 w-100">返回首页</a>
                @else
                    <form action="{{ route('store.activity.user') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="username" class="form-label">姓名</label>

                            <input type="text" class="form-control @error('username') is-invalid @enderror"
                                id="username" name="username" placeholder="请输入姓名" value="{{ old('username') }}" required>

                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>


                        <div class="mb-4">
                            <label for="age" class="form-label">性别</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="male"
                                        value="1" @checked(old('gender') == '1') required>
                                    <label class="form-check-label" for="male">男</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="female"
                                        value="2" @checked(old('gender') == '2')>
                                    <label class="form-check-label" for="female">女</label>
                                </div>
                            </div>

                            @error('gender')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="age" class="form-label">年龄</label>

                            <input type="number" min="1" max="150" class="form-control @error('age') is-invalid @enderror" id="age"
                                placeholder="请输入年龄" name="age" value="{{ old('age') }}" required>

                            @error('age')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="phone" class="form-label">手机号码</label>

                            <input type="tel" pattern="^1[3-9]\d{9}$"
                                class="form-control @error('phone') is-invalid @enderror" id="phone"
                                placeholder="请输入手机号码" name="phone" value="{{ old('phone') }}" required>

                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-2">提交</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</body>
<style lang="scss" scoped>
    .content {
        font-family: "Helvetica Neue", Helvetica, "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", "微软雅黑", Arial, sans-serif;
        padding: 20px;

        .title {
            display: flex;
            color: #0e0f0f;
            line-height: normal;
            font-style: normal;
            font-size: 32px;
            font-weight: 500;
            text-align: center;
            font-family: PP Editorial New Regular;
            margin-bottom: 60px;
        }
    }


    @media (max-width: 991.98px) {
        .position-lg-absolute {
            position: absolute;
            left: 0;
            top: 0;
            z-index: 1;
        }
    }
</style>

</html>
