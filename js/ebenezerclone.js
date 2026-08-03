(function ($) {
    var isTicketContext = false;

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
        return itemtype === 'ticket';
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
            var html = ''
                + '<div class="list-group-item" data-ebz-relation-id="' + item.relation_id + '">'
                + '  <div class="row">'
                + '    <div class="col-auto">' + $('<div/>').text(linkLabel).html() + '</div>'
                + '    <div class="col text-truncate">'
                + '      <span class="col-9 overflow-hidden text-nowrap">' + $('<div/>').text(title).html() + '</span>'
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
            });
    };

    $(function () {
        isTicketContext = detectTicketContext();
        if (!isTicketContext) {
            return;
        }

        patchLinkedTicketsVisibility();
    });
})(jQuery);
