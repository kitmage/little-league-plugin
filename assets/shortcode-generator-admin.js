(function () {
    var root = document.querySelector('[data-lllm-shortcode-generator="true"]');
    if (!root || typeof lllmShortcodeGeneratorConfig !== 'object') {
        return;
    }

    var map = lllmShortcodeGeneratorConfig.shortcodeMap || {};
    var messages = lllmShortcodeGeneratorConfig.messages || {};
    var optionalLabel = String(messages.optional || 'optional');
    var allowedValuesLabel = String(messages.allowedValues || 'Allowed values:');
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
            var rawValue = (typeof activeAttributeState[attributeName] === 'undefined') ? '' : activeAttributeState[attributeName];
            var value = String(rawValue).trim();

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
            var allowed = Array.isArray(meta.allowed_values) ? meta.allowed_values : [];
            var defaultValue = (typeof meta.default_value === 'undefined') ? '' : String(meta.default_value);

            if (meta.input_type === 'select' && allowed.length) {
                input = document.createElement('select');
                allowed.forEach(function (allowedValue) {
                    var allowedOption = document.createElement('option');
                    allowedOption.value = allowedValue;
                    allowedOption.textContent = allowedValue;
                    input.appendChild(allowedOption);
                });
            } else {
                input = document.createElement('input');
                input.type = meta.input_type === 'number' ? 'number' : 'text';
            }

            input.id = inputId;
            input.dataset.attribute = attributeName;
            input.className = 'regular-text';
            input.value = defaultValue;
            activeAttributeState[attributeName] = defaultValue;

            var onFieldChange = function () {
                activeAttributeState[attributeName] = String(input.value || '');
                buildShortcode();
            };

            input.addEventListener('input', onFieldChange);
            input.addEventListener('change', onFieldChange);
            cell.appendChild(input);

            if (allowed.length && meta.input_type !== 'select') {
                var allowedHint = document.createElement('p');
                allowedHint.className = 'description';
                allowedHint.textContent = allowedValuesLabel + ' ' + allowed.join(', ');
                cell.appendChild(allowedHint);
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
})();
