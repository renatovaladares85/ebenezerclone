<?php

include('../../../inc/includes.php');

header('Content-Type: application/javascript');

if (
    ($_SESSION['glpiactiveprofile']['interface'] ?? '') !== 'central'
) {
    return;
}

if (!class_exists('PluginEbenezercloneClone')) {
    include_once __DIR__ . '/../inc/clone.class.php';
}

if (PluginEbenezercloneClone::canUseTicketCloneActionInCurrentProfile()) {
    return;
}

echo <<<'JAVASCRIPT'
(function ($) {
    if (typeof $ === 'undefined') {
        return;
    }

    const isTicketContext = function () {
        const path = (window.location.pathname || '').toLowerCase();
        if (path.endsWith('/front/ticket.php') || path.endsWith('/front/ticket.form.php')) {
            return true;
        }

        return $("form[data-search-itemtype='Ticket'], form[id^='massformTicket']").length > 0;
    };

    const normalize = function (value) {
        return (value || '').toString().replace(/\s+/g, ' ').trim().toLowerCase();
    };

    const isCloneActionKey = function (value) {
        const normalized = normalize(value);
        return normalized === 'clone' || normalized.endsWith(':clone');
    };

    const isCloneLabel = function (value) {
        const normalized = normalize(value);
        return normalized === 'clone' || normalized === 'clonar';
    };

    const removeSingleActionClone = function (scope) {
        $(scope).find('#single-ma-action-menu [data-action]').each(function () {
            const item = $(this);
            if (isCloneActionKey(item.attr('data-action')) || isCloneLabel(item.text())) {
                item.remove();
            }
        });
    };

    const removeMassiveActionClone = function (scope) {
        $(scope).find("select[name='massiveaction']").each(function () {
            const select = $(this);
            select.find('option').each(function () {
                const option = $(this);
                if (isCloneActionKey(option.val()) || isCloneLabel(option.text())) {
                    option.remove();
                }
            });

            if (isCloneActionKey(select.val())) {
                select.val('-1').trigger('change');
            }
        });
    };

    const removeLegacyCloneSubmit = function (scope) {
        $(scope).find("input[type='submit'][name='clone'], button[name='clone']").remove();
    };

    const applyRestrictions = function (scope) {
        if (!isTicketContext()) {
            return;
        }

        const root = scope || document;
        removeSingleActionClone(root);
        removeMassiveActionClone(root);
        removeLegacyCloneSubmit(root);
    };

    $(function () {
        applyRestrictions(document);

        $(document).on('glpi.tab.loaded shown.bs.modal ajaxComplete', function (event) {
            applyRestrictions(event.target || document);
        });

        const observer = new MutationObserver(function (mutations) {
            for (let i = 0; i < mutations.length; i++) {
                if (mutations[i].addedNodes && mutations[i].addedNodes.length > 0) {
                    applyRestrictions(document);
                    return;
                }
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
})(jQuery);
JAVASCRIPT;
