import { loaderOpen } from './app';
import { loaderClose } from './app';
import { errorModalOpenBasedOnResponse } from './app';

/*
    Elementy
 */
const companyCheckbox = $('.company-checkbox');
const billingAddressCheckbox = $('.billing-address-checkbox');
const noteCheckbox = $('.note-checkbox');

const linkLoadAddressDelivery = $('#load-address-delivery');
const linkLoadAddressBilling = $('#load-address-billing');

const addressesSelect = $('#addresses-select');

/*
    Proměnné
 */
const addressTypeDelivery = 'delivery';
const addressTypeBilling = 'billing';

let addressToLoad = addressTypeDelivery;
let requestInProgress = false;

$(document).ready(function()
{
    /*
        Checkbox - Firma
     */
    companyCheckbox.each(function()
    {
        hideOrShowFields(this, 'company-fields');
    });

    companyCheckbox.change(function()
    {
        hideOrShowFields(this, 'company-fields');
    });

    /*
        Checkbox - Doručovací adresa
     */
    billingAddressCheckbox.each(function()
    {
        hideOrShowFields(this, 'billing-address-fields');
    });

    billingAddressCheckbox.change(function()
    {
        hideOrShowFields(this, 'billing-address-fields');
    });

    /*
        Checkbox - Poznámka
     */
    noteCheckbox.each(function()
    {
        hideOrShowFields(this, 'note-container');
    });

    noteCheckbox.change(function()
    {
        hideOrShowFields(this, 'note-container');
    });

    /*
        Modal - doručovací adresa
     */
    linkLoadAddressDelivery.click(function ()
    {
        addressLoadModalOpen(addressTypeDelivery);
    });

    /*
        Modal - fakturační adresa
     */
    linkLoadAddressBilling.click(function ()
    {
        addressLoadModalOpen(addressTypeBilling);
    });

    /*
        Select s adresami
     */
    addressesSelect.change(ajaxGetAddressData);
});

function hideOrShowFields(checkbox, containerName)
{
    const container = $('#' + containerName);
    const inputsInside = $('#' + containerName + ' :input');

    if(checkbox.checked)
    {
        container.show();
        inputsInside.attr("disabled", false);
    }
    else
    {
        container.hide();
        inputsInside.attr("disabled", true);
    }
}

function addressLoadModalOpen(addressType)
{
    addressToLoad = addressType;

    M.Modal.getInstance($('#modal-addresses')).open();
}

function ajaxGetAddressData()
{
    if(requestInProgress)
    {
        return;
    }
    requestInProgress = true;

    const addressId = addressesSelect.val();
    const url = addressesSelect.data('url');

    M.Modal.getInstance($('#modal-addresses')).close();
    loaderOpen();

    $.post({
        url: url,
        data: {'addressId': addressId},
        dataType: 'json',
    })
    .done(function (data)
    {
        fillAddressFields(data['addressData']);
    })
    .fail(function (jqXHR)
    {
        errorModalOpenBasedOnResponse(jqXHR['responseJSON']);
    })
    .always(function ()
    {
        cleanUp();
    });
}

function fillAddressFields(addressData)
{
    switch (addressToLoad)
    {
        case 'delivery':
        {
            $('.addressDeliveryNameFirst').val(addressData['nameFirst']);
            $('.addressDeliveryNameLast').val(addressData['nameLast']);
            $('.addressDeliveryStreet').val(addressData['street']);
            $('.addressDeliveryAdditionalInfo').val(addressData['additionalInfo']);
            $('.addressDeliveryTown').val(addressData['town']);
            $('.addressDeliveryZip').val(addressData['zip']);

            const countrySelect = $('.addressDeliveryCountry');
            countrySelect.val(addressData['country']);
            countrySelect.formSelect();

            break;
        }
        case 'billing':
        {
            $('.addressBillingNameFirst').val(addressData['nameFirst']);
            $('.addressBillingNameLast').val(addressData['nameLast']);
            $('.addressBillingStreet').val(addressData['street']);
            $('.addressBillingAdditionalInfo').val(addressData['additionalInfo']);
            $('.addressBillingTown').val(addressData['town']);
            $('.addressBillingZip').val(addressData['zip']);

            $('.addressBillingCompany').val(addressData['company']);
            $('.addressBillingIc').val(addressData['ic']);
            $('.addressBillingDic').val(addressData['dic']);

            const countrySelect = $('.addressBillingCountry');
            countrySelect.val(addressData['country']);
            countrySelect.formSelect();

            if (addressData['nameFirst'] !== null || addressData['nameLast'] !== null || addressData['street'] !== null || addressData['additionalInfo'] !== null || addressData['town'] !== null || addressData['zip'] !== null)
            {
                billingAddressCheckbox.prop('checked', true);
                billingAddressCheckbox.checked = true;
                hideOrShowFields(billingAddressCheckbox, 'billing-address-fields');
            }

            if (addressData['company'] !== null || addressData['ic'] !== null || addressData['dic'] !== null)
            {
                companyCheckbox.prop('checked', true);
                companyCheckbox.checked = true;
                hideOrShowFields(companyCheckbox, 'company-fields');
            }

            break;
        }
    }
}

function cleanUp()
{
    loaderClose();
    addressesSelect.prop('selectedIndex', 0);
    addressesSelect.formSelect();
    M.updateTextFields();
    requestInProgress = false;
}