import { loaderOpen, loaderClose, errorModalOpenBasedOnResponse } from './app';

let requestInProgress = false;

$(document).ready(function()
{
    initialize();
});

function initialize()
{
    // formulář celého košíku
    const formCartUpdate = $('#form-cart-update');
    if (formCartUpdate)
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
        const cartWrapper = $('#cart-wrapper');
        const url = cartWrapper.data('cart-remove-url');
        const csrfToken = cartWrapper.data('cart-remove-csrf-token');

        const data = {
            'cart_remove_form': {
                'cartOccurenceId': $(this).data('cart-occurence-id'),
                '_token': csrfToken,
            }
        };

        ajaxUpdateCart(url, data);
    });
}

function ajaxUpdateCart(url, data)
{
    if (requestInProgress)
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
        errorModalOpenBasedOnResponse(jqXHR['responseJSON'], 'Nepodařilo se aktualizovat košík, zkuste to prosím znovu.');
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

function cleanUp()
{
    loaderClose();
    M.updateTextFields();
    $('.tooltipped').tooltip();
    initialize();
    requestInProgress = false;
}