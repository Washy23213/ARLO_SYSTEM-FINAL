@php
    $departmentProgramFieldConfig = $departmentProgramFieldConfig ?? [];
    $programCatalog = $programCatalog ?? \App\Support\SystemOptions::programCatalogByDepartment();
@endphp
<script>
    (function () {
        const config = @json($departmentProgramFieldConfig);
        const programCatalog = @json($programCatalog);

        if (!config.departmentSelectId || !config.degreeSelectId) {
            return;
        }

        const groupLabels = {
            undergraduate: 'Undergraduate Programs',
            master: "Master's Programs",
            doctorate: 'Doctorate Programs',
            nondegree: 'Non-Degree Programs',
        };

        const lockedEmploymentValues = (config.lockedEmploymentValues || ['No', 'Unemployed'])
            .map((value) => String(value || '').trim().toLowerCase())
            .filter(Boolean);

        function getField(id) {
            return id ? document.getElementById(id) : null;
        }

        function selectHasOption(select, value) {
            if (!select || !value) {
                return false;
            }

            return Array.from(select.options).some((option) => option.value === value);
        }

        function setDisabledPresentation(field, disabled) {
            if (!field) {
                return;
            }

            field.classList.toggle('bg-slate-100', disabled);
            field.classList.toggle('bg-gray-100', disabled);
            field.classList.toggle('cursor-not-allowed', disabled);
            field.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        }

        function clearFieldValue(field) {
            if (!field) {
                return;
            }

            if (field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
                field.value = '';
                return;
            }

            if (field instanceof HTMLInputElement) {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = false;
                    return;
                }

                field.value = '';
            }
        }

        function renderDegreeOptions(preferredValue) {
            const departmentSelect = getField(config.departmentSelectId);
            const degreeSelect = getField(config.degreeSelectId);

            if (!departmentSelect || !degreeSelect) {
                return;
            }

            const selectedDepartment = String(departmentSelect.value || '').trim();
            const selectedDegree = typeof preferredValue === 'string'
                ? preferredValue.trim()
                : String(degreeSelect.value || config.selectedDegree || '').trim();

            degreeSelect.innerHTML = '';

            if (!selectedDepartment) {
                degreeSelect.add(new Option('Select college first', ''));

                if (selectedDegree !== '') {
                    degreeSelect.add(new Option(selectedDegree, selectedDegree));
                    degreeSelect.value = selectedDegree;
                }

                degreeSelect.disabled = true;
                setDisabledPresentation(degreeSelect, true);
                return;
            }

            const groups = programCatalog[selectedDepartment] || {};
            const availablePrograms = [];

            degreeSelect.add(new Option('Select Degree / Program', ''));

            Object.entries(groupLabels).forEach(([groupKey, label]) => {
                const programs = Array.isArray(groups[groupKey]) ? groups[groupKey] : [];

                if (!programs.length) {
                    return;
                }

                const groupElement = document.createElement('optgroup');
                groupElement.label = label;

                programs.forEach((program) => {
                    availablePrograms.push(program);
                    groupElement.appendChild(new Option(program, program));
                });

                degreeSelect.appendChild(groupElement);
            });

            if (selectedDegree !== '' && !availablePrograms.includes(selectedDegree)) {
                degreeSelect.appendChild(new Option(selectedDegree, selectedDegree));
            }

            degreeSelect.disabled = availablePrograms.length === 0 && selectedDegree === '';
            setDisabledPresentation(degreeSelect, degreeSelect.disabled);

            if (selectHasOption(degreeSelect, selectedDegree)) {
                degreeSelect.value = selectedDegree;
                return;
            }

            degreeSelect.value = '';
        }

        function isEmploymentLocked() {
            const employmentStatusSelect = getField(config.employmentStatusSelectId);

            if (!employmentStatusSelect) {
                return false;
            }

            const normalizedValue = String(employmentStatusSelect.value || '').trim().toLowerCase();

            return lockedEmploymentValues.includes(normalizedValue);
        }

        function syncEmploymentFields() {
            const locked = isEmploymentLocked();
            const fieldIds = Array.isArray(config.employmentFieldIds) ? config.employmentFieldIds : [];

            fieldIds.forEach((fieldId) => {
                const field = getField(fieldId);

                if (!field) {
                    return;
                }

                if (locked) {
                    clearFieldValue(field);
                }

                field.disabled = locked;
                setDisabledPresentation(field, locked);
            });

            syncSpecificLocationField();
        }

        function syncSpecificLocationField() {
            const locationSelect = getField(config.locationSelectId);
            const specificLocationGroup = getField(config.specificLocationGroupId);
            const specificLocationInput = getField(config.specificLocationInputId);

            if (!locationSelect || !specificLocationGroup || !specificLocationInput) {
                return;
            }

            const locked = isEmploymentLocked();
            const showSpecificLocation = !locked && locationSelect.value === 'Others';

            specificLocationGroup.classList.toggle('hidden', !showSpecificLocation);
            specificLocationInput.disabled = locked || !showSpecificLocation;
            setDisabledPresentation(specificLocationInput, locked || !showSpecificLocation);

            if (showSpecificLocation) {
                specificLocationInput.setAttribute('required', 'required');
                return;
            }

            specificLocationInput.removeAttribute('required');
            specificLocationInput.value = '';
        }

        function init() {
            const departmentSelect = getField(config.departmentSelectId);
            const degreeSelect = getField(config.degreeSelectId);

            if (!departmentSelect || !degreeSelect || departmentSelect.dataset.programLogicReady === 'true') {
                return;
            }

            departmentSelect.dataset.programLogicReady = 'true';

            departmentSelect.addEventListener('change', () => {
                renderDegreeOptions('');
            });

            const employmentStatusSelect = getField(config.employmentStatusSelectId);
            if (employmentStatusSelect) {
                employmentStatusSelect.addEventListener('change', syncEmploymentFields);
            }

            const locationSelect = getField(config.locationSelectId);
            if (locationSelect) {
                locationSelect.addEventListener('change', syncSpecificLocationField);
            }

            renderDegreeOptions(config.selectedDegree || '');
            syncEmploymentFields();
            syncSpecificLocationField();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init, { once: true });
        } else {
            init();
        }

        document.addEventListener('admin:page-ready', init);
    })();
</script>
