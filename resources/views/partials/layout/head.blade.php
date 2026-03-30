<!--begin::Head-->
<head>
    <meta charset="utf-8" />
    <title>@yield('title', config('app.name', 'Kiro Dashboard'))</title>
    <meta name="description" content="@yield('meta_description', 'Dashboard base layout with Metronic.')" />
    <meta name="keywords" content="metronic, dashboard, laravel" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="@yield('title', config('app.name', 'Kiro Dashboard'))" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:site_name" content="{{ config('app.name', 'Kiro Dashboard') }}" />
    <link rel="shortcut icon" href="{{ asset('metronic/assets/media/logos/favicon.ico') }}" />
    <base href="{{ asset('metronic') }}/" />

    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->

    <!--begin::Vendor Stylesheets(used for this page only)-->
    <link href="assets/plugins/custom/fullcalendar/fullcalendar.bundle.css" rel="stylesheet" type="text/css" />
    <link href="assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css" />
    <!--end::Vendor Stylesheets-->

    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->

    @stack('styles')

    <script>
        // Frame-busting to prevent click-jacking.
        if (window.top != window.self) {
            window.top.location.replace(window.self.location.href);
        }
    </script>
</head>
<!--end::Head-->
