import { errorModalOpenBasedOnResponse, loaderOpen, loaderClose } from './app';
require('./packetaLibrary');

const formOrderMethods = $('#form-order-methods');
let requestInProgress = false;

$(document).ready(function()
{
    initialize();
});

function initialize()
{
    // formulář obsahující platební a doručovací metody
    if(formOrderMethods)
    {
        // doprava
        $('.delivery-normal').on('click', function()
        {
            ajaxUpdateOrderMethods(formOrderMethods.attr('action'), formOrderMethods.serialize());
            $(".method-delivery").prop("checked", false);
        });

        $('.delivery-packeta-cz').on('click', function(e)
        {
            Packeta.Widget.pick( $(this).data('packeta-key'), onPacketaCzFinished, {
                country: 'cz',
                language: 'cs',
            });

            e.preventDefault();
        });

        // platba
        $('.method-payment').on('click', function()
        {
            ajaxUpdateOrderMethods(formOrderMethods.attr('action'), formOrderMethods.serialize());
            $(".method-payment").prop("checked", false);
        });
    }
}

function onPacketaCzFinished(data)
{
    if(typeof(data) != "undefined" && data !== null && formOrderMethods)
    {
        const packetaRadio = $(".delivery-packeta-cz");

        packetaRadio.prop("checked", true);
        $('.staticAddressDeliveryAdditionalInfo').val(data['id']);
        $('.staticAddressDeliveryCountry').val('Česká republika');
        $('.staticAddressDeliveryStreet').val(data['street']);
        $('.staticAddressDeliveryTown').val(data['city']);
        $('.staticAddressDeliveryZip').val(data['zip']);

        ajaxUpdateOrderMethods(formOrderMethods.attr('action'), formOrderMethods.serialize());
        packetaRadio.prop("checked", false);
    }
}

function ajaxUpdateOrderMethods(url, data)
{
    if(requestInProgress)
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
        errorModalOpenBasedOnResponse(jqXHR['responseJSON'], 'Nepodařilo se aktualizovat dopravu a platbu, zkuste to prosím později.');
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

    formOrderMethods.html(data['html']);

    if (typeof(data['totalProducts']) != "undefined" && data['totalProducts'] !== null)
    {
        $('.navbar-cart-total-products').text(data['totalProducts']);
    }
}

function cleanUp()
{
    loaderClose();
    initialize();
    requestInProgress = false;
}