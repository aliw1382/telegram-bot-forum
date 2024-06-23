<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" href="{{ asset('icon.ico') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/skeleton.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <title>Welcome To Bot Saz</title>

</head>

<body>

<div id="particles-js">
    <div class="container">
        <div class="row top">
            <div class="twelve column">
                <div class="logo">Iracode</div>
                <h1>Bot Saz</h1>
                <h2>Create By Ali Shahmohammadi</h2>
            </div>
        </div>

        <div class="row">
            <div class="one-half column">
                <div class="pens pulled">
                    <h1>Order</h1>
                    <ul>
                        <li>
                            <a href="https://iracode.com/social-network/" target="_blank">Order Telegram Bot Api</a>
                        </li>
                        <li>
                            <a href="tg://resolve?domain=aliw1382" target="_blank">Order Telegram Bot Cli</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="one-half column">
                <div class="posts pulled">
                    <h1>Posts</h1>
                    <ul>
                        <li>
                            <a href="{{ route('help') }}">
                                how to install? <span class="new">Important</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('install') }}">
                                install And config
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="footer">
            <p>
                © All rights reserved and any copy is subject to legal prosecution
            </p>
        </div>
    </div>
</div>


<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/particles.min.js') }}"></script>
<script src="{{ asset('assets/js/script.js') }}"></script>

</body>
</html>
