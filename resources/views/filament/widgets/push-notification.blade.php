<x-filament-widgets::widget>
    <x-filament::section id="push-notification-widget" style="display: none;">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-indigo-500 text-white flex items-center justify-center" style="width: 40px; height: 40px;">
                    <x-filament::icon icon="heroicon-o-bell" class="w-6 h-6 text-white" style="width: 24px; height: 24px;" />
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-base">Aktifkan Notifikasi Jadwal & Tugas</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Izinkan browser untuk menampilkan pop-up notifikasi.</p>
                </div>
            </div>
            
            <div>
                <button id="enable-push-btn" type="button"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-lg shadow-md focus:outline-none transition-colors"
                    onclick="requestPushPermission()">
                    Izinkan
                </button>
            </div>
        </div>
    </x-filament::section>

    <script>
        function urlBase64ToUint8Array(base64String) {
            var padding = '='.repeat((4 - base64String.length % 4) % 4);
            var base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
            var rawData = window.atob(base64);
            var outputArray = new Uint8Array(rawData.length);
            for (var i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        async function requestPushPermission() {
            const btn = document.getElementById('enable-push-btn');
            btn.innerHTML = 'Memproses...';
            btn.disabled = true;

            try {
                // Memicu native browser prompt DENGAN AKTIVITAS PENGGUNA (User Gesture / Click)
                const permission = await Notification.requestPermission();
                
                if (permission === 'granted') {
                    await subscribeToServer();
                    // Sembunyikan widget kalau sudah sukses
                    document.getElementById('push-notification-widget').style.display = 'none';
                } else {
                    btn.innerHTML = 'Ditolak Browser';
                    btn.style.backgroundColor = 'rgb(220 38 38)'; // bg-red-600
                }
            } catch (error) {
                console.error(error);
                btn.innerHTML = 'Izinkan';
                btn.disabled = false;
            }
        }

        async function subscribeToServer() {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                
                const vapidPublicKey = '{{ $this->getVapidPublicKey() }}';
                const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey);

                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedVapidKey
                });

                await fetch('/webpush', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(subscription)
                });
                
            } catch (error) {
                console.error('Subscription failed: ', error);
            }
        }

        // Cek status saat load. Kalau belum 'granted' / 'denied' (masih 'default'), tampilkan widget tombolnya.
        document.addEventListener('DOMContentLoaded', () => {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                return; // Not supported
            }

            if (Notification.permission === 'default') {
                document.getElementById('push-notification-widget').style.display = 'block';
            } else if (Notification.permission === 'granted') {
                // Pastikan disinkronisasi ulang ke server di background walaupun disembunyikan
                subscribeToServer();
            }
        });
    </script>
</x-filament-widgets::widget>
