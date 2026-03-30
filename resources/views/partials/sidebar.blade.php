<!--begin::Sidebar-->
<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="250px"
    data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
    <!--begin::Wrapper-->
    <div id="kt_app_sidebar_wrapper" class="app-sidebar-wrapper">
        <div class="hover-scroll-y my-5 my-lg-2 mx-4" data-kt-scroll="true"
            data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-height="auto"
            data-kt-scroll-dependencies="#kt_app_header" data-kt-scroll-wrappers="#kt_app_sidebar_wrapper"
            data-kt-scroll-offset="5px">
            <!--begin::Sidebar menu-->
            <div id="kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false"
                class="app-sidebar-menu-primary menu menu-column menu-rounded menu-sub-indention menu-state-bullet-primary px-3 mb-5">
                <div class="menu-item">
                    <a href="{{ route('dashboard') }}"
                        class="menu-link {{ request()->is('dashboard*') ? 'active' : '' }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-home-2 fs-2"></i>
                        </span>
                        <span class="menu-title">Dashboard</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="javascript:void(0);" class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-outline ki-chart-line fs-2"></i>
                        </span>
                        <span class="menu-title">Insights de Turistas</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="javascript:void(0);" class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-outline ki-location fs-2"></i>
                        </span>
                        <span class="menu-title">Ubicaciones</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="javascript:void(0);" class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-outline ki-building fs-2"></i>
                        </span>
                        <span class="menu-title">Proveedores</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="javascript:void(0);" class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-outline ki-user fs-2"></i>
                        </span>
                        <span class="menu-title">Perfiles</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="javascript:void(0);" class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-outline ki-briefcase fs-2"></i>
                        </span>
                        <span class="menu-title">Convocatorias</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="javascript:void(0);" class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-outline ki-graph fs-2"></i>
                        </span>
                        <span class="menu-title">Análisis</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="{{ route('dashboard') }}"
                        class="menu-link {{ request()->is('dashboard*') ? 'active' : '' }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-document fs-2"></i>
                        </span>
                        <span class="menu-title">Reportes</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="{{ route('settings.agent') }}" class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-outline ki-setting-3 fs-2"></i>
                        </span>
                        <span class="menu-title">Configuración del agente</span>
                    </a>
                </div>

                <div class="menu-item">
                    <a href="{{ route('settings.users') }}" class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-outline ki-profile-user fs-2"></i>
                        </span>
                        <span class="menu-title">Administración de usuarios</span>
                    </a>
                </div>
            </div>
            <!--end::Sidebar menu-->
        </div>
    </div>
    <!--end::Wrapper-->
</div>
<!--end::Sidebar-->