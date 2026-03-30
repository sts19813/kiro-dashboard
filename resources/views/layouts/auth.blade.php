<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <base href="{{ asset('metronic') }}/" />
    <title>@yield('title', config('app.name', 'Kiro Dashboard'))</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico" />

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />

    @stack('styles')
</head>
<body id="kt_body" class="app-blank bgi-size-cover bgi-attachment-fixed bgi-position-center">
    <script>
        var defaultThemeMode = "light";
        var themeMode;

        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else if (localStorage.getItem("data-bs-theme") !== null) {
                themeMode = localStorage.getItem("data-bs-theme");
            } else {
                themeMode = defaultThemeMode;
            }

            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }

            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>

    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <style>
            body { background-image: url('assets/media/auth/bg10.jpeg'); }
            [data-bs-theme="dark"] body { background-image: url('assets/media/auth/bg10-dark.jpeg'); }
        </style>

        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <div class="d-flex flex-lg-row-fluid">
                <div class="d-flex flex-column flex-center pb-0 pb-lg-10 p-10 w-100">
                    <img class="theme-light-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="assets/media/auth/agency.png" alt="Auth Illustration" />
                    <img class="theme-dark-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="assets/media/auth/agency-dark.png" alt="Auth Illustration" />

                    <h1 class="text-gray-800 fs-2qx fw-bold text-center mb-7">Fast, Efficient and Productive</h1>
                    <div class="text-gray-600 fs-base text-center fw-semibold">
                        Administra tu dashboard con una experiencia visual limpia, segura y moderna.
                        <br />Tu autenticacion ahora usa layout Metronic Overlay compartido.
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12">
                <div class="bg-body d-flex flex-column flex-center rounded-4 w-md-600px p-10">
                    <div class="d-flex flex-center flex-column align-items-stretch h-lg-100 w-md-400px">
                        <div class="d-flex flex-center flex-column flex-column-fluid pb-15 pb-lg-20">
                            @yield('auth_content')
                        </div>

                        <div class="d-flex flex-stack">
                            <div class="d-flex fw-semibold text-primary fs-base gap-5">
                                <a href="https://keenthemes.com" target="_blank" rel="noopener">Terms</a>
                                <a href="https://keenthemes.com/metronic" target="_blank" rel="noopener">Plans</a>
                                <a href="https://keenthemes.com/support" target="_blank" rel="noopener">Contact Us</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>var hostUrl = "assets/";</script>
    <script src="assets/plugins/global/plugins.bundle.js"></script>
    <script src="assets/js/scripts.bundle.js"></script>

    @stack('scripts')
</body>
</html>
