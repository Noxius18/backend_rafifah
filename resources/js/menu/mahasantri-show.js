export function registerMahasantriShow(Alpine) {
    Alpine.data('mahasantriShow', (config = {}) => ({
        toast: { show: false, message: '', type: 'success', timer: null },
        previewDocsSource: config.previewDocs ?? [],
        retryUrlTemplate: config.retryUrlTemplate ?? '',
        updateUrlTemplate: config.updateUrlTemplate ?? '',
        deleteAction: config.deleteAction ?? '',
        deleteName: config.deleteName ?? '',
        previewDocs: [],
        previewDocIndex: 0,
        saving: false,
        previewNik: '',
        previewNisn: '',
        init() {
            this.previewDocs = this.previewDocsSource.map((doc) => ({ ...doc }));
        },
        showToast(message, type = 'success') {
            if (this.toast.timer) clearTimeout(this.toast.timer);
            Object.assign(this.toast, { message, type, show: true });
            this.toast.timer = setTimeout(() => {
                this.toast.show = false;
            }, 4000);
        },
        get previewDoc() {
            return this.previewDocs[this.previewDocIndex] || {};
        },
        get previewUrl() {
            return this.previewDoc.url || '';
        },
        get previewTitle() {
            return this.previewDoc.title || '';
        },
        get previewTotal() {
            return this.previewDocs.length;
        },
        get isImageDoc() {
            return this.previewDoc.isImage || false;
        },
        openPreview(index, nik, nisn) {
            this.previewDocs = this.previewDocsSource.map((doc) => ({ ...doc }));
            this.previewDocIndex = index;
            this.previewNik = nik || '';
            this.previewNisn = nisn || '';
            document.getElementById('previewModal')?.showModal();
        },
        prevDoc() {
            if (this.previewDocIndex > 0) this.previewDocIndex -= 1;
        },
        nextDoc() {
            if (this.previewDocIndex < this.previewDocs.length - 1) this.previewDocIndex += 1;
        },
        openDeleteModal() {
            document.getElementById('deleteModal-name').textContent = this.deleteName;
            document.getElementById('deleteModal-form').action = this.deleteAction;
            document.getElementById('deleteModal').showModal();
        },
        async retryDownload(berkasId) {
            const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
            try {
                const res = await fetch(this.retryUrlTemplate.replace('__ID__', berkasId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        Accept: 'application/json',
                    },
                });
                const data = await res.json();
                if (res.ok) {
                    this.showToast(data.message || 'Proses unduh ulang dimulai');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    this.showToast(data.message || 'Gagal memulai unduh ulang', 'error');
                }
            } catch {
                this.showToast('Gagal menghubungi server', 'error');
            }
        },
        async saveBerkasStatus() {
            const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
            if (!csrfToken) {
                this.showToast('CSRF token tidak ditemukan', 'error');
                return;
            }

            if (!this.previewDocs[0]?.id) {
                this.showToast('Dokumen tidak tersedia', 'error');
                return;
            }

            if (!this.previewNik && !this.previewNisn) {
                this.showToast('Tidak ada perubahan yang perlu disimpan', 'error');
                return;
            }

            this.saving = true;
            try {
                const result = await this.patchBerkas(this.previewDocs[0].id, {
                    nik: this.previewNik || null,
                    nisn: this.previewNisn || null,
                }, csrfToken);

                if (result.message) {
                    this.showToast('Semua perubahan berhasil disimpan');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    this.showToast(result.message || 'Gagal menyimpan perubahan', 'error');
                    this.saving = false;
                }
            } catch {
                this.showToast('Gagal menghubungi server', 'error');
                this.saving = false;
            }
        },
        async patchBerkas(id, body, csrfToken) {
            const res = await fetch(this.updateUrlTemplate.replace('__ID__', id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify(body),
            });
            return res.json();
        },
    }));
}
