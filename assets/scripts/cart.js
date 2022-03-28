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

        formCartUpdate.on('submit', function(e)
        {
            e.preventDefault();
            ajaxUpdateCart(formCartUpdate.attr('action'), formCartUpdate.serialize());
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

    $('#modal-loader-text').text('Aktualizuji košík...');
    M.Modal.getInstance($('#modal-loader')).open();

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
    $('#cart-container').html(data['html']);
    $('#flash-container').html(data['flashHtml']);

    if (typeof(data['totalProducts']) != "undefined" && data['totalProducts'] !== null)
    {
        $('.navbar-cart-total-products').text(data['totalProducts']);
    }
}

function renderErrors(data)
{
    if (Array.isArray(data['errors']) && data['errors'].length > 0)
    {
        const errors = data['errors'].join('<br>');
        $('#modal-error-text').html(errors);
        $('#modal-error-heading').text('Chyba');
    }
    else
    {
        $('#modal-error-text').text('Nepodařilo se aktualizovat košík, zkuste to prosím znovu.');
        $('#modal-error-heading').text('Chyba');
    }
    M.Modal.getInstance($('#modal-error')).open();
}

function cleanUp()
{
    M.Modal.getInstance($('#modal-loader')).close();
    M.updateTextFields();
    initialize();
    requestInProgress = false;
}