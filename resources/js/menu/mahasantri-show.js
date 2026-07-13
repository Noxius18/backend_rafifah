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
        savingReview: false,
        previewNik: config.nik ?? '',
        previewNisn: config.nisn ?? '',
        originalNik: config.nik ?? '',
        originalNisn: config.nisn ?? '',
        approveTarget: { id: '', title: '' },
        rejectTarget: { id: '', title: '' },
        rejectDraftNote: '',
        init() {
            this.previewDocs = this.previewDocsSource.map((doc) => this.normalizePreviewDoc(doc));
            this.previewDocsSource = this.previewDocs.map((doc) => ({ ...doc }));
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
        get dirtyReviewCount() {
            return this.previewDocs.filter((doc) => this.isReviewDirty(doc)).length;
        },
        get hasDirtyReviewChanges() {
            return this.dirtyReviewCount > 0;
        },
        get hasIdentityChanges() {
            return this.previewNik !== this.originalNik || this.previewNisn !== this.originalNisn;
        },
        normalizePreviewDoc(doc) {
            const status = doc.statusVerifikasi ?? 'menunggu';
            const catatan = doc.catatanRevisi ?? '';

            return {
                ...doc,
                originalStatusVerifikasi: status,
                draftStatusVerifikasi: doc.draftStatusVerifikasi ?? status,
                originalCatatanRevisi: catatan,
                draftCatatanRevisi: doc.draftCatatanRevisi ?? catatan,
            };
        },
        isReviewDirty(doc) {
            return (doc?.draftStatusVerifikasi ?? 'menunggu') !== (doc?.originalStatusVerifikasi ?? 'menunggu')
                || (doc?.draftCatatanRevisi ?? '') !== (doc?.originalCatatanRevisi ?? '');
        },
        findPreviewDoc(id) {
            return this.previewDocs.find((doc) => doc.id === id) ?? null;
        },
        openPreview(index) {
            this.previewDocIndex = index;
            document.getElementById('previewModal')?.showModal();
        },
        prevDoc() {
            if (this.previewDocIndex > 0) this.previewDocIndex -= 1;
        },
        nextDoc() {
            if (this.previewDocIndex < this.previewDocs.length - 1) this.previewDocIndex += 1;
        },
        getDisplayStatus(berkasId, fallbackStatus = 'menunggu') {
            return this.findPreviewDoc(berkasId)?.draftStatusVerifikasi ?? fallbackStatus;
        },
        getDisplayCatatan(berkasId, fallbackCatatan = '') {
            return this.findPreviewDoc(berkasId)?.draftCatatanRevisi ?? fallbackCatatan ?? '';
        },
        isDraftDirtyById(berkasId) {
            const doc = this.findPreviewDoc(berkasId);
            return doc ? this.isReviewDirty(doc) : false;
        },
        statusBadgeClass(status) {
            if (status === 'disetujui') {
                return 'rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200';
            }

            if (status === 'ditolak') {
                return 'rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200';
            }

            return 'rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200';
        },
        statusLabel(status) {
            if (status === 'disetujui') return '✅ Disetujui';
            if (status === 'ditolak') return '❌ Ditolak';
            return '⏳ Menunggu';
        },
        openApproveModal(id, title) {
            this.approveTarget = { id, title };
            document.getElementById('approveBerkasModal')?.showModal();
        },
        confirmApproveDraft() {
            const doc = this.findPreviewDoc(this.approveTarget.id);
            if (!doc) {
                this.showToast('Dokumen tidak ditemukan', 'error');
                return;
            }

            doc.draftStatusVerifikasi = 'disetujui';
            doc.draftCatatanRevisi = '';
            document.getElementById('approveBerkasModal')?.close();
            document.getElementById('previewModal')?.showModal();
            this.showToast(`Draft verifikasi ${doc.title} disimpan lokal.`);
        },
        openRejectModal(id, title) {
            const doc = this.findPreviewDoc(id);
            this.rejectTarget = { id, title };
            this.rejectDraftNote = doc?.draftCatatanRevisi ?? '';
            document.getElementById('rejectBerkasModal')?.showModal();
        },
        confirmRejectDraft() {
            const doc = this.findPreviewDoc(this.rejectTarget.id);
            const catatan = this.rejectDraftNote.trim();

            if (!doc) {
                this.showToast('Dokumen tidak ditemukan', 'error');
                return;
            }

            if (!catatan) {
                this.showToast('Alasan catatan revisi wajib diisi jika berkas ditolak!', 'error');
                document.getElementById('inputCatatanRevisi')?.focus();
                return;
            }

            doc.draftStatusVerifikasi = 'ditolak';
            doc.draftCatatanRevisi = catatan;
            document.getElementById('rejectBerkasModal')?.close();
            document.getElementById('previewModal')?.showModal();
            this.showToast(`Draft penolakan ${doc.title} disimpan lokal.`);
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
        async saveReviewChanges() {
            const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
            if (!csrfToken) {
                this.showToast('CSRF token tidak ditemukan', 'error');
                return;
            }

            if (!this.previewDocs[0]?.id) {
                this.showToast('Dokumen tidak tersedia', 'error');
                return;
            }

            if (!this.hasDirtyReviewChanges && !this.hasIdentityChanges) {
                this.showToast('Tidak ada perubahan yang perlu disimpan', 'error');
                return;
            }

            this.savingReview = true;
            const changedDocs = this.previewDocs.filter((doc) => this.isReviewDirty(doc));
            const hadReviewChanges = changedDocs.length > 0;
            const hadIdentityChanges = this.hasIdentityChanges;
            const identityPayload = {
                nik: this.previewNik || null,
                nisn: this.previewNisn || null,
            };
            const reviewPayloadBase = hadIdentityChanges ? identityPayload : {};
            const failedDocs = [];
            let identitySaved = !hadIdentityChanges;

            try {
                if (!hadReviewChanges && hadIdentityChanges) {
                    const result = await this.patchBerkas(this.previewDocs[0].id, identityPayload, csrfToken);

                    if (result.ok) {
                        identitySaved = true;
                    } else {
                        failedDocs.push(result.data?.message || 'Data identitas gagal disimpan');
                    }
                }

                for (const [index, doc] of changedDocs.entries()) {
                    const result = await this.patchBerkas(doc.id, {
                        ...(index === 0 ? reviewPayloadBase : {}),
                        status_verifikasi: doc.draftStatusVerifikasi,
                        catatan_revisi: doc.draftStatusVerifikasi === 'ditolak'
                            ? doc.draftCatatanRevisi
                            : null,
                    }, csrfToken);

                    if (result.ok) {
                        doc.originalStatusVerifikasi = doc.draftStatusVerifikasi;
                        doc.originalCatatanRevisi = doc.draftStatusVerifikasi === 'ditolak'
                            ? doc.draftCatatanRevisi
                            : '';
                        if (index === 0 && hadIdentityChanges) {
                            identitySaved = true;
                        }
                    } else {
                        failedDocs.push(result.data?.message ? `${doc.title} (${result.data.message})` : doc.title);
                    }
                }

                if (identitySaved) {
                    this.originalNik = this.previewNik;
                    this.originalNisn = this.previewNisn;
                }

                this.previewDocsSource = this.previewDocs.map((doc) => ({ ...doc }));

                if (failedDocs.length === 0) {
                    const successMessage = hadReviewChanges && hadIdentityChanges
                        ? 'Perubahan verifikasi dan identitas berhasil disimpan'
                        : hadReviewChanges
                            ? 'Perubahan verifikasi berhasil disimpan'
                            : 'Perubahan data identitas berhasil disimpan';
                    this.showToast(successMessage);
                    return;
                }

                this.showToast(`Sebagian perubahan gagal disimpan: ${failedDocs.join(', ')}`, 'error');
            } catch {
                this.showToast('Gagal menghubungi server', 'error');
            } finally {
                this.savingReview = false;
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
            const data = await res.json();

            return {
                ok: res.ok,
                data,
            };
        },
    }));
}
