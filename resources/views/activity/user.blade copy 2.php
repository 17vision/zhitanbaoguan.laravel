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

<body class="d-flex flex-column align-items-center content">
    <img src="{{ asset('static/image/activity/title-1.png') }}" class="title-1" />
    <img src="{{ asset('static/image/activity/title-2.png') }}" class="title-2" />

    {{-- @if (session('success'))
        <div class="alert alert-success mt-4">
            {{ session('success') }}
        </div>

        <a href="{{ url('/') }}" class="btn btn-secondary mt-3 w-100">返回首页</a>
    @else --}}
    <div class="form-box">
        <form action="{{ route('store.activity.user') }}" method="POST">
            @csrf
    
            <div class="mb-4">
                <label for="username" class="form-label">姓名</label>
    
                <input type="text" class="form-control @error('username') is-invalid @enderror" id="username"
                    name="username" placeholder="请输入姓名" value="{{ old('username') }}" required>
    
                @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
    
            <div class="mb-4">
                <label for="age" class="form-label">性别</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="gender" id="male" value="1"
                            @checked(old('gender') == '1') required>
                        <label class="form-check-label" for="male">男</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="gender" id="female" value="2"
                            @checked(old('gender') == '2')>
                        <label class="form-check-label" for="female">女</label>
                    </div>
                </div>
    
                @error('gender')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>
    
            <div class="mb-4">
                <label for="age" class="form-label">年龄</label>
    
                <input type="number" min="1" max="150" class="form-control @error('age') is-invalid @enderror"
                    id="age" placeholder="请输入年龄" name="age" value="{{ old('age') }}" required>
    
                @error('age')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
    
            <div class="mb-4">
                <label for="phone" class="form-label">手机号码</label>
    
                <input type="tel" pattern="^1[3-9]\d{9}$" class="form-control @error('phone') is-invalid @enderror"
                    id="phone" placeholder="请输入手机号码" name="phone" value="{{ old('phone') }}" required>
    
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
    
            <button type="submit" class="btn btn-primary w-100 mt-2">提交</button>
        </form>
    </div>

    <script>
        (function() {
            const designWidth = 750; // 设计稿宽度
            const baseRem = 100; // 设计稿中 100px = 1rem

            function setRootFontSize() {
                const deviceWidth = document.documentElement.clientWidth || window.innerWidth;
                // 限制最大宽度（可选，防止在 iPad/PC 上过大）
                const maxWidth = 750;
                const effectiveWidth = Math.min(deviceWidth, maxWidth);
                const fontSize = (effectiveWidth / designWidth) * baseRem;
                document.documentElement.style.fontSize = fontSize + 'px';
            }

            setRootFontSize();
            window.addEventListener('resize', setRootFontSize);
            window.addEventListener('orientationchange', setRootFontSize);
        })();
    </script>
</body>
<style lang="scss" scoped>
    .content {
        font-family: "Helvetica Neue", Helvetica, "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", "微软雅黑", Arial, sans-serif;
        background: url({{ asset('static/image/activity/activity-user-bg.jpg') }});
        background-position: center center;
        background-repeat: no-repeat;
        background-size: cover;
        overflow: hidden;

        .title-1 {
            width: 7.97rem;
            height: 2.44rem;
            margin-top: 0.76rem;
        }

        .title-2 {
            width: 2.83rem;
            height: 0.69rem;
            margin-top: 0.1rem;
        }

        .form-box {
            background: url({{asset('static/image/activity/form-bg.png')}});
            width: 6.5rem;
            height: 8.28rem;
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
