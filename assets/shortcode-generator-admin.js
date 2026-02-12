(function () {
    var root = document.querySelector('[data-lllm-shortcode-generator="true"]');
    if (!root || typeof lllmShortcodeGeneratorConfig !== 'object') {
        return;
    }

    var map = lllmShortcodeGeneratorConfig.shortcodeMap || {};
    var ajaxUrl = String(lllmShortcodeGeneratorConfig.ajaxUrl || '');
    var valueSourceNonce = String(lllmShortcodeGeneratorConfig.valueSourceNonce || '');
    var messages = lllmShortcodeGeneratorConfig.messages || {};
    var optionalLabel = String(messages.optional || 'optional');
    var copiedLabel = String(messages.copied || 'Copied!');
    var copyFallbackLabel = String(messages.copyFallback || 'Press Ctrl/Cmd+C to copy.');
    var loadingOptionsLabel = String(messages.loadingOptions || 'Loading options…');
    var noOptionsLabel = String(messages.noOptions || 'No options available');
    var optionsLoadErrorLabel = String(messages.optionsLoadError || 'Could not load options.');
    var retryLabel = String(messages.retry || 'Retry');

    var typeSelect = document.getElementById('lllm-shortcode-type');
    var attributesRoot = document.getElementById('lllm-shortcode-attributes');
    var output = document.getElementById('lllm-shortcode-output');
    var copyButton = document.getElementById('lllm-shortcode-copy');
    var copyNotice = document.getElementById('lllm-shortcode-copy-notice');

    if (!typeSelect || !attributesRoot || !output || !copyButton || !copyNotice) {
        return;
    }

    var activeAttributeState = {};
    var dynamicSourceOptionCache = {};

    var normalizeOptionConfig = function (optionConfig) {
        if (optionConfig && typeof optionConfig === 'object') {
            var normalizedValue = String(typeof optionConfig.value === 'undefined' ? '' : optionConfig.value);
            var normalizedLabel = String(typeof optionConfig.label === 'undefined' ? normalizedValue : optionConfig.label);

            return {
                label: normalizedLabel,
                value: normalizedValue
            };
        }

        return {
            label: String(optionConfig),
            value: String(optionConfig)
        };
    };

    var getAttributeValue = function (attributeName) {
        var entry = activeAttributeState[attributeName];
        if (entry && typeof entry === 'object' && Object.prototype.hasOwnProperty.call(entry, 'value')) {
            return String(entry.value || '');
        }

        return String(entry || '');
    };

    var setCopyNotice = function (message) {
        copyNotice.textContent = message || '';
    };

    var esc = function (value) {
        return String(value).replace(/["\\]/g, '\\$&');
    };

    var clearAttributeUiAndState = function () {
        activeAttributeState = {};
        attributesRoot.innerHTML = '';
    };

    var renderOptionElements = function (select, options, includeBlankOption) {
        select.innerHTML = '';

        if (includeBlankOption) {
            var blankOption = document.createElement('option');
            blankOption.value = '';
            blankOption.dataset.label = '';
            blankOption.textContent = '—';
            select.appendChild(blankOption);
        }

        options.forEach(function (optionConfig) {
            var optionState = normalizeOptionConfig(optionConfig);
            var option = document.createElement('option');
            option.value = optionState.value;
            option.dataset.label = optionState.label;
            option.textContent = optionState.label === optionState.value
                ? optionState.label
                : optionState.label + ' (' + optionState.value + ')';
            select.appendChild(option);
        });
    };

    var setSelectLoadingState = function (select, includeBlankOption) {
        select.disabled = true;
        renderOptionElements(select, [{ label: loadingOptionsLabel, value: '' }], includeBlankOption);
    };

    var setSelectEmptyState = function (select, includeBlankOption) {
        select.disabled = true;
        renderOptionElements(select, [{ label: noOptionsLabel, value: '' }], includeBlankOption);
    };

    var setSelectErrorState = function (select, message, includeBlankOption) {
        select.disabled = true;
        renderOptionElements(select, [{ label: message || optionsLoadErrorLabel, value: '' }], includeBlankOption);
    };

    var setSelectReadyState = function (select, options, includeBlankOption, defaultValue) {
        renderOptionElements(select, options, includeBlankOption);
        select.disabled = false;
        select.value = defaultValue;

        if (select.value !== defaultValue) {
            select.selectedIndex = 0;
        }
    };

    var getStateFromSelect = function (select) {
        var selectedOption = select.options[select.selectedIndex] || null;
        return {
            label: selectedOption ? String(selectedOption.dataset.label || '') : '',
            value: String(select.value || '')
        };
    };

    var fetchDynamicOptions = function (sourceKey) {
        var normalizedSourceKey = String(sourceKey || '');
        if (!normalizedSourceKey) {
            return Promise.resolve([]);
        }

        if (Object.prototype.hasOwnProperty.call(dynamicSourceOptionCache, normalizedSourceKey)) {
            return Promise.resolve(dynamicSourceOptionCache[normalizedSourceKey]);
        }

        if (!ajaxUrl || !valueSourceNonce) {
            return Promise.reject(new Error('missing_config'));
        }

        var body = new URLSearchParams();
        body.set('action', 'lllm_shortcode_generator_value_source');
        body.set('nonce', valueSourceNonce);
        body.set('source_key', normalizedSourceKey);

        return fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('http_error');
            }

            return response.json();
        }).then(function (payload) {
            if (!payload || payload.success !== true || !payload.data || !Array.isArray(payload.data.options)) {
                throw new Error('invalid_payload');
            }

            var normalizedOptions = payload.data.options.map(normalizeOptionConfig);
            dynamicSourceOptionCache[normalizedSourceKey] = normalizedOptions;
            return normalizedOptions;
        });
    };

    var buildShortcode = function () {
        var selected = typeSelect.value;
        var definition = map[selected];

        if (!definition) {
            output.value = '';
            setCopyNotice('');
            return;
        }

        var parts = ['[' + selected];
        (definition.attributes || []).forEach(function (attributeName) {
            var meta = (definition.attribute_meta && definition.attribute_meta[attributeName]) ? definition.attribute_meta[attributeName] : {};
            var value = getAttributeValue(attributeName).trim();

            if (meta.optional && value === '') {
                return;
            }

            parts.push(attributeName + '="' + esc(value) + '"');
        });

        parts.push(']');
        output.value = parts.join(' ');
    };

    var selectOutputForManualCopy = function () {
        output.focus();
        output.select();
    };

    var handleCopyClick = function () {
        if (!output.value) {
            setCopyNotice('');
            return;
        }

        var onCopyFailed = function () {
            selectOutputForManualCopy();
            setCopyNotice(copyFallbackLabel);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(output.value).then(function () {
                setCopyNotice(copiedLabel);
            }).catch(onCopyFailed);
            return;
        }

        onCopyFailed();
    };

    var buildFieldErrorMessage = function (message, onRetry) {
        var wrapper = document.createElement('div');
        wrapper.className = 'lllm-shortcode-field-status lllm-shortcode-field-status-error';

        var text = document.createElement('span');
        text.textContent = message;
        wrapper.appendChild(text);

        if (typeof onRetry === 'function') {
            var retryButton = document.createElement('button');
            retryButton.type = 'button';
            retryButton.className = 'button-link';
            retryButton.textContent = retryLabel;
            retryButton.addEventListener('click', onRetry);
            wrapper.appendChild(document.createTextNode(' '));
            wrapper.appendChild(retryButton);
        }

        return wrapper;
    };

    var renderAttributes = function () {
        var selected = typeSelect.value;
        var definition = map[selected];

        clearAttributeUiAndState();
        setCopyNotice('');

        if (!definition) {
            buildShortcode();
            return;
        }

        var table = document.createElement('table');
        table.className = 'form-table';
        table.setAttribute('role', 'presentation');

        var tbody = document.createElement('tbody');

        (definition.attributes || []).forEach(function (attributeName) {
            var meta = (definition.attribute_meta && definition.attribute_meta[attributeName]) ? definition.attribute_meta[attributeName] : {};
            var row = document.createElement('tr');
            var header = document.createElement('th');
            header.setAttribute('scope', 'row');

            var label = document.createElement('label');
            var inputId = 'lllm-shortcode-attr-' + attributeName;
            label.setAttribute('for', inputId);
            label.textContent = meta.label || attributeName;
            header.appendChild(label);

            if (meta.optional) {
                var help = document.createElement('span');
                help.className = 'lllm-shortcode-attribute-optional';
                help.textContent = '(' + optionalLabel + ')';
                header.appendChild(help);
            }

            var cell = document.createElement('td');
            var input;
            var controlType = meta.control_type || 'text';
            var source = (meta && meta.value_source) ? meta.value_source : {};
            var defaultValue = (typeof meta.default_value === 'undefined') ? '' : String(meta.default_value);

            if (controlType === 'select') {
                input = document.createElement('select');
                input.id = inputId;
                input.dataset.attribute = attributeName;
                input.className = 'regular-text';

                var includeBlankOption = !!meta.optional;
                var initialOptions = [];
                if (source.type === 'static' && Array.isArray(source.options)) {
                    initialOptions = source.options;
                }

                if (source.type === 'dynamic') {
                    setSelectLoadingState(input, includeBlankOption);
                    activeAttributeState[attributeName] = { label: '', value: '' };

                    var statusMount = document.createElement('div');
                    statusMount.className = 'lllm-shortcode-field-status';
                    cell.appendChild(input);
                    cell.appendChild(statusMount);

                    var loadDynamicOptions = function () {
                        statusMount.innerHTML = '';
                        setSelectLoadingState(input, includeBlankOption);
                        activeAttributeState[attributeName] = { label: '', value: '' };
                        buildShortcode();

                        fetchDynamicOptions(source.key).then(function (dynamicOptions) {
                            if (!dynamicOptions.length) {
                                setSelectEmptyState(input, includeBlankOption);
                                activeAttributeState[attributeName] = { label: '', value: '' };
                                buildShortcode();
                                return;
                            }

                            setSelectReadyState(input, dynamicOptions, includeBlankOption, defaultValue);
                            activeAttributeState[attributeName] = getStateFromSelect(input);
                            buildShortcode();
                        }).catch(function () {
                            setSelectErrorState(input, optionsLoadErrorLabel, includeBlankOption);
                            activeAttributeState[attributeName] = { label: '', value: '' };
                            statusMount.innerHTML = '';
                            statusMount.appendChild(buildFieldErrorMessage(optionsLoadErrorLabel, loadDynamicOptions));
                            buildShortcode();
                        });
                    };

                    var onDynamicFieldChange = function () {
                        activeAttributeState[attributeName] = getStateFromSelect(input);
                        buildShortcode();
                    };

                    input.addEventListener('input', onDynamicFieldChange);
                    input.addEventListener('change', onDynamicFieldChange);

                    loadDynamicOptions();
                } else {
                    renderOptionElements(input, initialOptions, includeBlankOption);
                    input.value = defaultValue;
                    if (input.value !== defaultValue) {
                        input.selectedIndex = 0;
                    }
                    activeAttributeState[attributeName] = getStateFromSelect(input);

                    var onSelectChange = function () {
                        activeAttributeState[attributeName] = getStateFromSelect(input);
                        buildShortcode();
                    };

                    input.addEventListener('input', onSelectChange);
                    input.addEventListener('change', onSelectChange);
                    cell.appendChild(input);
                }
            } else if (controlType === 'checkbox') {
                input = document.createElement('input');
                input.type = 'checkbox';
                input.checked = defaultValue === '1';
                input.id = inputId;
                input.dataset.attribute = attributeName;
                activeAttributeState[attributeName] = { value: input.checked ? '1' : '0' };

                var onCheckboxChange = function () {
                    activeAttributeState[attributeName] = {
                        value: input.checked ? '1' : '0'
                    };
                    buildShortcode();
                };

                input.addEventListener('input', onCheckboxChange);
                input.addEventListener('change', onCheckboxChange);
                cell.appendChild(input);
            } else {
                input = document.createElement('input');
                input.type = controlType === 'number' ? 'number' : 'text';
                input.id = inputId;
                input.dataset.attribute = attributeName;
                input.className = 'regular-text';
                input.value = defaultValue;
                activeAttributeState[attributeName] = { value: defaultValue };

                var onFieldChange = function () {
                    activeAttributeState[attributeName] = {
                        value: String(input.value || '')
                    };
                    buildShortcode();
                };

                input.addEventListener('input', onFieldChange);
                input.addEventListener('change', onFieldChange);
                cell.appendChild(input);
            }

            row.appendChild(header);
            row.appendChild(cell);
            tbody.appendChild(row);
        });

        table.appendChild(tbody);
        attributesRoot.appendChild(table);
        buildShortcode();
    };

    Object.keys(map).forEach(function (shortcodeName) {
        var definition = map[shortcodeName] || {};
        var option = document.createElement('option');
        option.value = shortcodeName;
        option.textContent = definition.display_label ? definition.display_label + ' (' + shortcodeName + ')' : shortcodeName;
        typeSelect.appendChild(option);
    });

    typeSelect.addEventListener('change', renderAttributes);
    copyButton.addEventListener('click', handleCopyClick);
    renderAttributes();
})();
