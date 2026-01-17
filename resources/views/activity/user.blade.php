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

<body>
    <div class="d-flex flex-column align-items-center content">
        <img src="{{ asset('static/image/activity/title.png') }}" class="top-title" />

        <form action="{{ route('store.activity.user') }}" method="POST">
            <div class="form-box">
                @csrf

                <span class="title">填写信息</span>

                <div class="form-item">
                    <label for="username" class="form-label">姓名:</label>

                    <input type="text" class="form-input" id="username" name="username" placeholder="请输入姓名"
                        value="{{ old('username') }}" required>
                </div>

                <div class="form-item">
                    <label for="username" class="form-label">性别:</label>

                    <div class="form-input">
                        <div class="form-check-radio">
                            <input class="form-check-input" type="radio" name="gender" id="male" value="1"
                                @checked(old('gender') == '1') required>
                            <label class="form-check-label" for="male">男</label>
                        </div>
                        <div class="form-check-radio">
                            <input class="form-check-input" type="radio" name="gender" id="female" value="2"
                                @checked(old('gender') == '2')>
                            <label class="form-check-label" for="female">女</label>
                        </div>
                    </div>
                </div>

                <div class="form-item">
                    <label for="age" class="form-label">年龄:</label>

                    <input type="number" min="1" max="150" class="form-input" id="age" name="age"
                        placeholder="请输入年龄" value="{{ old('age') }}" required>
                </div>

                <div class="form-item">
                    <label for="phone" class="form-label">手机号码:</label>

                    <input type="tel" pattern="^1[3-9]\d{9}$" class="form-input" id="phone" name="phone"
                        placeholder="请输入手机号码" name="phone" value="{{ old('phone') }}" required>
                </div>

                <button type="submit" class="submit-btn">提交</button>
            </div>
        </form>
    </div>

    @if (session('success'))
    <div class="message-box">
        <div class="success-box">
            <img src="{{ asset('static/image/activity/success-1.png') }}" class="success-icon" />

            <span class="success-txt">提交成功啦</span>

            {{-- <a href="{{ url('/') }}" class="success-btn">返回首页</a> --}}
        </div>
    </div>
    @endif

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
    html {
        font-family: "Helvetica Neue", Helvetica, "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", "微软雅黑", Arial, sans-serif;
    }

    input[type="number"] {
        -moz-appearance: textfield;
    }

    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .content {
        background: url('/static/image/activity/activity-user-bg.jpg');
        background-position: center center;
        background-repeat: no-repeat;
        background-size: cover;
        /* aspect-ratio: 749 / 1333 width: 100%; */
        min-height: 100vh;

        .top-title {
            width: 7.49rem;
            height: 3.03rem;
            margin-top: 0.76rem;
            margin-bottom: 0.61rem;
        }

        .form-box {
            background: url('/static/image/activity/form-bg.png');
            width: 6.5rem;
            height: 8.28rem;
            background-size: 6.5rem 8.28rem;
            margin-bottom: 0.5rem;

            .title {
                display: inline-block;
                height: 1.1rem;
                line-height: 1.1rem;
                font-size: 0.36rem;
                color: #005030;
                margin-left: 0.4rem;
                margin-bottom: 0.12rem;
            }

            .form-item {
                display: flex;
                flex-direction: column;
                margin-left: 0.4rem;
                margin-bottom: 0.3rem;

                .form-label {
                    color: #4F4F4F;
                    font-size: 0.28rem;
                    margin-bottom: 0.1rem;
                }

                .form-input {
                    display: flex;
                    align-items: center;
                    background-color: #80929A95;
                    width: 5.7rem;
                    height: 0.68rem;
                    padding: 0 0.22rem;
                    border-radius: 0.1rem;
                    font-size: 0.24rem;
                    border: none;
                    outline: none;
                    -webkit-appearance: none;
                    /* Safari/Chrome 去除默认样式 */
                    -moz-appearance: none;
                    /* Firefox 去除默认样式 */
                    appearance: none;
                    color: #ffffff;

                    &::placeholder {
                        color: #ffffffaa;
                    }

                    &::-webkit-input-placeholder {
                        color: #ffffffaa;
                    }

                    .form-check-radio {
                        display: flex;
                        align-items: center;
                        margin-right: 0.47rem;
        
                        .form-check-input {
                            -webkit-appearance: none;
                            -moz-appearance: none;
                            appearance: none;
                            margin-top: 0;
                            margin-right: 0.1rem;
                            background-size: initial;


                            &:checked {
                                background-color: #3E997B;
                                border-color: #3E997B;
                            }
                        }
                    }
                }
            }

            .submit-btn {
                width: 5.71rem;
                height: 0.68rem;
                border-radius: 0.48rem;
                background: #3E997B;
                border: none;
                color: #ffffff;
                font-size: 0.32rem;
                margin-left: 0.39rem;
            }
        }
    }

    .message-box {
        display: flex;
        justify-content: center;
        align-items: center;
        position: fixed;
        left: 0;
        top: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;

        .success-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: linear-gradient(to bottom, #CBEFE7FF, #F8FFFBFF);
            width: 5.75rem;
            height: 4.75rem;
            border-radius: 0.2rem;

            .success-icon {
                width: 4.38rem;
                height: 2.78rem;
                margin-top: 0.49rem;
            }

            .success-txt {
                font-weight: 500;
                font-size: 0.32rem;
                color: #318066;
            }

            .success-btn {
                width: 2.85rem;
                height: 0.68rem;
                border-radius: 0.48rem;
                line-height: 0.68rem;
                font-size: 0.32rem;
                text-align: center;
                color: #fff;
                background: #3E997BFF;
                margin-top: 1rem;
            }
        }
    }
</style>

</html>
