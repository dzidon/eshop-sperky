import { loaderOpen } from './app';
import { loaderClose } from './app';
import { errorModalOpen } from './app';

$(document).ready(function()
{
    const companyCheckbox = $('.company-checkbox');
    const billingAddressCheckbox = $('.billing-address-checkbox');
    const noteCheckbox = $('.note-checkbox');

    /*
        Firma
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
        Doručovací adresa
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
        Poznámka
     */
    noteCheckbox.each(function()
    {
        hideOrShowFields(this, 'note-container');
    });

    noteCheckbox.change(function()
    {
        hideOrShowFields(this, 'note-container');
    });
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