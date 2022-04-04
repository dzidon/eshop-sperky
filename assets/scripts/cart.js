import { loaderOpen } from './app';
import { loaderClose } from './app';
import { errorModalOpen } from './app';

let requestInProgress = false;

$(document).ready(function()
{
    initialize();
});

function initialize()
{
    // formulář celého košíku
    const formCartUpdate = $('#form-cart-update');
    if(formCartUpdate)
    {
        formCartUpdate.on('change', function()
        {
            ajaxUpdateCart(formCartUpdate.attr('action'), formCartUpdate.serialize());
        });

        formCartUpdate.on('submit', function (e)
        {
            ajaxUpdateCart(formCartUpdate.attr('action'), formCartUpdate.serialize());
            e.preventDefault();
        });
    }

    // tlačítka pro odstranění
    $('.button-cart-remove').click(function()
    {
        const url = $('#cart-wrapper').data('cart-remove-url');
        const data = {
            'cartOccurenceId': $(this).data('cart-occurence-id'),
        };

        ajaxUpdateCart(url, data);
    });
}

function ajaxUpdateCart(url, data)
{
    if(requestInProgress)
    {
        return;
    }
    requestInProgress = true;

    loaderOpen('Aktualizuji košík...');

    $.post({
        url: url,
        data: data,
        dataType: 'json',
    })
    .done(function (data)
    {
        renderCart(data);
    })
    .fail(function (jqXHR)
    {
        renderErrors(jqXHR['responseJSON']);
        renderCart(jqXHR['responseJSON']);
    })
    .always(function ()
    {
        cleanUp();
    });
}

function renderCart(data)
{
    if (typeof(data) == "undefined")
    {
        return;
    }

    $('#cart-container').html(data['html']);
    $('#flash-container').html(data['flashHtml']);

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
        errorModalOpen('Nepodařilo se aktualizovat košík, zkuste to prosím znovu.');
    }
}

function cleanUp()
{
    loaderClose();
    M.updateTextFields();
    initialize();
    requestInProgress = false;
}