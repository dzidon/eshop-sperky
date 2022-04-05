import { loaderOpen } from './app';
import { loaderClose } from './app';
import { errorModalOpen } from './app';
require('./packetaLibrary');

let requestInProgress = false;

$(document).ready(function()
{
    initialize();
});

function initialize()
{
    // formulář obsahující platební a doručovací metody
    const formOrderMethods = $('#form-order-methods');
    if(formOrderMethods)
    {
        $('.method-normal').on('click', function(e)
        {
            ajaxUpdateOrderMethods(formOrderMethods.attr('action'), formOrderMethods.serialize());
            e.preventDefault();
        });

        $('.method-packeta-cz').on('click', function(e)
        {
            Packeta.Widget.pick( $(this).data('packeta-key'), console.log, {
                country: 'cs',
                language: 'cs',
            });
            e.preventDefault();
        });
    }
}

function ajaxUpdateOrderMethods(url, data)
{
    if(requestInProgress || data === null)
    {
        return;
    }
    requestInProgress = true;

    loaderOpen('Aktualizuji...');

    $.post({
        url: url,
        data: data,
        dataType: 'json',
    })
    .done(function (data)
    {
        renderOrderMethods(data);
    })
    .fail(function (jqXHR)
    {
        renderErrors(jqXHR['responseJSON']);
        renderOrderMethods(jqXHR['responseJSON']);
    })
    .always(function ()
    {
        cleanUp();
    });
}

function renderOrderMethods(data)
{
    if (typeof(data) == "undefined")
    {
        return;
    }

    $('#form-order-methods').html(data['html']);

    if (typeof(data['totalProducts']) != "undefined" && data['totalProducts'] !== null)
    {
        $('.navbar-cart-total-products').text(data['totalProducts']);
    }
}

function renderErrors(data)
{
    if (typeof(data) != "undefined" && Array.isArray(data['errors']) && data['errors'].length > 0)
    {
        const errors = data['errors'].join('<br>');
        errorModalOpen(errors);
    }
    else
    {
        errorModalOpen('Nepodařilo se aktualizovat dopravu a platbu, zkuste to prosím později.');
    }
}

function cleanUp()
{
    loaderClose();
    initialize();
    requestInProgress = false;
}