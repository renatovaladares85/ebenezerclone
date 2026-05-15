(function ($) {
    var shouldLockActorFields = {
        requester: false,
        observer: false,
        assign: false
    };
    var shouldLockProperties = false;
    var propertyLockFields = [];
    var isTicketContext = false;
    var shouldHideTicketCloneAction = false;
    var shouldHideCloneMassiveAction = false;
    var lockedPropertyNames = [
        'date',
        'time_to_resolve',
        'solvedate',
        'closedate',
        'type',
        'itilcategories_id',
        'status',
        'requesttypes_id',
        'urgency',
        'impact',
        'priority',
        'locations_id',
        '_contracts_id',
        'actiontime',
        'slas_id_ttr',
        'slas_id_tto',
        'olas_id_ttr',
        'olas_id_tto',
        'time_to_own'
    ];

    var isCloneAction = function (actionKey) {
        if (!actionKey) {
            return false;
        }
        return /clone/i.test(String(actionKey).trim());
    };

    var removeTicketCloneAction = function (root) {
        var $root = $(root);

        if (shouldHideCloneMassiveAction) {
            $root.find('select[name="massiveaction"] option').each(function () {
                var $option = $(this);
                var actionValue = ($option.val() || '').toString();
                if (isCloneAction(actionValue)) {
                    $option.remove();
                }
            });
        }

        if (shouldHideTicketCloneAction) {
            $root.find('#single-ma-action-menu [data-action]').each(function () {
                var $action = $(this);
                var actionKey = ($action.data('action') || '').toString();
                if (isCloneAction(actionKey)) {
                    $action.remove();
                }
            });
        }
    };

    var getTicketId = function () {
        if (!isTicketContext) {
            return 0;
        }

        var fromQuery = parseInt((new URLSearchParams(window.location.search)).get('id') || '0', 10);
        if (fromQuery > 0) {
            return fromQuery;
        }

        var fromInput = parseInt(($('form input[name=id]').first().val() || '0'), 10);
        return fromInput > 0 ? fromInput : 0;
    };

    var detectTicketContext = function () {
        var path = (window.location.pathname || '').toLowerCase();
        if (path.indexOf('/front/ticket.form.php') !== -1 || path.indexOf('/front/ticket.php') !== -1) {
            return true;
        }

        var itemtype = ($('input[name=itemtype]').first().val() || '').toString().toLowerCase();
        if (itemtype === 'ticket') {
            return true;
        }

        return false;
    };

    var markSelect2AsReadonly = function ($field, cssClass) {
        var $container = $field.next('.select2');
        if (!$container.length) {
            return;
        }

        $container.addClass(cssClass).attr('aria-disabled', 'true');
        $container.find('.select2-selection').css({
            'pointer-events': 'none',
            'opacity': '0.75'
        });
    };

    var lockActorFieldByType = function (root, actorType) {
        var $root = $(root);
        var $actorSelect = $root.find('select[data-actor-type="' + actorType + '"]');
        if (!$actorSelect.length) {
            return;
        }

        $actorSelect.each(function () {
            var $field = $(this);
            $field.prop('disabled', true).attr('aria-disabled', 'true');

            markSelect2AsReadonly($field, 'ebenezerclone-assigned-readonly');
        });

        $('button[form^="addme_as_' + actorType + '_"]').prop('disabled', true).hide();
    };

    var lockActorFields = function (root) {
        ['requester', 'observer', 'assign'].forEach(function (actorType) {
            if (shouldLockActorFields[actorType]) {
                lockActorFieldByType(root, actorType);
            }
        });
    };

    var lockPropertiesField = function (root) {
        var $root = $(root);
        var targetNames = propertyLockFields.length ? propertyLockFields : lockedPropertyNames;
        var selectors = targetNames.map(function (name) {
            return '[name="' + name + '"], [name="' + name + '[]"]';
        }).join(', ');

        if (!selectors) {
            return;
        }

        $root.find(selectors).each(function () {
            var $field = $(this);
            var $form = $field.closest('form');
            var formId = ($form.attr('id') || '').toString();
            if (formId.indexOf('ebenezerclone_form_') === 0) {
                return;
            }

            var tagName = ($field.prop('tagName') || '').toLowerCase();
            var type = (($field.attr('type') || '') + '').toLowerCase();
            //var $flatpickr = $field.closest('.flatpickr');

            if (tagName === 'input' && (type === 'text' || type === 'datetime-local' || type === 'date' || type === 'time' || type === 'number')) {
                $field.prop('readonly', true).prop('disabled', true);
            } else {
                $field.prop('disabled', true);
            }

            $field.attr('aria-disabled', 'true').addClass('disabled');

            markSelect2AsReadonly($field, 'ebenezerclone-properties-readonly');

            /*if ($flatpickr.length) {
                $flatpickr.addClass('ebenezerclone-properties-readonly disabled');
                $flatpickr.find('[data-toggle], [data-clear], .input-button, .input-group-text').attr('aria-disabled', 'true').addClass('disabled').css({
                    'pointer-events': 'none',
                    'opacity': '0.75'
                });
            }*/
        });
    };

    var applyActorGuard = function (actorPermissions) {
        var permissions = actorPermissions || {};
        ['requester', 'observer', 'assign'].forEach(function (actorType) {
            shouldLockActorFields[actorType] = Object.prototype.hasOwnProperty.call(permissions, actorType)
                ? !Boolean(permissions[actorType])
                : false;
        });
        lockActorFields(document);
    };

    var ensureLinkedTicketsBadge = function (totalLinks) {
        var $header = $('#linked_tickets-heading');
        if (!$header.length || totalLinks <= 0) {
            return;
        }

        var $badge = $header.find('.badge.bg-secondary').first();
        if (!$badge.length) {
            var $title = $header.find('.item-title').first();
            if ($title.length) {
                $badge = $('<span class="badge bg-secondary ms-2"></span>');
                $title.after($badge);
            }
        }

        if ($badge.length) {
            $badge.text(String(totalLinks));
        }
    };

    var ensureLinkedTicketsList = function () {
        var $root = $('#linked_tickets .accordion-body').first();
        if (!$root.length) {
            return $();
        }

        var $list = $root.find('.list-group').first();
        if ($list.length) {
            return $list;
        }

        var $card = $('<div class="card"></div>');
        $list = $('<div class="list-group list-group-flush list-group-hoverable"></div>');
        $card.append($list);
        $root.prepend($card);
        return $list;
    };

    var appendMissingLinkedTickets = function (items) {
        if (!items || !items.length) {
            return;
        }

        var $list = ensureLinkedTicketsList();
        if (!$list.length) {
            return;
        }

        items.forEach(function (item) {
            if (!item || !item.relation_id || !item.tickets_id) {
                return;
            }

            var relationSelector = '[data-ebz-relation-id="' + item.relation_id + '"]';
            if ($list.find(relationSelector).length) {
                return;
            }

            var linkLabel = item.link_label || '';
            var title = item.title || ('Ticket #' + item.tickets_id);
            var href = item.url || '#';

            var html = ''
                + '<div class="list-group-item" data-ebz-relation-id="' + item.relation_id + '">'
                + '  <div class="row">'
                + '    <div class="col-auto">' + $('<div/>').text(linkLabel).html() + '</div>'
                + '    <div class="col text-truncate">'
                + '      <a href="' + $('<div/>').text(href).html() + '" class="col-9 overflow-hidden text-nowrap">'
                + '        <span>' + $('<div/>').text(title).html() + '</span>'
                + '      </a>'
                + '    </div>'
                + '  </div>'
                + '</div>';
            $list.append(html);
        });
    };

    var patchLinkedTicketsVisibility = function () {
        if (!isTicketContext) {
            return;
        }

        var ticketsId = getTicketId();
        if (ticketsId <= 0) {
            return;
        }

        var rootDoc = (window.CFG_GLPI && window.CFG_GLPI.root_doc) ? window.CFG_GLPI.root_doc : '';
        var endpoint = rootDoc + '/plugins/ebenezerclone/front/linked_tickets_visibility.php';

        $.getJSON(endpoint, { tickets_id: ticketsId })
            .done(function (data) {
                if (!(data && data.ok)) {
                    return;
                }

                appendMissingLinkedTickets(data.items || []);
                ensureLinkedTicketsBadge(parseInt(data.total_links || 0, 10));
            });
    };

    var checkAssignedPermission = function () {
        if (!isTicketContext) {
            return;
        }

        var ticketsId = getTicketId();

        var rootDoc = (window.CFG_GLPI && window.CFG_GLPI.root_doc) ? window.CFG_GLPI.root_doc : '';
        var endpoint = rootDoc + '/plugins/ebenezerclone/front/assign_permission.php';
        var payload = {};
        if (ticketsId > 0) {
            payload.tickets_id = ticketsId;
        }

        $.getJSON(endpoint, payload)
            .done(function (data) {
                shouldHideTicketCloneAction = !(data && data.can_use_ticket_clone_action);
                shouldHideCloneMassiveAction = !(data && data.can_use_massive_clone);
                removeTicketCloneAction(document);
                if (ticketsId <= 0) {
                    return;
                }
                shouldLockProperties = !!(data && data.ok && data.should_lock_properties);
                propertyLockFields = (data && data.ok && Array.isArray(data.property_lock_fields))
                    ? data.property_lock_fields
                    : [];
                if (shouldLockProperties) {
                    lockPropertiesField(document);
                }
                applyActorGuard((data && data.ok && data.actor_permissions) ? data.actor_permissions : {});
            });
    };

    $(function () {
        isTicketContext = detectTicketContext();
        if (!isTicketContext) {
            return;
        }

        checkAssignedPermission();
        patchLinkedTicketsVisibility();

        if (typeof MutationObserver === 'undefined') {
            return;
        }

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) {
                        removeTicketCloneAction(node);
                        if (shouldLockProperties) {
                            lockPropertiesField(node);
                        }
                        lockActorFields(node);
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
})(jQuery);
