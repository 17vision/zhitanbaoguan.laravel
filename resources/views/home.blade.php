@extends('layouts.app')

@vite(['resources/sass/page/home.scss'])

@section('content')
    <div class="container-1">
        <div class="container container-app">
            <div class="d-flex align-items-center header">
                <a class="logo" href="https://zhengnian.17vision.com">
                    <img class="观息空间" src="{{ url('static/image/web/logo.png') }}" />
                </a>
                <span>观息空间</span>
            </div>
            <div class="row no-gutters">
                <div class="col-lg-8 col-md-12 content">
                    <div class="text-dark title">与山川相拥，舒缓心绪</div>

                    <div class="text">
                        睡眠 · 专注 · 冥想 · 呼吸
                    </div>

                    <div class="span">告别喧嚣与浮躁，就从打开观息空间开始</div>
                    <div class="span">这款专注睡眠、冥想与身心放松的应用，将旅行途中的清风、山林间的鸟鸣、海岸边的潮声，与科学的冥想练习融为一体。当你被生活的快节奏裹挟时，不妨在这里暂歇片刻：让自然之声抚平焦虑，用冥想练习沉淀思绪，在专注与平静中，邂逅一夜安稳好眠。</div>

                    <div class="d-flex qr-code">
                        <div class="d-flex flex-column align-items-center qr-code-item">
                            <img src="{{ asset('static/image/home/gxkj_miniapp.jpg?t=1') }}" alt="观息空间小程序">
                            <span>微信小程序</span>
                        </div>


                        <div class="down-wrap">
                            <a id="android" target="_blank"  href="{{ asset('static/apk/android-app.apk?t=1') }}">
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
                <span>专注</span>
            </div>
            <div class="col-6 col-md-3 d-flex flex-column justify-content-center align-items-center">
                <img src="{{ asset('static/image/home/mingxiang.png') }}" alt="冥想">
                <span>冥想</span>
            </div>
            <div class="col-6 col-md-3 d-flex flex-column justify-content-center align-items-center">
                <img src="{{ asset('static/image/home/huxi.png') }}" alt="呼吸">
                <span>呼吸</span>
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
