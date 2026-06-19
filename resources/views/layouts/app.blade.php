<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
	<meta charset="utf-8" />
	<title>@yield('title', 'Dashboard')</title>

	<!-- Meta -->
	<meta name="description" content="Panel administrativo de Kiro Dashboard." />
	<meta name="keywords" content="dashboard, administración, kiro" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta property="og:locale" content="es_MX" />
	<meta property="og:type" content="article" />
	<meta property="og:title" content="Kiro Dashboard" />
	<meta property="og:url" content="{{ config('app.url') }}" />
	<meta property="og:site_name" content="Kiro Dashboard" />
	<link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.ico') }}" />
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

	<!-- Fonts -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />

	<!-- Vendor Stylesheets (para páginas específicas, opcional) -->
	<link href="{{ asset('metronic/assets/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet"
		type="text/css" />
	<link href="{{ asset('metronic/assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet"
		type="text/css" />

	<!-- Global Stylesheets Bundle (obligatorios) -->
	<link href="{{ asset('metronic/assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
	<link href="{{ asset('metronic/assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />

	<script>
		// Prevención de clickjacking
		if (window.top != window.self) {
			window.top.location.replace(window.self.location.href);
		}
	</script>

	<style>
		:root {
			--bs-primary: #7723FF;
			--bs-primary-active: #5E1BCC;
			--bs-primary-light: #F2EBFF;
		}

		/* Botón Ver registro */
		.btn-light-primary,
		.btn-view-registration {
			background-color: #7723FF !important;
			color: #ffffff !important;
			border-color: #7723FF !important;
		}

		.btn-light-primary:hover,
		.btn-view-registration:hover {
			background-color: #5f1ccc !important;
			border-color: #5f1ccc !important;
			color: #ffffff !important;
		}

		/* Badge jugadores */
		.badge-light-primary {
			background-color: #7723FF !important;
			color: #ffffff !important;
		}
	</style>

</head>

<body id="kt_app_body" data-kt-app-header-fixed="true" data-kt-app-header-fixed-mobile="true"
	data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
	data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-aside-enabled="false"
	data-kt-app-aside-fixed="false" data-kt-app-aside-push-toolbar="false" data-kt-app-aside-push-footer="false"
	class="app-default">


	<!--begin::Theme mode setup on page load-->
	<script>
		var defaultThemeMode = "light";
		var themeMode;
		if (document.documentElement) {
			if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
				themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
			} else {
				if (localStorage.getItem("data-bs-theme") !== null) {
					themeMode = localStorage.getItem("data-bs-theme");
				} else {
					themeMode = defaultThemeMode;
				}
			}
			if (themeMode === "system") {
				themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
			}
			document.documentElement.setAttribute("data-bs-theme", themeMode);
		}
	</script>
	<!--end::Theme mode setup on page load-->
	<!--begin::App-->

	<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
		<!--begin::Page-->
		<div class="app-page flex-column flex-column-fluid" id="kt_app_page">
			<!-- Header -->
			@include('partials.header')


			<div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
				<!--begin::Sidebar-->
				@include('partials.sidebar')
				<!--end::Sidebar-->
				<!--begin::Main-->
				<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
					<!--begin::Content wrapper-->
					<div class="d-flex flex-column flex-column-fluid">
						<!--begin::Content-->
						<div id="kt_app_content" class="app-content flex-column-fluid">
							<!--begin::Content container-->
							<div id="kt_app_content_container" class="app-container container-fluid">

								@yield('content')

								<!--end::Row-->
							</div>
							<!--end::Content container-->
						</div>
						<!--end::Content-->
					</div>
					<!--end::Content wrapper-->
					<!--begin::Footer-->
					@include('partials.footer')
					<!--end::Footer-->
				</div>
			</div>
		</div>
		<!--end::Wrapper-->
	</div>
	<!--begin::Javascript-->
	<script>var hostUrl = "{{ asset('metronic/assets') }}/";</script>

	<!--begin::Global Javascript Bundle (obligatorio para todas las páginas)-->
	<script src="{{ asset('metronic/assets/plugins/global/plugins.bundle.js') }}"></script>
	<script src="{{ asset('metronic/assets/js/scripts.bundle.js') }}"></script>
	<!--end::Global Javascript Bundle-->

	<!--begin::Vendors Javascript (usados solo en algunas páginas)-->
	<script src="{{ asset('metronic/assets/plugins/custom/fullcalendar/fullcalendar.bundle.js') }}"></script>
	<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
	<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
	<script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
	<script src="https://cdn.amcharts.com/lib/5/radar.js"></script>
	<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
	<script src="https://cdn.amcharts.com/lib/5/map.js"></script>
	<script src="https://cdn.amcharts.com/lib/5/geodata/worldLow.js"></script>
	<script src="https://cdn.amcharts.com/lib/5/geodata/continentsLow.js"></script>
	<script src="https://cdn.amcharts.com/lib/5/geodata/usaLow.js"></script>
	<script src="https://cdn.amcharts.com/lib/5/geodata/worldTimeZonesLow.js"></script>
	<script src="https://cdn.amcharts.com/lib/5/geodata/worldTimeZoneAreasLow.js"></script>
	<script src="{{ asset('metronic/assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
	<!--end::Vendors Javascript-->

	<!--begin::Custom Javascript (usados solo en algunas páginas)-->
	<script src="{{ asset('metronic/assets/js/widgets.bundle.js') }}"></script>
	<script src="{{ asset('metronic/assets/js/custom/widgets.js') }}"></script>
	<script src="{{ asset('metronic/assets/js/custom/apps/chat/chat.js') }}"></script>
	<script src="{{ asset('metronic/assets/js/custom/utilities/modals/upgrade-plan.js') }}"></script>
	<script src="{{ asset('metronic/assets/js/custom/utilities/modals/users-search.js') }}"></script>
	<!--end::Custom Javascript-->


	<!-- Load Pickr -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" />
	<script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr"></script>

	<!-- Select2 -->
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			if (!window.Swal) {
				return;
			}

			var Toast = Swal.mixin({
				toast: true,
				position: 'top-end',
				showConfirmButton: false,
				timer: 4200,
				timerProgressBar: true,
				customClass: {
					popup: 'shadow-sm',
				},
			});

			@if (session('status'))
				Toast.fire({
					icon: 'success',
					title: @json(session('status')),
				});
			@endif

			@if ($errors->any())
				Toast.fire({
					icon: 'error',
					title: @json($errors->first()),
				});
			@endif
		});
	</script>

	<!-- Para cargar scripts adicionales desde otras vistas -->
	@stack('scripts')
	<!--end::Javascript-->
</body>

</html>
