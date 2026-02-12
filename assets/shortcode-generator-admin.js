(function () {
    var root = document.querySelector('[data-lllm-shortcode-generator="true"]');
    if (!root || typeof lllmShortcodeGeneratorConfig !== 'object') {
        return;
    }

    var map = lllmShortcodeGeneratorConfig.shortcodeMap || {};
    var valueSources = lllmShortcodeGeneratorConfig.valueSources || {};
    var messages = lllmShortcodeGeneratorConfig.messages || {};
    var optionalLabel = String(messages.optional || 'optional');
    var copiedLabel = String(messages.copied || 'Copied!');
    var copyFallbackLabel = String(messages.copyFallback || 'Press Ctrl/Cmd+C to copy.');

    var typeSelect = document.getElementById('lllm-shortcode-type');
    var attributesRoot = document.getElementById('lllm-shortcode-attributes');
    var output = document.getElementById('lllm-shortcode-output');
    var copyButton = document.getElementById('lllm-shortcode-copy');
    var copyNotice = document.getElementById('lllm-shortcode-copy-notice');

    if (!typeSelect || !attributesRoot || !output || !copyButton || !copyNotice) {
        return;
    }

    var activeAttributeState = {};

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

    var resolveOptions = function (meta) {
        var source = (meta && meta.value_source) ? meta.value_source : {};
        if (source.type === 'static' && Array.isArray(source.options)) {
            return source.options;
        }

        if (source.type === 'dynamic' && source.key && Array.isArray(valueSources[source.key])) {
            return valueSources[source.key];
        }

        return [];
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
            var options = resolveOptions(meta);
            var defaultValue = (typeof meta.default_value === 'undefined') ? '' : String(meta.default_value);

            if (controlType === 'select') {
                input = document.createElement('select');

                if (meta.optional) {
                    var blankOption = document.createElement('option');
                    blankOption.value = '';
                    blankOption.textContent = '—';
                    input.appendChild(blankOption);
                }

                options.forEach(function (optionConfig) {
                    var optionState = normalizeOptionConfig(optionConfig);
                    var option = document.createElement('option');
                    option.value = optionState.value;
                    option.dataset.label = optionState.label;
                    option.textContent = optionState.label === optionState.value
                        ? optionState.label
                        : optionState.label + ' (' + optionState.value + ')';
                    input.appendChild(option);
                });
            } else if (controlType === 'checkbox') {
                input = document.createElement('input');
                input.type = 'checkbox';
                input.checked = defaultValue === '1';
                input.className = '';
            } else {
                input = document.createElement('input');
                input.type = controlType === 'number' ? 'number' : 'text';
                input.value = defaultValue;
            }

            input.id = inputId;
            input.dataset.attribute = attributeName;
            if (controlType !== 'checkbox') {
                input.className = 'regular-text';
                input.value = defaultValue;
            }
            if (controlType === 'select') {
                var initialOption = input.options[input.selectedIndex] || null;
                activeAttributeState[attributeName] = {
                    label: initialOption ? String(initialOption.dataset.label || '') : '',
                    value: String(input.value || '')
                };
            } else {
                activeAttributeState[attributeName] = {
                    value: controlType === 'checkbox'
                        ? (input.checked ? '1' : '0')
                        : defaultValue
                };
            }

            var onFieldChange = function () {
                if (controlType === 'select') {
                    var selectedOption = input.options[input.selectedIndex] || null;
                    activeAttributeState[attributeName] = {
                        label: selectedOption ? String(selectedOption.dataset.label || '') : '',
                        value: String(input.value || '')
                    };
                } else {
                    activeAttributeState[attributeName] = {
                        value: controlType === 'checkbox'
                            ? (input.checked ? '1' : '0')
                            : String(input.value || '')
                    };
                }
                buildShortcode();
            };

            input.addEventListener('input', onFieldChange);
            input.addEventListener('change', onFieldChange);
            cell.appendChild(input);

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
