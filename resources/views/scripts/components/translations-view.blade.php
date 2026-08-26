const TranslationsView = {
    template: "#translations-view-template",
    setup() {
        const keys = ref([]);
        // UI States
        const confirmModal = ref({ show: false, message: '', confirm: null });
        const toasts = ref([]);

        const notify = (message, type = 'success') => {
            const id = Date.now();
            toasts.value.push({ id, message, type });
            setTimeout(() => {
                toasts.value = toasts.value.filter(t => t.id !== id);
            }, 3000);
        };
        const locales = ref([]);
        const loading = ref(true);
        const search = ref('');
        const savingState = ref({});

        const showAddKeyModal = ref(false);
        const newKey = ref({ group: 'messages', key: '' });

        const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 20 });
        const searchTimer = ref(null);
        let fetchRequestId = 0;

        const showExportModal = ref(false);
        const exportForm = ref({
            locale: '',
            nested: true
        });

        const exportTranslations = () => {
            if (!exportForm.value.locale) return;

            const url = `/${config.prefix}/export?locale=${exportForm.value.locale}&nested=${exportForm.value.nested ? 1 : 0}`;
            window.location.href = url;
            showExportModal.value = false;
        };

        // Filters
        const filters = ref({
            group: '',
            missing_locale: ''
        });
        const groups = ref([]);

        const fetchGroups = async () => {
            try {
                const res = await api.get('/groups');
                groups.value = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
            } catch (e) {
                console.error("Failed to load groups", e);
            }
        };

        const fetchKeys = async (page = 1) => {
            const requestId = ++fetchRequestId;
            loading.value = true;
            try {
                let url = `/translations/grouped?page=${page}&limit=${pagination.value.per_page}`;
                if (search.value) url += `&search=${encodeURIComponent(search.value)}`;
                if (filters.value.group) url += `&group=${encodeURIComponent(filters.value.group)}`;
                if (filters.value.missing_locale) url += `&missing_locale=${encodeURIComponent(filters.value.missing_locale)}`;

                const data = await api.get(url);
                if (requestId !== fetchRequestId) {
                    return;
                }

                keys.value = data.data; // API Resource puts list in 'data'
                if (data.meta) {
                    pagination.value = {
                        ...data.meta,
                        per_page: Number(data.meta.per_page) || pagination.value.per_page
                    };
                }
                // Always update locales to ensure we have the latest list
                if (data.locales) {
                    locales.value = data.locales;
                }
            } catch (e) {
                if (requestId !== fetchRequestId) {
                    return;
                }

                console.error("Data load failed:", e);
                notify(`Failed to load data: ${e.message}`, 'error');
            } finally {
                if (requestId === fetchRequestId) {
                    loading.value = false;
                }
            }
        };

        // Alias loadData to fetchKeys for compatibility if needed elsewhere,
        // but we should use fetchKeys primarily.
        const loadData = fetchKeys;

        const debouncedSearch = () => {
            clearTimeout(searchTimer.value);
            searchTimer.value = setTimeout(() => {
                pagination.value.current_page = 1;
                fetchKeys(1);
            }, 300);
        };

        onMounted(() => {
            fetchKeys(1);
            fetchGroups();
        });

        const changePage = (page) => {
            if (page >= 1 && page <= pagination.value.last_page) {
                loadData(page);
            }
        };

        const changePerPage = (limit) => {
            pagination.value.per_page = Number(limit);
            fetchKeys(1);
        };

        const updateValue = async (keyData, locale, value) => {
            const stateKey = keyData.id + '-' + locale;
            savingState.value[stateKey] = true;

            try {
                await api.put('/translations/value', {
                    key_id: keyData.id,
                    locale: locale,
                    value: value
                });
                keyData.values[locale] = value;
            } catch (e) {
                console.error(e);
                notify('Failed to save value', 'error');
            } finally {
                delete savingState.value[stateKey];
            }
        };

        const createKey = async () => {
            try {
                const res = await api.post('/keys', newKey.value);
                if (res.status === 'success') {
                    showAddKeyModal.value = false;
                    newKey.value.key = '';
                    await loadData();
                    notify('Key created successfully');
                }
            } catch (e) {
                notify('Error creating key: ' + e.message, 'error');
            }
        };

        const showAddLocaleModal = ref(false);
        const newLocale = ref({ code: '', is_default: false });

        const createLocale = async () => {
            if (!newLocale.value.code) return;
            try {
                const res = await api.post('/locales', {
                    locale: newLocale.value.code,
                    is_default: newLocale.value.is_default
                });
                if (res.status === 'success') {
                    locales.value.push(res.locale); // Push full object
                    newLocale.value = { code: '', is_default: false };
                    showAddLocaleModal.value = false;
                    await loadData();
                    notify('Locale added successfully');
                }
            } catch (e) {
                notify('Error creating locale: ' + e.message, 'error');
            }
        };

        const showEditLocaleModal = ref(false);
        const editingLocale = ref({ id: null, code: '', is_default: false });

        const openEditLocaleModal = (locale) => {
            editingLocale.value = { ...locale, is_default: !!locale.is_default };
            showEditLocaleModal.value = true;
        };

        const updateLocale = async () => {
            try {
                const res = await api.put(`/locales/${editingLocale.value.id}`, {
                    code: editingLocale.value.code,
                    is_default: editingLocale.value.is_default
                });
                if (res.status === 'success') {
                    showEditLocaleModal.value = false;
                    await loadData();
                    notify('Locale updated successfully');
                }
            } catch (e) {
                notify('Error updating locale: ' + e.message, 'error');
            }
        };

        const deleteLocale = (id) => {
            confirmModal.value = {
                show: true,
                message: 'Are you sure you want to delete this language? ALL translations for this language will be permanently deleted.',
                confirm: async () => {
                    confirmModal.value.show = false;
                    try {
                        await api.delete(`/locales/${id}`);
                        await loadData();
                        notify('Locale deleted successfully');
                    } catch (e) {
                        notify('Error deleting locale: ' + e.message, 'error');
                    }
                }
            };
        };

        const showEditKeyModal = ref(false);
        const editingKey = ref({ id: null, group: '', key: '' });

        const openEditModal = (keyData) => {
            editingKey.value = { ...keyData, key: keyData.key_name }; // Map key_name to key for the form
            showEditKeyModal.value = true;
        };

        const updateKey = async () => {
            try {
                const res = await api.put(`/keys/${editingKey.value.id}`, {
                    group: editingKey.value.group,
                    key: editingKey.value.key
                });
                if (res.status === 'success') {
                    showEditKeyModal.value = false;
                    await loadData();
                    notify('Key updated successfully');
                }
            } catch (e) {
                notify('Error updating key: ' + e.message, 'error');
            }
        };

        const deleteKey = (id) => {
            confirmModal.value = {
                show: true,
                message: 'Are you sure you want to delete this key? All translations associated with it will be permanently removed.',
                confirm: async () => {
                    confirmModal.value.show = false;
                    try {
                        await api.delete(`/keys/${id}`);
                        await loadData();
                        notify('Key deleted successfully');
                    } catch (e) {
                        notify('Error deleting key: ' + e.message, 'error');
                    }
                }
            };
        };

        // Import Logic
        const showImportModal = ref(false);
        const importData = ref({ locale: '', file: null });
        const importing = ref(false);

        const handleFileChange = (event) => {
            const file = event.target.files[0];
            if (file && validateImportFile(file, event.target)) {
                importData.value.file = file;
            }
        };

        const validateImportFile = (file, input = null) => {
            const isJson = file.name.toLowerCase().endsWith('.json');
            const isWithinSizeLimit = file.size <= MAX_IMPORT_FILE_SIZE;

            if (!isJson || !isWithinSizeLimit) {
                importData.value.file = null;
                if (input) {
                    input.value = '';
                }

                notify(
                    !isJson
                        ? 'Only JSON files are allowed.'
                        : 'JSON files must be 10MB or smaller.',
                    'error'
                );
                return false;
            }

            return true;
        };

        const executeImport = async () => {
            if (!importData.value.locale || !importData.value.file) {
                notify('Please select a locale and a file.', 'error');
                return;
            }

            importing.value = true;
            // Use FormData for file upload
            const formData = new FormData();
            formData.append('locale', importData.value.locale);
            formData.append('file', importData.value.file);

            try {
                const res = await api.post('/import', formData, true); // true for multipart
                notify(`Import successful! processed ${res.count} strings.`);
                showImportModal.value = false;
                importData.value = { locale: '', file: null }; // Reset
                loadData(); // Reload grid
            } catch (e) {
                // Error handled by api util usually, but just in case
                console.error(e);
            } finally {
                importing.value = false;
            }
        };

        const handleFileDrop = (event) => {
            const file = event.dataTransfer.files[0];
            if (file) {
                validateImportFile(file);
            }
        };

        return {
            keys, locales, loading, search, debouncedSearch, updateValue, savingState,
            filters, groups, fetchKeys, // Explicitly exposed
            showAddKeyModal, newKey, createKey,
            showAddLocaleModal, newLocale, createLocale,
            showEditKeyModal, editingKey, openEditModal, updateKey, deleteKey,
            showEditLocaleModal, editingLocale, openEditLocaleModal, updateLocale, deleteLocale,
            toasts, confirmModal, pagination, changePage, changePerPage,
            showImportModal, importData, handleFileChange, handleFileDrop, executeImport, importing,
            showExportModal, exportForm, exportTranslations
        };
    }
};
