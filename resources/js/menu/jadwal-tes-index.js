const withToast = () => ({
    toast: { show: false, message: '', type: 'success', timer: null },
    showToast(message, type = 'success') {
        if (this.toast.timer) clearTimeout(this.toast.timer);
        Object.assign(this.toast, { message, type, show: true });
        this.toast.timer = setTimeout(() => {
            this.toast.show = false;
        }, 4000);
    },
});

export function registerJadwalTesIndex(Alpine) {
    Alpine.data('jadwalTesIndex', (config = {}) => ({
        ...withToast(),
        gelombangs: config.gelombangs ?? [],
        routes: config.routes ?? {},
        reviewStep: 'info',
        rejectNote: '',
        editRevisi: {
            selectedTanggal: '',
            jamMulai: '',
            interval: 30,
            linkZoom: '',
            penguji: {
                penguji_bacaan_al_quran: '',
                penguji_tajwid_tahsin: '',
                penguji_hafalan: '',
                penguji_wawancara: '',
            },
        },
        init() {
            window.menuJadwalTesIndex = this;
            if ((config.unscheduledCount ?? 0) > 0) {
                this.$nextTick(() => document.getElementById('unscheduledModal')?.showModal());
            }
        },
        openReviewModal() {
            this.reviewStep = 'info';
            this.rejectNote = '';
            document.getElementById('reviewModal')?.showModal();
        },
        showRejectForm() {
            this.reviewStep = 'reject_form';
        },
        backToInfo() {
            this.reviewStep = 'info';
        },
        submitApprove() {
            document.getElementById('approveSemuaForm')?.submit();
        },
        submitReject() {
            if (this.rejectNote.trim().length < 5) return;
            const input = document.getElementById('rejectNoteInput');
            if (input) input.value = this.rejectNote.trim();
            document.getElementById('rejectSemuaForm')?.submit();
        },
        openEditModalFromRow(data) {
            if (data.status_jadwal === 'Revisi') {
                this.editRevisi.selectedTanggal = data.tanggal ?? '';
                this.editRevisi.jamMulai = data.jam ?? '';
                this.editRevisi.linkZoom = data.link_zoom ?? '';
                const form = document.getElementById('editByDateForm');
                if (form && this.routes.updateByDate) {
                    form.action = this.routes.updateByDate.replace('__TANGGAL__', data.tanggal ?? '');
                }
                document.getElementById('editByDateModal')?.showModal();
                return;
            }

            const form = document.getElementById('editModal-form');
            if (!form || !this.routes.updateSingle) return;
            form.action = this.routes.updateSingle.replace('__ID__', data.id ?? '');
            form.querySelector('[name="jam"]').value = data.jam ?? '';
            form.querySelector('[name="link_zoom"]').value = data.link_zoom ?? '';
            document.getElementById('editModal')?.showModal();
        },
        openEditByDateGroup(data) {
            this.editRevisi.selectedTanggal = data.tanggal ?? '';
            this.editRevisi.jamMulai = data.jam_mulai ?? '';
            this.editRevisi.interval = data.interval ?? 30;
            this.editRevisi.linkZoom = data.link_zoom ?? '';
            this.editRevisi.penguji = {
                penguji_bacaan_al_quran: data.penguji?.penguji_bacaan_al_quran?.id_panitia || '',
                penguji_tajwid_tahsin: data.penguji?.penguji_tajwid_tahsin?.id_panitia || '',
                penguji_hafalan: data.penguji?.penguji_hafalan?.id_panitia || '',
                penguji_wawancara: data.penguji?.penguji_wawancara?.id_panitia || '',
            };
            const form = document.getElementById('editByDateForm');
            if (form && this.routes.updateByDate) {
                form.action = this.routes.updateByDate.replace('__TANGGAL__', data.tanggal ?? '');
            }
            document.getElementById('editByDateModal')?.showModal();
        },
        normalizeDate(value) {
            return String(value ?? '').substring(0, 10);
        },
        isDateInGelombang(date) {
            return this.gelombangs.some((gelombang) => {
                const start = this.normalizeDate(gelombang.start_date);
                const end = this.normalizeDate(gelombang.end_date);
                return date >= start && date <= end;
            });
        },
        validateAddForm(event) {
            const form = event.target;
            const requiredFields = Array.from(form.querySelectorAll('[required]')).filter((field) => !field.disabled && field.type !== 'hidden');
            const firstEmpty = requiredFields.find((field) => !String(field.value ?? '').trim());

            if (firstEmpty) {
                event.preventDefault();
                this.toggleAddAlert(true, 'Lengkapi semua field wajib terlebih dahulu.');
                firstEmpty.focus();
                return;
            }

            const date = this.normalizeDate(form.querySelector('[name="tanggal"]')?.value);
            if (date && !this.isDateInGelombang(date)) {
                event.preventDefault();
                this.toggleAddAlert(true, 'Tanggal tidak masuk dalam rentang gelombang manapun.');
                form.querySelector('[name="tanggal"]')?.focus();
                return;
            }

            this.toggleAddAlert(false);
        },
        validateEditByDateForm(event) {
            const form = event.target;
            const date = this.normalizeDate(form.querySelector('[name="tanggal_baru"]')?.value);
            if (date && !this.isDateInGelombang(date)) {
                event.preventDefault();
                this.showToast('Tanggal revisi baru tidak masuk dalam rentang gelombang manapun.', 'error');
                form.querySelector('[name="tanggal_baru"]')?.focus();
            }
        },
        toggleAddAlert(show, message = '') {
            const alert = document.getElementById('form-error');
            const messageNode = document.getElementById('form-error-message');
            if (!alert || !messageNode) return;
            alert.classList.toggle('hidden', !show);
            messageNode.textContent = message;
        },
    }));
}
