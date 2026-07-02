export function registerJadwalTesNilai(Alpine) {
    Alpine.data('jadwalTesNilai', (config = {}) => ({
        toast: { show: false, message: '', type: 'success', timer: null },
        saving: false,
        formData: { ...(config.formData ?? {}) },
        routes: config.routes ?? {},
        reviewId: config.reviewId ?? '',
        init() {},
        showToast(message, type = 'success') {
            if (this.toast.timer) clearTimeout(this.toast.timer);
            Object.assign(this.toast, { message, type, show: true });
            this.toast.timer = setTimeout(() => {
                this.toast.show = false;
            }, 4000);
        },
        clampNilai(event, field) {
            const raw = event.target.value;
            if (raw === '' || raw === '-') return;
            let value = parseInt(raw, 10);
            if (Number.isNaN(value)) {
                event.target.value = '';
                this.formData[field] = '';
                return;
            }
            value = Math.min(100, Math.max(0, value));
            const normalized = String(value);
            if (normalized !== event.target.value) {
                event.target.value = normalized;
            }
            this.formData[field] = normalized;
        },
        get nilaiList() {
            return [
                parseInt(this.formData.nilai_bacaan_al_quran, 10),
                parseInt(this.formData.nilai_tajwid_tahsin, 10),
                parseInt(this.formData.nilai_hafalan, 10),
                parseInt(this.formData.nilai_wawancara, 10),
            ].filter((value) => !Number.isNaN(value));
        },
        get total() {
            return this.nilaiList.length ? this.nilaiList.reduce((sum, value) => sum + value, 0) : null;
        },
        get rataRata() {
            return this.nilaiList.length ? Math.round(this.total / this.nilaiList.length) : null;
        },
        get computedStatus() {
            if (this.nilaiList.length < 4) return null;
            const below71Count = this.nilaiList.filter((value) => value < 71).length;
            if (below71Count === 0) return 'Lulus';
            if (below71Count === 1) return 'Pertimbangan';
            return 'Tidak Lulus';
        },
        async submitNilai() {
            await this.postJson(this.routes.store, 'Nilai tersimpan');
        },
        async simpanHasil() {
            if (this.nilaiList.length < 4) {
                this.showToast('Semua nilai aspek harus diisi terlebih dahulu', 'error');
                return;
            }
            await this.postJson(this.routes.simpanHasil);
        },
        async confirmReview(action) {
            await this.postJson(this.routes.review.replace('__ID__', this.reviewId), 'Status dan nilai berhasil diperbarui', {
                status: action,
            });
        },
        async postJson(url, defaultSuccess = '', extraPayload = {}) {
            this.saving = true;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: this.jsonHeaders(),
                    body: JSON.stringify({
                        id_mahasantri: config.idMahasantri,
                        id_jadwal: config.idJadwal,
                        ...this.formData,
                        ...extraPayload,
                    }),
                });
                const data = await res.json();
                if (res.ok) {
                    this.showToast(data.message || defaultSuccess || 'Berhasil', 'success');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.showToast(data.error || data.message || `Gagal (${res.status})`, 'error');
                }
            } catch (error) {
                this.showToast(`Gagal menyimpan: ${error.message}`, 'error');
            } finally {
                this.saving = false;
            }
        },
        jsonHeaders() {
            return {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                Accept: 'application/json',
            };
        },
    }));
}
