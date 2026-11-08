import { ref } from 'vue';

export function useSnap() {
    const isLoading = ref(false);
    const error = ref(null);

    const loadSnapScript = () => {
        return new Promise((resolve, reject) => {
            if (window.snap) {
                resolve(window.snap);
                return;
            }

            const scriptId = 'midtrans-snap-script';
            if (document.getElementById(scriptId)) {
                const interval = setInterval(() => {
                    if (window.snap) {
                        clearInterval(interval);
                        resolve(window.snap);
                    }
                }, 100);
                return;
            }

            const clientKey = import.meta.env.VITE_MIDTRANS_CLIENT_KEY || 'SB-Mid-client-test';
            const script = document.createElement('script');
            script.id = scriptId;
            script.src = import.meta.env.VITE_MIDTRANS_SNAP_URL || 'https://app.sandbox.midtrans.com/snap/snap.js';
            script.setAttribute('data-client-key', clientKey);
            script.onload = () => resolve(window.snap);
            script.onerror = (err) => reject(new Error('Gagal memuat script Midtrans Snap: ' + (err?.message || 'Network error')));
            document.body.appendChild(script);
        });
    };

    const payWithSnap = async (snapToken) => {
        isLoading.value = true;
        error.value = null;

        try {
            await loadSnapScript();

            return new Promise((resolve) => {
                window.snap.pay(snapToken, {
                    onSuccess: (result) => {
                        isLoading.value = false;
                        resolve({ status: 'success', result });
                    },
                    onPending: (result) => {
                        isLoading.value = false;
                        resolve({ status: 'pending', result });
                    },
                    onError: (err) => {
                        isLoading.value = false;
                        error.value = err;
                        resolve({ status: 'error', error: err });
                    },
                    onClose: () => {
                        isLoading.value = false;
                        resolve({ status: 'closed' });
                    },
                });
            });
        } catch (err) {
            isLoading.value = false;
            error.value = err;
            throw err;
        }
    };

    return {
        isLoading,
        error,
        payWithSnap,
        loadSnapScript,
    };
}

