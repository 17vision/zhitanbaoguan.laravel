@extends('layouts.app')

@vite(['resources/sass/page/home.scss'])

@section('content')
    <div class="container container-app">
        <div class="row no-gutters">
            <div class="col-lg-4 col-md-12 content">
                <div class="text-dark title">观息空间</div>

                <div class="text">
                    压力大、易分心、 睡不好？【观息空间】是 3 -5 分钟上手的正念工具，含减压、助眠、专注功能， 帮你在碎片时间找回平静。
                </div>

                <div class="d-flex qr-code">
                    <div class="d-flex flex-column align-items-center qr-code-item">
                        <img src="{{ asset('static/image/home/gxkj_miniapp.jpg?t=1') }}" alt="观息空间小程序">
                        <span>微信小程序</span>
                    </div>
                </div>

                <div class="down-wrap">
                    <a id="android" onclick="showAlert('开发中，请稍后')">Android 下载</a>
                </div>
            </div>

            <div class="col-lg-8 col-md-12 app-img">
                <img src="{{ asset('static/image/home/gxkj.jpg?t=3') }}" alt="观息空间">
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function(value) {
            alert(value)
        }
    </script>
@endsection
