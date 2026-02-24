<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\TodayScheduleWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('app')
            ->login()
            ->profile(isSimple: false)
            ->favicon(asset('apple-touch-icon.png'))
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('<link rel="manifest" href="/manifest.json"><meta name="theme-color" content="#8b5cf6"><link rel="apple-touch-icon" href="/apple-touch-icon.png"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"><script>if ("serviceWorker" in navigator) { navigator.serviceWorker.getRegistrations().then(function(registrations) { for(let registration of registrations) { if(registration.active && registration.active.scriptURL.includes("?v=")) { registration.unregister(); } } }); window.addEventListener("load", () => { navigator.serviceWorker.register("/sw.js"); }); }</script>')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('<script>
                    let deferredPrompt;
                    window.addEventListener("beforeinstallprompt", (e) => {
                        e.preventDefault();
                        deferredPrompt = e;

                        // Jangan tampilkan tombol install di halaman login
                        if (window.location.pathname.includes("/login")) return;

                        let installBtn = document.getElementById("pwa-install-btn");
                        if(!installBtn) {
                            installBtn = document.createElement("button");
                            installBtn.id = "pwa-install-btn";

                            // Responsive styling
                            function applyStyles() {
                                if (window.innerWidth <= 640) {
                                    installBtn.style.cssText = "position:fixed; bottom:16px; left:16px; right:16px; z-index:40; background:#8b5cf6; color:white; border:none; padding:12px 16px; border-radius:12px; font-weight:bold; box-shadow:0 4px 12px rgba(0,0,0,0.3); cursor:pointer; font-size:14px; text-align:center; width:auto; display:flex; align-items:center; justify-content:center; gap:8px;";
                                } else {
                                    installBtn.style.cssText = "position:fixed; bottom:20px; right:20px; z-index:40; background:#8b5cf6; color:white; border:none; padding:12px 20px; border-radius:50px; font-weight:bold; box-shadow:0 4px 6px rgba(0,0,0,0.3); cursor:pointer; font-size:14px; display:flex; align-items:center; gap:8px;";
                                }
                            }

                            let label = document.createElement("span");
                            label.textContent = "⬇️ Install App Solara";

                            let closeBtn = document.createElement("span");
                            closeBtn.innerHTML = "✕";
                            closeBtn.style.cssText = "margin-left:8px; opacity:0.7; cursor:pointer; font-size:16px; line-height:1;";
                            closeBtn.onclick = (ev) => { ev.stopPropagation(); installBtn.remove(); };

                            installBtn.appendChild(label);
                            installBtn.appendChild(closeBtn);

                            installBtn.onclick = async (ev) => {
                                if (ev.target === closeBtn) return;
                                deferredPrompt.prompt();
                                const { outcome } = await deferredPrompt.userChoice;
                                if (outcome === "accepted") { installBtn.remove(); }
                                deferredPrompt = null;
                            };

                            document.body.appendChild(installBtn);
                            applyStyles();
                            window.addEventListener("resize", applyStyles);
                        }
                    });
                </script>')
            )
            ->registration()
            ->brandName('☀️ Solara')
            ->colors([
                'primary'  => Color::Violet,
                'gray'     => Color::Slate,
                'info'     => Color::Sky,
                'success'  => Color::Emerald,
                'warning'  => Color::Amber,
                'danger'   => Color::Rose,
            ])
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'Akademik',
                'Produktivitas',
                'Keuangan & Goals',
                'Pengaturan',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverviewWidget::class,
                TodayScheduleWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
