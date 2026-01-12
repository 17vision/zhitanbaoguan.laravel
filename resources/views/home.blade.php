@extends('layouts.app')

@vite(['resources/sass/page/home.scss'])

@section('content')
    <div class="container-1">
        <div class="container container-app">
            <div class="row no-gutters">
                <div class="col-lg-8 col-md-12 content">
                    <div class="text-dark title">与山川相拥，舒缓心绪</div>

                    <div class="text">
                        睡眠 · 专注 · 冥想 · 呼吸
                    </div>

                    <div class="d-flex qr-code">
                        <div class="d-flex flex-column align-items-center qr-code-item">
                            <img src="{{ asset('static/image/home/gxkj_miniapp.jpg?t=1') }}" alt="观息空间小程序">
                            <span>微信小程序</span>
                        </div>


                        <div class="down-wrap">
                            <a id="android" onclick="showAlert('开发中，请稍后')">
                                <img src="{{ asset('static/image/home/android-btn.png') }}" alt="观息空间小程序">
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-12 app-img">
                    <img class="phone-img" src="{{ asset('static/image/home/i1.png') }}" alt="观息空间">
                </div>
            </div>
        </div>
    </div>


    <div class="container container-app container-2">
        <div class="row no-gutters">
            <div class="col-6 col-md-3 d-flex flex-column justify-content-center align-items-center">
                <img src="{{ asset('static/image/home/shuimian.png') }}" alt="睡眠">
                <span>睡眠</span>
            </div>
            <div class="col-6 col-md-3 d-flex flex-column justify-content-center align-items-center">
                <img src="{{ asset('static/image/home/zhuanzhu.png') }}" alt="专注">
                <span>睡眠</span>
            </div>
            <div class="col-6 col-md-3 d-flex flex-column justify-content-center align-items-center">
                <img src="{{ asset('static/image/home/mingxiang.png') }}" alt="冥想">
                <span>睡眠</span>
            </div>
            <div class="col-6 col-md-3 d-flex flex-column justify-content-center align-items-center">
                <img src="{{ asset('static/image/home/huxi.png') }}" alt="呼吸">
                <span>睡眠</span>
            </div>
        </div>
    </div>

    <div class="container-3">
        <div class="container container-app">
            <div class="row">
                <div class="col-lg-5 col-md-12 left-img">
                    <img class="phone-img" src="{{ asset('static/image/home/i2.png') }}" alt="观息空间">
                </div>
    
                <div class="col-lg-7 col-md-12 offset-lg-5 d-flex flex-column">
                    <span class="title-1">告别焦虑内耗，提升专注力</span>
                    <span class="title-1">轻松掌握正念技巧</span>
                    <span class="title-2">保持正念，凡事往好处理，无论遇到何处处境，你都能创造奇迹，用自己的方式改变命运。</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-4">
        <div class="container container-app">
            <div class="row">
                <div class="col-lg-7 col-md-12 d-flex flex-column">
                    <span class="title-1">告别辗转难眠！</span>
                    <span class="title-1">和万物之声相拥，正念小憩也香甜。</span>
                    <span class="title-2">高清助眠声音陪你深夜安睡，独创轻唤醒功能唤你晨间好梦。</span>
                </div>
    
                <div class="col-lg-5 col-md-12 right-img">
                    <img class="phone-img" src="{{ asset('static/image/home/i4.png') }}" alt="观息空间">
                </div>
            </div>
        </div>
    </div>

    <div class="container-5">
        <div class="container container-app">
            <div class="col-lg-5 col-md-12 left-img">
                <img  class="phone-img" src="{{ asset('static/image/home/i3.png') }}" alt="观息空间">
            </div>

            <div class="col-lg-7 col-md-12 offset-lg-5 d-flex flex-column">
                <span class="title-1">开始今日训练</span>
                <span class="title-1">和万物声音一起放松身心 </span>
                <span class="title-2">训练结束后，记得设置轻唤醒保证，清晨温柔起床</span>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function showAlert(value) {
            alert(value)
        }
    </script>
@endsection
